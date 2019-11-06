<?php

namespace app\controllers\admin;


use app\controllers\AbstractController;
use app\libraries\Core;
use app\libraries\FileUtils;
use app\libraries\routers\AccessControl;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LibraryManage
 * @package app\controllers\admin
 * @AccessControl(role="INSTRUCTOR")
 */
class LibraryManage extends AbstractController {

    protected $libraryPath;


    public function __construct(Core $core)
    {
        parent::__construct($core);

        $this->libraryPath = $this->core->getConfig()->getHomeworkLibraryLocation();

    }

    /**
     * Function for checking to see if a library already exists
     *
     * @param string $name
     * @return bool
     */
    protected function libraryExists(string $name): bool {
        $libraries = FileUtils::getAllDirs($this->libraryPath);

        return in_array($name, $libraries);
    }

    /**
     * Function for creating the location for the library
     *
     * @param string $name
     * @return string
     */
    protected function createLibraryLocation(string $name): string {
        $path = FileUtils::joinPaths($this->libraryPath, $name);
        if (!FileUtils::createDir($path)) {
            return '';
        }

        return $path;
    }

    /**
     * Function for uploading a zipped up library to the server. This should be called via AJAX, saving the result
     * to the json_buffer of the Output object, return a true or false on whether or not it succeeded.
     *
     * @Route("/library/zip/upload")
     * @return array
     */
    public function ajaxUploadLibrary(): array {
        if (empty($_POST) || !isset($_FILES['zip'])) {
            return $this->core->getOutput()->renderResultMessage(
                'A .zip file must be provided to upload.',
                false
            );
        }
        $zipFile = $_FILES['zip'];
        $name = $zipFile['name'];
        $tmpName  = $zipFile['tmp_name'];
        $type = $zipFile['type'];

        $parts = explode('.', $name);
        if (count($parts) < 2 || strtolower($parts[-1]) != '.zip') {
            return $this->core->getOutput()->renderResultMessage(
                'Please upload a .zip file.',
                false
            );
        }


        // Check to see if the library exists
        return [];
    }

    /**
     * Function for adding a homework library to the server from a git repository. This should be called via AJAX,
     * saving the result to the json_buffer of the Output object, returns a true or false on whether or not it
     * succeeded.
     *
     * @Route("/librar/git/upload")
     * @return array
     */
    public function ajaxUploadLibraryFromGit(): array {
        if (empty($_POST) || !isset($_POST['git_url'])) {
            return $this->core->getOutput()->renderResultMessage(
                'A git repository must be provided to clone.',
                false
            );
        }


        return [];
    }

}
