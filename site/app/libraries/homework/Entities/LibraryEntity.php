<?php namespace app\libraries\homework\Entities;

class LibraryEntity {

    /** @var string */
    protected $key;

    /** @var string */
    protected $location;

    /**
     * @param string $key
     * @param string $location
     */
    public function __construct(string $key, string $location) {
        $this->key = $key;
        $this->location = rtrim($location, "/ \n\r");
    }

    /**
     * @param LibraryEntity $library
     * @return bool
     */
    public function isNot(LibraryEntity $library): bool {
        return !$this->is($library);
    }

    /**
     * @param LibraryEntity $library
     * @return bool
     */
    public function is(LibraryEntity $library): bool {
        return $this->hasNameOf($library->getKey()) &&
               $this->hasLocationOf($library->getLocation());
    }

    /**
     * Checks to see if the library has the specified name
     *
     * @param string $name
     * @return bool
     */
    public function hasNameOf(string $name): bool {
        return $this->getKey() === $name;
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @param string $location
     * @return bool
     */
    public function hasLocationOf(string $location): bool {
        return $this->location === rtrim($location, "/ \n\r");
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
     * Get the actual library named path
     *
     * @return string
     */
    public function getLibraryPath(): string {
        return $this->location . '/' . $this->key;
    }
}
