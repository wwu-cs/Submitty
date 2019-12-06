<?php

namespace app\libraries\homework\Responses;

class LibraryAddResponse {
    /** @var string */
    public $error;

    /** @var string */
    protected $message;

    public function __construct(string $message = '') {
        $this->message = $message;
    }

    public static function error(string $message): LibraryAddResponse {
        $response = new static;
        $response->error = $message;
        return $response;
    }

    public function getMessage(): string {
        return $this->message;
    }
}
