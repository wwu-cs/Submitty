<?php

namespace app\libraries\homework\UseCases;

class LibraryGetResponse {
    /** @var string[] */
    protected $libraries = [];

    public function addLibrary(string $lib) {
        $this->libraries[] = $lib;
    }

    public function getResults(): array {
        return $this->libraries;
    }
}
