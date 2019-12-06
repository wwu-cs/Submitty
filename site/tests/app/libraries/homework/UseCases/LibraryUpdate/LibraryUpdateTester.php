<?php

namespace tests\app\libraries\homework\UseCases\LibraryUpdate;

use DateTime;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use tests\app\libraries\homework\UseCases\BaseTestCase;
use app\libraries\homework\UseCases\LibraryUpdateUseCase;
use app\libraries\homework\Responses\LibraryUpdateResponse;

class LibraryUpdateTester extends BaseTestCase {
    /** @var LibraryUpdateUseCase */
    protected $useCase;

    /** @var LibraryUpdateResponse */
    protected $response;

    public function setUp(): void {
        parent::setUp();

        $this->useCase = new LibraryUpdateUseCase($this->core);
    }

    /** @test */
    public function testUpdateLibraryWithNoPreExistingMetadata() {
        $library = new LibraryEntity('name', $this->location);
        $this->libraryGateway->addLibrary($library);

        $this->handleTest($library->getKey());

        $this->assertEquals('Successfully updated \'name\'', $this->response->getMessage());
        $this->assertTrue($this->response->success);
        /** @var MetadataEntity[] $metadata */
        $metadata = $this->metadataGateway->getAll($this->location);
        $this->assertCount(1, $metadata);
        $metadata = $metadata[0];
        $this->assertEquals('unknown', $metadata->getSourceType());
        $this->assertEquals('name', $metadata->getName());
    }

    /**
     * Execute the test with given parameters and store the result
     *
     * @param $library
     */
    public function handleTest($library) {
        $this->response = $this->useCase->updateLibrary($library);
    }

    /** @test */
    public function testUpdateLibraryWithExistingMetadata() {
        $library = new LibraryEntity('name', $this->location);
        $this->libraryGateway->addLibrary($library);

        $date = new DateTime('2011-01-01T15:03:01.012345Z');

        $original = new MetadataEntity(
            $library,
            'nomen',
            'source',
            0,
            $date,
            $date
        );

        $this->metadataGateway->add($original);

        $this->handleTest($library->getKey());

        $this->assertEquals('Successfully updated \'nomen\'', $this->response->getMessage());
        /** @var MetadataEntity[] $metadata */
        $metadata = $this->metadataGateway->getAll($this->location);
        $this->assertCount(1, $metadata);
        $this->assertNotEquals($metadata[0]->getLastUpdatedDate(), $date);
        $this->assertEquals($date, $metadata[0]->getCreatedDate());
    }

    /** @test */
    public function testUpdateLibraryWithNullName() {
        $this->handleTest(null);

        $this->assertEquals('You must specify the library to update.', $this->response->getMessage());
        $this->assertFalse($this->response->success);
    }

    /** @test */
    public function testUpdateLibraryWithEmptyName() {
        $this->handleTest('');

        $this->assertEquals('You must specify the library to update.', $this->response->getMessage());
        $this->assertFalse($this->response->success);
    }

    /** @test */
    public function testUpdateLibraryThatDoesntExist() {
        $this->handleTest('name');

        $this->assertEquals(
            'There was a problem updating the metadata: Library does not exist.',
            $this->response->getMessage()
        );
        $this->assertFalse($this->response->success);
    }

    /** @test */
    public function testHandleFailingAnUpdate() {
        $library = new LibraryEntity('name', $this->location);
        $this->libraryGateway->addLibrary($library);
        $this->libraryGateway->makeNextAddOrUpdateFailWithMessage('get rekt 173911 times');
        // Has to be git source if we want to trigger update
        $this->metadataGateway->add(
            MetadataEntity::createNewMetadata(
                $library,
                'name',
                'git'
            )
        );
        $this->handleTest('name');

        $this->assertEquals(
            'Could not update library because: get rekt 173911 times',
            $this->response->getMessage()
        );
    }

    /** @test */
    public function testItDoesntUpdateZipLibraries() {
        $library = new LibraryEntity('name', $this->location);

        // Try to make it fail
        $this->libraryGateway->addLibrary($library);
        $this->libraryGateway->makeNextAddOrUpdateFailWithMessage('get rekt 173911 times');

        $this->handleTest('name');

        $this->assertEquals('Successfully updated \'name\'', $this->response->getMessage());
        $this->assertCount(1, $this->metadataGateway->getAll($this->location));
    }

    /** @test */
    public function testHandleFailingToUpdateMetadata() {
        $library = new LibraryEntity('name', $this->location);
        $this->libraryGateway->addLibrary($library);
        $this->metadataGateway->makeNextUpdateFailWithMessage('dank memes can\'t melt steel beams');
        $this->handleTest('name');

        $this->assertEquals(
            'There was a problem updating the metadata: dank memes can\'t melt steel beams',
            $this->response->getMessage()
        );
    }
}
