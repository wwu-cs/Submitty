<?php

namespace app\libraries\homework\Responses;

use app\libraries\homework\Entities\MetadataEntity;

class LibraryGetResponse {
    /** @var MetadataEntity[] */
    protected $libraries = [];

    public function addLibrary(MetadataEntity $lib) {
        $this->libraries[] = $lib;
    }

    /**
     * Returns an array the library names
     *
     * @return MetadataEntity[]
     */
    public function getResults(): array {
        return $this->libraries;
    }
}
