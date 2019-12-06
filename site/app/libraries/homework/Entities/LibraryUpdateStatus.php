<?php

namespace app\libraries\homework\Entities;

class LibraryUpdateStatus {
    /** @var string */
    public $message;

    /** @var bool */
    public $success;

    /**
     * @param string $error
     * @return LibraryUpdateStatus
     */
    public static function error(string $error): LibraryUpdateStatus {
        return new static(false, $error);
    }

    /**
     * @param string $message
     * @return LibraryUpdateStatus
     */
    public static function success(string $message): LibraryUpdateStatus {
        return new static(true, $message);
    }

    /**
     * @param bool $success
     * @param string $message
     */
    public function __construct(bool $success, string $message) {
        $this->success = $success;
        $this->message = $message;
    }
}
