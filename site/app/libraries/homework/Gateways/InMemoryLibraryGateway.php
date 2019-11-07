<?php namespace app\libraries\homework\Gateways;

class InMemoryLibraryGateway implements LibraryGateway {
    /** @var array */
    protected $libraries = [];

    public function addLibraryWithName(string $name, string $location) {
        if (!isset($this->libraries[$location])) {
            $this->libraries[$location] = [];
        }
        $this->libraries[$location][] = $name;
    }

    /** @inheritDoc */
    public function addGitLibrary(string $repoUrl, string $location): string {
        $parts = explode('/', $location);
        $name = end($parts);
        // Pop off last element as the location is going to include the library name, and we dont want that
        // for the storage layout we have setup in memory
        array_pop($parts);
        $location = implode('/', $parts);
        $this->addLibraryWithName($name, $location);
        return 'success';
    }

    /** @inheritDoc */
    public function addZipLibrary(string $filePath, string $location): string {
        $parts = explode('/', $location);
        $name = end($parts);
        // Pop off last element as the location is going to include the library name, and we dont want that
        // for the storage layout we have setup in memory
        array_pop($parts);
        $location = implode('/', $parts);
        $this->addLibraryWithName($name, $location);
        return 'success';
    }

    /** @inheritDoc */
    public function getAllLibraries(string $location): array {
        if (!isset($this->libraries[$location])) {
            return [];
        }
        return $this->libraries[$location];
    }

    /** @inheritDoc */
    public function libraryExists(string $name, string $location): bool {
        if (!isset($this->libraries[$location])) {
            return false;
        }
        return in_array($name, $this->libraries[$location]);
    }
}
