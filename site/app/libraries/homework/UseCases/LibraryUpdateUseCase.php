<?php namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;


class LibraryUpdateUseCase extends BaseUseCase {

    /** @var LibraryGateway */
    protected $gateway;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
    }

    public function updateLibrary($name): \app\libraries\homework\Responses\LibraryUpdateResponse {
        if (!$name) {
            return \app\libraries\homework\Responses\LibraryUpdateResponse::error('You must specify the library to remove.');
        }

        $library = new LibraryEntity($name, $this->location);

        $response = $this->gateway->updateLibrary($library);

        if ($response->success) {
            return new \app\libraries\homework\Responses\LibraryUpdateResponse($response->message);
        }

        return \app\libraries\homework\Responses\LibraryUpdateResponse::error($response->message);
    }

}
