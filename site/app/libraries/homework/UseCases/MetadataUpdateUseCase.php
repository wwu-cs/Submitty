<?php

namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Responses\MetadataUpdateResponse;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;

class MetadataUpdateUseCase extends BaseUseCase {
    /** @var MetadataGateway */
    protected $metadata;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->metadata = MetadataGatewayFactory::getInstance();
    }

    /**
     * Will update library metadata. If no metadata currently exists, it will create it.
     *
     * @param LibraryEntity $libraryEntity
     * @return MetadataUpdateResponse
     */
    public function updateMetadataFor(LibraryEntity $libraryEntity): MetadataUpdateResponse {
        $meta = $this->metadata->update($libraryEntity);

        if ($meta->error) {
            return MetadataUpdateResponse::error($meta->error);
        }

        return MetadataUpdateResponse::success('Updated library metadata.', $meta->result);
    }
}
