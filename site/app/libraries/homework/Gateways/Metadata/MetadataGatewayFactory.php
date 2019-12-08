<?php

namespace app\libraries\homework\Gateways\Metadata;

use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Library\FileSystemLibraryGateway;

class MetadataGatewayFactory {
    /** @var MetadataGateway */
    protected static $instance;

    /**
     * Lazy load the library singleton
     *
     * @return MetadataGateway
     */
    public static function getInstance(): MetadataGateway {
        if (!static::$instance) {
            $libraryGateway = new FileSystemLibraryGateway();
            LibraryGatewayFactory::setInstance($libraryGateway);
            static::$instance = new FileSystemMetadataGateway($libraryGateway);
        }

        return static::$instance;
    }

    /**
     * Set the MetadataGateway singleton
     *
     * @param MetadataGateway $gateway
     */
    public static function setInstance(MetadataGateway $gateway) {
        static::$instance = $gateway;
    }

    /**
     * Clear the MetadataGateway singleton
     */
    public static function clearInstance() {
        static::$instance = null;
    }
}
