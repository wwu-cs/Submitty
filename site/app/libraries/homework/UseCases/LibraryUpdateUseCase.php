<?php

namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Responses\LibraryUpdateResponse;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;


class LibraryUpdateUseCase extends BaseUseCase {

    /** @var LibraryGateway */
    protected $gateway;

    /** @var MetadataGateway */
    protected $metadata;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
        $this->metadata = MetadataGatewayFactory::getInstance();
    }

    public function updateLibrary($name): LibraryUpdateResponse {
        if (!$name) {
            return LibraryUpdateResponse::error('You must specify the library to remove.');
        }

        // Construct library representation
        $library = new LibraryEntity($name, $this->location);

        // Update the library
        $response = $this->gateway->updateLibrary($library);

        if (!$response->success) {
            return LibraryUpdateResponse::error($response->message);
        }

        // Update metadata
        $metadataStatus = $this->metadata->update($library);

        if ($metadataStatus->error) {
            return LibraryUpdateResponse::error($metadataStatus->error);
        }

        return LibraryUpdateResponse::success($response->message);
    }
}
