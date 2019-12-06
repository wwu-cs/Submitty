<?php namespace app\libraries\homework\Gateways\Metadata;


use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\MetadataGateway;

class FileSystemMetadataGateway implements MetadataGateway {
    /** @inheritDoc */
    public function update(LibraryEntity $entity): MetadataEntity {
        // TODO: Implement update() method.
    }

    /** @inheritDoc */
    public function get(LibraryEntity $entity) {
        // TODO: Implement get() method.
    }

    /** @inheritDoc */
    public function getAll(): array {
        // TODO: Implement getAll() method.
    }
}
