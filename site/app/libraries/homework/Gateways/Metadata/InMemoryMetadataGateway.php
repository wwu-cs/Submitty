<?php namespace app\libraries\homework\Gateways\Metadata;


use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;

class InMemoryMetadataGateway implements MetadataGateway {
    /** @var MetadataEntity[] */
    protected $metadata = [];

    /** @var MetadataEntity[] */
    protected $updateQueue = [];

    /** @var LibraryGateway */
    protected $libraryGateway;

;

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
    public function update(LibraryEntity $entity): MetadataEntity {
        $meta = array_pop($this->updateQueue);
        $this->metadata[] = $meta;
        return $meta;
    }

    /** @inheritDoc */
    public function get(LibraryEntity $entity) {

        foreach ($this->metadata as $meta) {
            if ($meta->getLibrary()->is($entity)) {
                return $meta;
            }
        }
        return null;
    }

    /** @inheritDoc */
    public function getAll(): array {
        return $this->metadata;
    }
}
