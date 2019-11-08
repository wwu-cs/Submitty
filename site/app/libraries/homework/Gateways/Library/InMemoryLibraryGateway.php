<?php namespace app\libraries\homework\Gateways\Library;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;

class InMemoryLibraryGateway implements LibraryGateway {
    /** @var LibraryEntity[] */
    protected $libraries = [];

    public function addLibrary(LibraryEntity $library): string {
        if ($this->libraryExists($library)) {
            return 'Library already exists';
        }

        $this->libraries[] = $library;

        return 'success';
    }

    /** @inheritDoc */
    public function addGitLibrary(LibraryEntity $library, string $repoUrl): string {
        return $this->addLibrary($library);
    }

    /** @inheritDoc */
    public function addZipLibrary(LibraryEntity $library, string $tmpFilePath): string {
        return $this->addLibrary($library);
    }

    /** @inheritDoc */
    public function getAllLibraries(string $location): array {
        $results = [];

        /** @var LibraryEntity $library */
        foreach ($this->libraries as $library) {
            if ($library->hasLocationOf($location)) {
                $results[] = $library;
            }
        }

        return $results;
    }

    /** @inheritDoc */
    public function libraryExists(LibraryEntity $library): bool {
        return count(array_filter($this->libraries, function (LibraryEntity $item) use ($library) {
            return $item->is($library);
        })) > 0;
    }
}
