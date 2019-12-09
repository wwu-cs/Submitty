<?php

namespace app\libraries\homework\UseCases;

class LibraryAddResponse {
    /** @var string */
    protected $message;

    /** @var string */
    public $error;

    public function __construct(string $message = '') {
        $this->message = $message;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public static function error(string $message): LibraryAddResponse {
        $response = new static();
        $response->error = $message;
        return $response;
    }
}
