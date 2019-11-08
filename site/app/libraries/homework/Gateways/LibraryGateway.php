<?php namespace app\libraries\homework\Gateways;


use app\libraries\homework\Entities\LibraryEntity;

interface LibraryGateway {
    /**
     * This will clone the provided repo url into the specified location.
     * On success will return 'success', and on failure will return an error message
     *
     * @param LibraryEntity $library
     * @param string $repoUrl
     * @return string
     */
    public function addGitLibrary(LibraryEntity $library, string $repoUrl): string;

    /**
     * This will add a library via a zip file, and unzip the contents to the specified location.
     * On success will return 'success', and on failure will return an error message
     *
     * @param LibraryEntity $library
     * @param string $tmpFilePath
     * @return string
     */
    public function addZipLibrary(LibraryEntity $library, string $tmpFilePath): string;

    /**
     * Returns all libraries from the specified homework library location
     *
     * @param string $location
     * @return LibraryEntity[]
     */
    public function getAllLibraries(string $location): array;

    /**
     * Checks to see if a library already exists.
     * Returns true if the library does exist, and false if it does not.
     *
     * @param LibraryEntity $library
     * @return bool
     */
    public function libraryExists(LibraryEntity $library): bool;

}
