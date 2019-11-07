<?php namespace app\libraries\homework\UseCases;


use app\libraries\Core;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\LibraryGatewayFactory;


class LibraryGetResponse {
    /** @var string[] */
    protected $libraries = [];

    public function addLibrary(string $library) {
        $this->libraries[] = $library;
    }

    /**
     * @return string[]
     */
    public function getLibraries(): array {
        return $this->libraries;
    }

}

class LibraryGetUseCase extends BaseUseCase {

    /** @var LibraryGateway */
    protected $gateway;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
    }

    /**
     * Get all libraries in the homework config location
     *
     * @return LibraryGetResponse
     */
    public function getAllLibraries(): LibraryGetResponse {
        $libraries = $this->gateway->getAllLibraries($this->location);
        $response = new LibraryGetResponse();

        foreach ($libraries as $library) {
            $response->addLibrary($library);
        }

        return $response;
    }
}
