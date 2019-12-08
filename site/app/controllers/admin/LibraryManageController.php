<?php

namespace app\controllers\admin;

use app\libraries\Core;
use app\libraries\FileUtils;
use app\libraries\response\Response;
use app\controllers\AbstractController;
use app\exceptions\NotEnabledException;
use app\libraries\response\WebResponse;
use app\libraries\response\JsonResponse;
use app\exceptions\AuthorizationException;
use Symfony\Component\Routing\Annotation\Route;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\UseCases\LibraryAddUseCase;
use app\libraries\homework\UseCases\LibraryGetUseCase;
use app\libraries\homework\UseCases\LibraryRemoveUseCase;
use app\libraries\homework\UseCases\LibraryUpdateUseCase;

/**
 * Class LibraryManageController
 *
 * Following the clean architecture
 * https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html
 *
 * @package app\controllers\admin
 */
class LibraryManageController extends AbstractController {
    const DATE_FORMAT = 'd M, Y H:i:s';

    /**
     * @param Core $core
     * @throws NotEnabledException
     * @throws AuthorizationException
     */
    public function __construct(Core $core) {
        parent::__construct($core);
        $homeworkLibraryAccessLevel = $this->core->getConfig()->homeworkLibraryAccessLevel();

        if (!$this->core->getUser()->canAccess($homeworkLibraryAccessLevel)) {
            throw new AuthorizationException('You do not have permission to access this route', 401);
        }

        if (!$this->core->getConfig()->useHomeworkLibrary()) {
            throw new NotEnabledException();
        }
    }

    /**
     * Takes an array of metadata entities and translates them into presentable arrays
     *
     * @param array $libraryMetadata
     * @return MetadataEntity[]
     */
    protected function presentMetadata(array $libraryMetadata): array {
        $response = [];

        /** @var MetadataEntity $meta */
        foreach ($libraryMetadata as $meta) {
            $response[] = [
                'key'                  => $meta->getLibrary()->getKey(),
                'name'                 => $meta->getName(),
                'source'               => $meta->getSourceType(),
                'number_of_gradeables' => $meta->getGradeableCount(),
                'updated_at'           => $meta->getLastUpdatedDate()->format(self::DATE_FORMAT),
                'created_at'           => $meta->getCreatedDate()->format(self::DATE_FORMAT),
            ];
        }

        return $response;
    }

    /**
     * Controller route to show the homework library page.
     *
     * @Route("/homework/library/manage", methods={"GET"})
     * @return Response
     */
    public function showLibraryManagePage() {
        $useCase = new LibraryGetUseCase($this->core);

        $results = $useCase->getLibraries()->getResults();

        $response = $this->presentMetadata($results);

        return Response::WebOnlyResponse(
            new WebResponse(
                [
                    'admin',
                    'LibraryManager',
                ], 'showLibraryManager',
                'Do all your fancy homework library things here!',
                $response
            )
        );
    }

    /**
     * Function for uploading a zipped up library to the server. This should be called via AJAX, saving the result
     * to the json_buffer of the Output object, return a true or false on whether or not it succeeded.
     *
     * @Route("/homework/library/manage/upload/zip", methods={"POST"})
     * @return Response
     */
    public function ajaxUploadLibraryFromZip(): Response {
        $useCase = new LibraryAddUseCase($this->core);

        $fileInfo = $_FILES['zip'];

        $results = $useCase->addZipLibrary(
            $fileInfo ?? null,
            $_POST['name'] ?? null
        );

        if ($fileInfo && isset($fileInfo['tmp_name'])) {
            FileUtils::rmFile($fileInfo['tmp_name']);
        }

        if ($results->error) {
            $response = JsonResponse::getFailResponse($results->error);
        }
        else {
            $response = JsonResponse::getSuccessResponse($results->getMessage());
        }

        return Response::JsonOnlyResponse($response);
    }

    /**
     * Function for adding a homework library to the server from a git repository. This should be called via AJAX,
     * saving the result to the json_buffer of the Output object, returns a true or false on whether or not it
     * succeeded.
     *
     * @Route("/homework/library/manage/upload/git", methods={"POST"})
     * @return Response
     */
    public function ajaxUploadLibraryFromGit(): Response {
        $useCase = new LibraryAddUseCase($this->core);

        $results = $useCase->addGitLibrary(
            $_POST['git_url'] ?? null,
            $_POST['name'] ?? null
        );

        if ($results->error) {
            $response = JsonResponse::getFailResponse($results->error);
        }
        else {
            $response = JsonResponse::getSuccessResponse($results->getMessage());
        }

        return Response::JsonOnlyResponse($response);
    }

    /**
     * Function for returning all libraries stored on the system. This should be called via AJAX
     * saving the result to the json_buffer of the Output object, returns a true or false on
     * whether or not it succeeded.
     *
     * @Route("/homework/library/manage/list", methods={"GET"})
     * @return Response
     */
    public function ajaxGetLibraryList(): Response {
        $useCase = new LibraryGetUseCase($this->core);

        $results = $useCase->getLibraries()->getResults();

        $response = $this->presentMetadata($results);

        return Response::JsonOnlyResponse(
            JsonResponse::getSuccessResponse($response)
        );
    }

    /**
     * Function for deleting a specific library stored on the system. This should be called via
     * a DELETE AJAX request. It then returns json data to the caller about the request specifying
     * if it was successful or not and any error messages.
     *
     * @Route("/homework/library/manage/remove/{name}", methods={"DELETE"})
     * @param string $name
     * @return Response
     */
    public function ajaxRemoveLibrary(string $name): Response {
        $useCase = new LibraryRemoveUseCase($this->core);

        $results = $useCase->removeLibrary($name);

        if ($results->error) {
            $response = JsonResponse::getFailResponse($results->error);
        }
        else {
            $response = JsonResponse::getSuccessResponse($results->getMessage());
        }

        return Response::JsonOnlyResponse($response);
    }


    /**
     * Function for updating a specific git library stored on the system. This should be called via
     * a PATCH AJAX request. It then returns json data to the caller with a status message saying why
     * the request failed if it failed, otherwise it will just return a success message to be displayed
     * to the user.
     *
     * @Route("/homework/library/manage/update/{name}", methods={"PATCH"})
     * @param string $name
     * @return Response
     */
    public function ajaxUpdateLibrary(string $name): Response {
        $useCase = new LibraryUpdateUseCase($this->core);

        $result = $useCase->updateLibrary($name);

        if (!$result->success) {
            $response = JsonResponse::getFailResponse($result->getMessage());
        }
        else {
            $response = JsonResponse::getSuccessResponse($result->getMessage());
        }

        return Response::JsonOnlyResponse($response);
    }
}
