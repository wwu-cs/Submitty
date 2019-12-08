<?php

namespace app\libraries\homework\Gateways\Metadata;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Entities\MetadataGetStatus;
use app\libraries\homework\Entities\MetadataUpdateStatus;

class InMemoryMetadataGateway implements MetadataGateway {
    /** @var MetadataEntity[] */
    protected $metadata = [];

    /** @var LibraryGateway */
    protected $libraryGateway;


    /** @var string[] */
    protected $failMessageQueue;

    /**
     * @param LibraryGateway $libraryGateway
     */
    public function __construct(LibraryGateway $libraryGateway) {
        $this->libraryGateway = $libraryGateway;
    }

    /**
     * For when you need a method to fail for testing
     *
     * @param string $message
     */
    public function makeNextUpdateFailWithMessage(string $message) {
        $this->failMessageQueue[] = $message;
    }

    /**
     * Adds a set of metadata to the local repository
     *
     * @param MetadataEntity $metadata
     */
    public function add(MetadataEntity $metadata) {
        $this->metadata[] = $metadata;
    }

    /** @inheritDoc */
    public function update(MetadataEntity $entity): MetadataUpdateStatus {
        if (!empty($this->failMessageQueue)) {
            return MetadataUpdateStatus::error(array_pop($this->failMessageQueue));
        }

        if (!$this->libraryGateway->libraryExists($entity->getLibrary())) {
            return MetadataUpdateStatus::error('Library does not exist.');
        }

        $this->metadata = array_filter(
            $this->metadata,
            function (MetadataEntity $mEntity) use ($entity) {
                return $mEntity->getLibrary()->isNot($entity->getLibrary());
            }
        );

        $this->metadata[] = $entity;

        return MetadataUpdateStatus::success($entity);
    }

    /** @inheritDoc */
    public function get(LibraryEntity $entity): MetadataGetStatus {
        if (!$this->libraryGateway->libraryExists($entity)) {
            return MetadataGetStatus::error('Library does not exist.');
        }

        foreach ($this->metadata as $meta) {
            if ($meta->getLibrary()->is($entity)) {
                return MetadataGetStatus::success($meta);
            }
        }
        return MetadataGetStatus::error('Could not find library metadata.');
    }

    /** @inheritDoc */
    public function getAll(string $location): array {
        return array_filter(
            $this->metadata,
            function (MetadataEntity $entity) use ($location) {
                return $entity->getLibrary()->hasLocationOf($location);
            }
        );
    }
}
