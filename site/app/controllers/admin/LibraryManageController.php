<?php

namespace app\controllers\admin;


use app\libraries\Core;
use app\controllers\AbstractController;
use app\exceptions\NotEnabledException;
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
        $response = $useCase->addZipLibrary($_FILES['zip']);

        if ($response->error) {
            return $this->core->getOutput()->renderResultMessage(
                $response->error,
                false
            );
        }

        return $this->core->getOutput()->renderResultMessage(
            $response->getMessage(),
            true
        );
    }

    /**
     * Function for adding a homework library to the server from a git repository. This should be called via AJAX,
     * saving the result to the json_buffer of the Output object, returns a true or false on whether or not it
     * succeeded.
     *
     * @Route("/homework/library/upload/git", methods={"POST", "GET"})
     * @return array
     */
    public function ajaxUploadLibraryFromGit(): array {
        $useCase = new LibraryAddUseCase($this->core);
        $response = $useCase->addGitLibrary($_POST['git_url']);

        if ($response->error) {
            return $this->core->getOutput()->renderResultMessage(
                $response->error,
                false
            );
        }

        return $this->core->getOutput()->renderResultMessage(
            $response->getMessage(),
            true
        );
    }

}
