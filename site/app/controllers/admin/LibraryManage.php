<?php

namespace app\controllers\admin;


use ZipArchive;
use app\libraries\Core;
use app\libraries\FileUtils;
use app\controllers\AbstractController;
use app\libraries\routers\AccessControl;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LibraryManage
 * @package app\controllers\admin
 * @AccessControl(role="INSTRUCTOR")
 */
class LibraryManage extends AbstractController
{
    protected $libraryPath;

    public function __construct(Core $core) {
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
        $tmpName = $zipFile['tmp_name'];

        $parts = explode('.', $name);
        if (count($parts) != 2 || strtolower($parts[-1]) != '.zip') {
            return $this->core->getOutput()->renderResultMessage(
                'Please upload a .zip file with no periods other than the one for the file extension.',
                false
            );
        }

        $libName = $parts[0];

        if ($this->libraryExists($libName)) {
            return $this->core->getOutput()->renderResultMessage(
                'Library already exists.',
                false
            );
        }

        // Create the libraries folder
        $libraryLocation = $this->createLibraryLocation($libName);
        if (!$libraryLocation) {
            return $this->core->getOutput()->renderResultMessage(
                'Error creating library folder.',
                false
            );
        }

        $dst = FileUtils::joinPaths($libraryLocation, $name);
        if (!move_uploaded_file($tmpName, $dst)) {
            FileUtils::recursiveRmdir($libraryLocation);

            return $this->core->getOutput()->renderResultMessage(
                'Error uploading file',
                false
            );
        }

        $zip = new ZipArchive();
        $res = $zip->open($dst);
        if ($res === TRUE) {
            $zip->extractTo($libraryLocation);
            $zip->close();
        } else {
            FileUtils::recursiveRmdir($libraryLocation);

            return $this->core->getOutput()->renderResultMessage(
                'Could not unzip the file.',
                false
            );

        }

        FileUtils::rmFile($dst);

        return $this->core->getOutput()->renderResultMessage(
            'Successfully installed the new library'
        );
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

        $url = $_POST['git_url'];

        // Regex can be viewed in detail here:
        // https://www.debuggex.com/r/H4kRw1G0YPyBFjfm
        if (!preg_match(
            '/((git|ssh|http(s)?)|(git@[\w.]+))(:(\/\/)?)([\w.@:/\-~]+)(\.git)(\/)?/',
            $url,
            $matches
        )) {
            return $this->core->getOutput()->renderResultMessage(
                'The git url is not of the right format.',
                false
            );
        }

        /*
         * From the link above, one can easily see that group 7 is the wanted group.
         * We use index 8 because index 0 from preg match is the whole string.
         * We then split and take the name which is usually at the end of the url.
         * This will not work for same repo names with different authors, so
         * that will probably want to be fixed later.
         */
        $libName = explode('/', $matches[8])[-1];

        if ($this->libraryExists($libName)) {
            return $this->core->getOutput()->renderResultMessage(
                'Library already exists.',
                false
            );
        }

        // Create the libraries folder
        $libraryLocation = $this->createLibraryLocation($libName);
        if (!$libraryLocation) {
            return $this->core->getOutput()->renderResultMessage(
                'Error creating library folder.',
                false
            );
        }

        /*
         * I am not going to worry too much about bash injection here because
         * a user would already need full admin access to get to this point.
         * It also should be relatively protected with the escaping of shell arguments.
         */
        exec('git clone ' . escapeshellarg($url) . ' ' . escapeshellarg($libraryLocation));


        return $this->core->getOutput()->renderResultMessage(
            'Cloned the library repository.'
        );
    }

}
