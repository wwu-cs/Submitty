<?php

namespace app\libraries\homework\Responses;

class LibraryUpdateResponse {
    /** @var bool */
    public $success;

    /** @var string */
    protected $message;

    protected function __construct(string $message, bool $success) {
        $this->message = $message;
        $this->success = $success;
    }

    /**
     * @param string $message
     * @return static
     */
    public static function error(string $message) {
        return new static($message, false);
    }

    public static function success(string $message) {
        return new static($message, true);
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }
}
