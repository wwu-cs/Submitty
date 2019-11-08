<?php

namespace app\controllers\admin;


use app\libraries\Core;
use app\controllers\AbstractController;
use app\exceptions\NotEnabledException;
use app\libraries\homework\UseCases\LibraryGetUseCase;
use app\libraries\homework\UseCases\LibraryRemoveUseCase;
use app\libraries\routers\AccessControl;
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

        if (!$this->core->getConfig()->useHomeworkLibrary()) {
            throw new NotEnabledException();
        }
    }

    /**
     * Function for uploading a zipped up library to the server. This should be called via AJAX, saving the result
     * to the json_buffer of the Output object, return a true or false on whether or not it succeeded.
     *
     * @Route("/homework/library/upload/zip", methods={"POST"})
     * @return array
     */
    public function ajaxUploadLibraryFromZip(): array {
        $useCase = new LibraryAddUseCase($this->core);

        // Equivalent to $_FILES['zip'] except for it doesn't generate a notice error.
        // Idk why E_NOTICE is enabled. It's dumb because it makes me have to do workarounds like this all over
        $file = (isset($_FILES['zip'])) ? $_FILES['zip'] : null;

        $response = $useCase->addZipLibrary($file);

        return $this->core->getOutput()->renderResultMessage(
            $response->error ?? $response->getMessage(),
            empty($response->error)
        );
    }

    /**
     * Function for adding a homework library to the server from a git repository. This should be called via AJAX,
     * saving the result to the json_buffer of the Output object, returns a true or false on whether or not it
     * succeeded.
     *
     * @Route("/homework/library/upload/git", methods={"POST"})
     * @return array
     */
    public function ajaxUploadLibraryFromGit(): array {
        $useCase = new LibraryAddUseCase($this->core);

        // Equivalent to $_POST['git_url'] except for it doesn't generate a notice error.
        // Idk why E_NOTICE is enabled. It's dumb because it makes me have to do workarounds like this all over
        $url = (isset($_POST['git_url'])) ? $_POST['git_url'] : null;

        $response = $useCase->addGitLibrary($url);

        return $this->core->getOutput()->renderResultMessage(
            $response->error ?? $response->getMessage(),
            empty($response->error)
        );
    }

    /**
     * Function for returning all libraries stored on the system. This should be called via AJAX
     * saving the result to the json_buffer of the Output object, returns a true or false on
     * whether or not it succeeded.
     *
     * @Route("/homework/library/list", methods={"GET"})
     * @return array
     */
    public function ajaxGetLibraryList(): array {
        $useCase = new LibraryGetUseCase($this->core);

        $response = $useCase->getLibraries();

        return $this->core->getOutput()->renderResultMessage(
            $response->error ?? $response->getResults(),
            empty($response->error)
        );
    }

    /**
     * Function for deleting a specific library stored on the system. This should be called via
     * a DELETE AJAX request. It then returns json data to the caller about the request specifying
     * if it was successful or not and any error messages.
     *
     * @Route("/homework/library/remove/{name}", methods={"DELETE"})
     * @param string $name
     * @return array
     */
    public function ajaxRemoveLibrary(string $name): array {
        $useCase = new LibraryRemoveUseCase($this->core);

        $response = $useCase->removeLibrary($name);

        return $this->core->getOutput()->renderResultMessage(
            $response->error ?? $response->getMessage(),
            empty($response->error)
        );

    }
}
