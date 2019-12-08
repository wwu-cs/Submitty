<?php

namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Responses\LibraryGetResponse;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;

class LibraryGetUseCase extends BaseUseCase {
    /** @var MetadataGateway */
    protected $metadata;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->metadata = MetadataGatewayFactory::getInstance();
    }

    /**
     * Gets all libraries and their metadata
     *
     * @return LibraryGetResponse
     */
    public function getLibraries(): LibraryGetResponse {
        $response = new LibraryGetResponse();

        $libraries = $this->metadata->getAll($this->location);

        foreach ($libraries as $library) {
            $response->addLibrary($library);
        }

        return $response;
    }
}
