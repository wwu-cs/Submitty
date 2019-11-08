<?php namespace app\libraries\homework\Entities;


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
        $this->location = $location;
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
     * @param string $location
     * @return bool
     */
    public function hasLocationOf(string $location): bool {
        return $this->location == $location;
    }

    /**
     * @param LibraryEntity $library
     * @return bool
     */
    public function is(LibraryEntity $library): bool {
        return $library->getName() == $this->getName() &&
            $library->getLocation() == $this->getLocation();
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
