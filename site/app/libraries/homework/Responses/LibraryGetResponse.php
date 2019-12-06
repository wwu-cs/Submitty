<?php

namespace app\libraries\homework\Responses;

class LibraryGetResponse {
    /** @var string[] */
    protected $libraries = [];

    public function addLibrary(string $lib) {
        $this->libraries[] = $lib;
    }

    /**
     * Returns an array the library names
     *
     * @return string[]
     */
    public function getResults(): array {
        return $this->libraries;
    }
}
