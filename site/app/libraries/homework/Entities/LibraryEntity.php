<?php

namespace app\libraries\homework\Entities;

use app\libraries\homework\Entities\LibraryUpdateStatus;

class LibraryEntity {

    /** @var string */
    protected $name;

    /** @var string */
    protected $location;

    /**
     * @param string $name
     * @param string $location
     */
    public function __construct(string $name, string $location) {
        $this->name = $name;
        $this->location = rtrim($location, "/ \n\r");
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get the library container location
     *
     * @return string
     */
    public function getLocation(): string {
        return $this->location;
    }

    /**
     * Checks to see if the library has the specified name
     *
     * @param string $name
     * @return bool
     */
    public function hasNameOf(string $name): bool {
        return $this->getName() === $name;
    }

    /**
     * @param string $location
     * @return bool
     */
    public function hasLocationOf(string $location): bool {
        return $this->location === rtrim($location, "/ \n\r");
    }

    /**
     * @param LibraryEntity $library
     * @return bool
     */
    public function is(LibraryEntity $library): bool {
        return $this->hasNameOf($library->getName()) &&
            $this->hasLocationOf($library->getLocation());
    }

    /**
     * @param LibraryEntity $library
     * @return bool
     */
    public function isNot(LibraryEntity $library): bool {
        return !$this->is($library);
    }

    /**
     * Get the actual library named path
     *
     * @return string
     */
    public function getLibraryPath(): string {
        return $this->location . '/' .  $this->name;
    }
}
