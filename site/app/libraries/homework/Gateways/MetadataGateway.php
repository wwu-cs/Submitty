<?php namespace app\libraries\homework\Gateways;


use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Entities\MetadataGetStatus;
use app\libraries\homework\Entities\MetadataUpdateStatus;

interface MetadataGateway {
    /**
     * Update or set metadata for a library
     *
     * @param LibraryEntity $entity
     * @return MetadataUpdateStatus
     */
    public function update(LibraryEntity $entity): MetadataUpdateStatus;

    /**
     * Get metadata for a library
     *
     * @param LibraryEntity $entity
     * @return MetadataGetStatus
     */
    public function get(LibraryEntity $entity): MetadataGetStatus;

    /**
     * Get all libraries and their metadata.
     *
     * @param string $location
     * @return MetadataEntity[]
     */
    public function getAll(string $location): array;
}
