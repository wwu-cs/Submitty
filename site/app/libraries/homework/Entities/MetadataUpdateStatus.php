<?php

namespace app\libraries\homework\Entities;

class MetadataUpdateStatus {
    /** @var MetadataEntity */
    public $result;

    /** @var string */
    public $error;

    /**
     * @param string         $error
     * @param MetadataEntity $entity
     */
    protected function __construct(string $error, MetadataEntity $entity = null) {
        $this->error = $error;
        $this->result = $entity;
    }

    /**
     * @param string $error
     * @return MetadataUpdateStatus
     */
    public static function error(string $error): MetadataUpdateStatus {
        return new static($error, null);
    }

    /**
     * @param MetadataEntity $entity
     * @return MetadataUpdateStatus
     */
    public static function success(MetadataEntity $entity): MetadataUpdateStatus {
        return new static('', $entity);
    }
}
