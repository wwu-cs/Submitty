<?php namespace app\libraries\homework\Gateways;


use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;

interface MetadataGateway {
    /**
     * Update or set metadata for a library
     *
     * @param LibraryEntity $entity
     * @return MetadataEntity
     */
    public function update(LibraryEntity $entity): MetadataEntity;

    /**
     * Get metadata for a library
     *
     * @param LibraryEntity $entity
     * @return MetadataEntity|null
     */
    public function get(LibraryEntity $entity);

    /**
     * Get all libraries and their metadata.
     *
     * @return MetadataEntity[]
     */
    public function getAll(): array;
}
