<?php namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;

class LibraryRemoveUseCase extends BaseUseCase {
    /** @var LibraryGateway */
    protected $gateway;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
    }

    /**
     * Removes a library from the library repository by name
     *
     * @param string|null $name
     * @return \app\libraries\homework\Responses\LibraryRemoveResponse
     */
    public function removeLibrary($name): \app\libraries\homework\Responses\LibraryRemoveResponse {
        if (!$name) {
            return \app\libraries\homework\Responses\LibraryRemoveResponse::error('You must specify the library to remove.');
        }

        $library = new LibraryEntity($name, $this->location);

        if ($this->gateway->removeLibrary($library)) {
            return new \app\libraries\homework\Responses\LibraryRemoveResponse("Successfully removed library '$name'");
        }

        return \app\libraries\homework\Responses\LibraryRemoveResponse::error("Error when removing library '$name'");
    }

}
