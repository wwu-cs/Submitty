<?php

namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Entities\MetadataEntity;
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
            return LibraryUpdateResponse::error('You must specify the library to update.');
        }

        // Construct library representation
        $library = new LibraryEntity($name, $this->location);

        // Update the library
        $response = $this->gateway->updateLibrary($library);

        if (!$response->success) {
            return LibraryUpdateResponse::error('Could not update library because: ' . $response->message);
        }

        // Update metadata
        $currentMetadata = $this->metadata->get($library);

        if (!$currentMetadata->error) {
            $metadataStatus = $this->metadata->update(
                $currentMetadata->result->touch()
            );
        }
        else {
            // If we get here, a library was probably somehow added without metadata
            $metadataStatus = $this->metadata->update(
                MetadataEntity::createNewMetadata(
                    $library,
                    $library->getKey(),
                    'unknown'
                )
            );
        }

        if ($metadataStatus->error) {
            return LibraryUpdateResponse::error('There was a problem updating the metadata: ' . $metadataStatus->error);
        }

        return LibraryUpdateResponse::success("Successfully updated '{$metadataStatus->result->getName()}'");
    }
}
