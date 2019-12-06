<?php namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;

class LibraryUpdateResponse {
    /** @var string */
    public $error;

    protected $message;

    public function __construct(string $message) {
        $this->message = $message;
    }

    /**
     * @param string $message
     * @return static
     */
    public static function error(string $message) {
        $instance = new static('');
        $instance->error = $message;
        return $instance;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }
}


class LibraryUpdateUseCase extends BaseUseCase {

    /** @var LibraryGateway */
    protected $gateway;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
    }

    public function updateLibrary($name): LibraryUpdateResponse {
        if (!$name) {
            return LibraryUpdateResponse::error('You must specify the library to remove.');
        }

        $library = new LibraryEntity($name, $this->location);

        $response = $this->gateway->updateLibrary($library);

        if ($response->success) {
            return new LibraryUpdateResponse($response->message);
        }

        return LibraryUpdateResponse::error($response->message);
    }

}
