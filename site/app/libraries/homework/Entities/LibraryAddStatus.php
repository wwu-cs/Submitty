<?php

namespace app\libraries\homework\Entities;

use app\libraries\homework\Entities\LibraryEntity;

class LibraryAddStatus {
    const SUCCESS = 'success';

    /** @var LibraryEntity|null */
    public $library;

    /** @var string */
    public $message;

    /**
     * @param string $error
     * @return LibraryAddStatus
     */
    public static function error(string $error): LibraryAddStatus {
        return new static(null, $error);
    }

    /**
     * @param LibraryEntity $library
     * @return LibraryAddStatus
     */
    public static function success(LibraryEntity $library): LibraryAddStatus {
        return new static($library, self::SUCCESS);
    }

    /**
     * @param LibraryEntity|null $library
     * @param string $message
     */
    public function __construct($library, string $message) {
        $this->library = $library;
        $this->message = $message;
    }
}
