<?php

namespace app\libraries\homework\Responses;

use app\libraries\homework\Entities\MetadataEntity;

class MetadataUpdateResponse {
    /** @var string */
    public $error;

    /** @var string */
    protected $message;

    /** @var MetadataEntity|null */
    protected $metadata;

    /**
     * MetadataUpdateResponse constructor.
     *
     * @param string              $message
     * @param MetadataEntity|null $meta
     */
    protected function __construct(string $message, $meta) {
        $this->message = $message;
        $this->metadata = $meta;
    }

    public static function error(string $message): MetadataUpdateResponse {
        $response = new static('', null);
        $response->error = $message;
        return $response;
    }

    public static function success(string $message, MetadataEntity $metadata): MetadataUpdateResponse {
        return new static($message, $metadata);
    }

    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @return MetadataEntity|null
     */
    public function getMetadata() {
        return $this->metadata;
    }
}
