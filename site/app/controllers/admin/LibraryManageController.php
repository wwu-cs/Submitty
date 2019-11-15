<?php

namespace app\controllers\admin;


use app\models\User;
use app\libraries\Core;
use app\libraries\FileUtils;
use app\libraries\response\Response;
use app\controllers\AbstractController;
use app\exceptions\NotEnabledException;
use app\libraries\response\JsonResponse;
use app\libraries\routers\AccessControl;
use app\exceptions\AuthorizationException;
use app\exceptions\AuthenticationException;
use Symfony\Component\Routing\Annotation\Route;
use app\libraries\homework\UseCases\LibraryAddUseCase;

/**
 * Class LibraryManage
 *
 * Following the clean architecture
 * https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html
 *
 * @package app\controllers\admin
 * @AccessControl(role="INSTRUCTOR")
 */
class LibraryManageController extends AbstractController {
    /**
     * LibraryManage constructor.
     * @param Core $core
     * @throws NotEnabledException
     */
    public function __construct(Core $core) {
        parent::__construct($core);

        if ($this->core->userLoaded()) {
            throw new AuthenticationException('You must sign in to access this route', 403);
        }

        if ($this->core->getUser()->getAccessLevel() !== User::LEVEL_SUPERUSER) {
            throw new AuthorizationException('You must be superuser to access this route', 401);
        }

        if (!$this->core->getConfig()->useHomeworkLibrary()) {
            throw new NotEnabledException();
        }
    }

    /**
     * Function for uploading a zipped up library to the server. This should be called via AJAX, saving the result
     * to the json_buffer of the Output object, return a true or false on whether or not it succeeded.
     *
     * @Route("/homework/library/upload/zip", methods={"POST"})
     * @return Response
     */
    public function ajaxUploadLibraryFromZip(): Response {
        $useCase = new LibraryAddUseCase($this->core);

        $fileInfo = $_FILES['zip'] ?? null;

        $results = $useCase->addZipLibrary($fileInfo);

        if ($fileInfo && isset($fileInfo['tmp_name'])) {
            FileUtils::rmFile($fileInfo['tmp_name']);
        }

        if ($results->error) {
            $response = JsonResponse::getFailResponse($results->error);
        } else {
            $response = JsonResponse::getSuccessResponse($results->getMessage());
        }

        return Response::JsonOnlyResponse($response);
    }

    /**
     * Function for adding a homework library to the server from a git repository. This should be called via AJAX,
     * saving the result to the json_buffer of the Output object, returns a true or false on whether or not it
     * succeeded.
     *
     * @Route("/homework/library/upload/git", methods={"POST"})
     * @return Response
     */
    public function ajaxUploadLibraryFromGit(): Response {
        $useCase = new LibraryAddUseCase($this->core);

        $results = $useCase->addGitLibrary($_POST['git_url'] ?? null);

        if ($results->error) {
            $response = JsonResponse::getFailResponse($results->error);
        } else {
            $response = JsonResponse::getSuccessResponse($results->getMessage());
        }

        return Response::JsonOnlyResponse($response);
    }

}
