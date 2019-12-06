<?php

namespace app\libraries\homework\Gateways\Metadata;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Entities\MetadataGetStatus;
use app\libraries\homework\Entities\MetadataUpdateStatus;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;

class InMemoryMetadataGateway implements MetadataGateway {
    /** @var MetadataEntity[] */
    protected $metadata = [];

    /** @var LibraryGateway */
    protected $libraryGateway;

    public function __construct() {
        $this->libraryGateway = LibraryGatewayFactory::getInstance();
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
