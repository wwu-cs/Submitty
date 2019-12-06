<?php namespace tests\app\libraries\homework\UseCases;

use app\libraries\Core;
use tests\BaseUnitTest;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Library\InMemoryLibraryGateway;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;
use app\libraries\homework\Gateways\Metadata\InMemoryMetadataGateway;

class BaseTestCase extends BaseUnitTest {
    /** @var InMemoryLibraryGateway */
    protected $libraryGateway;

    /** @var InMemoryMetadataGateway */
    protected $metadataGateway;

    /** @var Core */
    protected $core;

    /** @var string */
    protected $location;

    public function setUp(): void {
        parent::setUp();

        $this->location = 'library location';

        $this->core = $this->createMockCore(
            [
                'homework_library_enable'   => true,
                'homework_library_location' => $this->location,
            ]
        );

        $this->libraryGateway = new InMemoryLibraryGateway();
        $this->metadataGateway = new InMemoryMetadataGateway();
        LibraryGatewayFactory::setInstance($this->libraryGateway);
        MetadataGatewayFactory::setInstance($this->metadataGateway);
    }
}
