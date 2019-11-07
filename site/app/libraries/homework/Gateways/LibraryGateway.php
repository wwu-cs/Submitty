<?php namespace app\libraries\homework\Gateways;


interface LibraryGateway
{
    /**
     * This will clone the provided repo url into the specified location.
     * On success will return 'success', and on failure will return an error message
     *
     * @param string $repoUrl
     * @param string $location
     * @return string
     */
    public function addGitLibrary(string $repoUrl, string $location): string;

    /**
     * This will add a library via a zip file, and unzip the contents to the specified location.
     * On success will return 'success', and on failure will return an error message
     *
     * @param string $filePath
     * @param string $location
     * @return string
     */
    public function addZipLibrary(string $filePath, string $location): string;

    /**
     * Returns all libraries from the specified homework library location
     *
     * @param string $location
     * @return string[]
     */
    public function getAllLibraries(string $location): array;

    /**
     * Checks to see if a library already exists.
     * Returns true if the library does exist, and false if it does not.
     *
     * @param string $name
     * @param string $location
     * @return bool
     */
    public function libraryExists(string $name, string $location): bool;

}
