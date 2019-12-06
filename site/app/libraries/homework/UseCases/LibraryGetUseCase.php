<?php namespace app\libraries\homework\UseCases;


use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Responses\LibraryGetResponse;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;


class LibraryGetUseCase extends BaseUseCase {

    /** @var LibraryGateway */
    protected $gateway;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
    }

    public function getLibraries(): LibraryGetResponse {
        $response = new LibraryGetResponse();

        $libraries = $this->gateway->getAllLibraries($this->location);

        /** @var LibraryEntity $library */
        foreach ($libraries as $library) {
            $response->addLibrary($library->getName());
        }

        return $response;
    }
}
