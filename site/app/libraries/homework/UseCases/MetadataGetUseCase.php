<?php

namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Responses\MetadataGetResponse;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;

class MetadataGetUseCase extends BaseUseCase {
    /** @var MetadataGateway */
    protected $metadata;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->metadata = MetadataGatewayFactory::getInstance();
    }

    /**
     * @param LibraryEntity $library
     * @return MetadataGetResponse
     */
    public function getMetadataFor(LibraryEntity $library): MetadataGetResponse {
        $meta = $this->metadata->get($library);

        if ($meta->error) {
            return MetadataGetResponse::error($meta->error);
        }

        return MetadataGetResponse::success($meta->result);
    }
}
