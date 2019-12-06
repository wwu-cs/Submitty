<?php namespace app\libraries\homework\UseCases;


use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;

class MetadataUpdateUseCase extends BaseUseCase {
    /** @var LibraryGateway */
    protected $libraries;

    /** @var MetadataGateway */
    protected $metadata;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->libraries = LibraryGatewayFactory::getInstance();
        $this->metadata = MetadataGatewayFactory::getInstance();
    }

    /**
     * Will update library metadata. If no metadata currently exists, it will create it.
     *
     * @param LibraryEntity $libraryEntity
     * @return \app\libraries\homework\Responses\MetadataUpdateResponse
     */
    public function updateMetadataFor(LibraryEntity $libraryEntity): \app\libraries\homework\Responses\MetadataUpdateResponse {
        if (!$this->libraries->libraryExists($libraryEntity)) {
            return \app\libraries\homework\Responses\MetadataUpdateResponse::error('Library does not exist.');
        }

        $meta = $this->metadata->update($libraryEntity);
        return \app\libraries\homework\Responses\MetadataUpdateResponse::success('Updated library metadata.', $meta);
    }
}
