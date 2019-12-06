<?php

namespace app\libraries\homework\Responses;

use app\libraries\homework\Entities\MetadataEntity;

class MetadataGetResponse {
    /** @var string */
    public $error;

    /** @var MetadataEntity|null */
    protected $metadata;

    /**
     * @param MetadataEntity|null $meta
     */
    protected function __construct($meta) {
        $this->metadata = $meta;
    }

    public static function error(string $message): MetadataGetResponse {
        $response = new static(null);
        $response->error = $message;
        return $response;
    }

    public static function success(MetadataEntity $metadata): MetadataGetResponse {
        return new static($metadata);
    }

    /**
     * @return MetadataEntity|null
     */
    public function getMetadata() {
        return $this->metadata;
    }
}
