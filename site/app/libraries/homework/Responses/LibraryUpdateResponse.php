<?php

namespace app\libraries\homework\Responses;

class LibraryUpdateResponse {
    /** @var string */
    public $error;

    protected $message;

    public function __construct(string $message) {
        $this->message = $message;
    }

    /**
     * @param string $message
     * @return static
     */
    public static function error(string $message) {
        $instance = new static('');
        $instance->error = $message;
        return $instance;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }
}
