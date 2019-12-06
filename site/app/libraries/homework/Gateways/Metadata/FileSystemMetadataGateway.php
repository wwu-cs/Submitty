<?php

namespace app\libraries\homework\Gateways\Metadata;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Entities\MetadataGetStatus;
use app\libraries\homework\Entities\MetadataUpdateStatus;

class FileSystemMetadataGateway implements MetadataGateway {
    /** @inheritDoc */
    public function update(MetadataEntity $entity): MetadataUpdateStatus {
        // TODO: Implement update() method.
    }

    /** @inheritDoc */
    public function get(LibraryEntity $entity): MetadataGetStatus {
        // TODO: Implement get() method.
    }

    /** @inheritDoc */
    public function getAll(string $location): array {
        // TODO: Implement getAll() method.
    }

    /** @inheritDoc */
    public function nameExists(string $name): bool {
        // TODO: Implement nameExists() method.
    }

    /** @inheritDoc */
    public function getFromName(string $name): MetadataGetStatus {
        // TODO: Implement getFromName() method.
    }
}
