<?php

namespace tests\app\libraries\homework\UseCases\LibraryAdd;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\UseCases\LibraryAddUseCase;
use tests\app\libraries\homework\UseCases\BaseTestCase;
use app\libraries\homework\Responses\LibraryAddResponse;

class ZipLibraryAddTester extends BaseTestCase {
    /** @var LibraryAddUseCase */
    protected $useCase;

    /** @var LibraryAddResponse */
    protected $response;

    public function setUp(): void {
        parent::setUp();

        $this->useCase = new LibraryAddUseCase($this->core);
    }

    /** @test */
    public function testItDoesNotAddNullFilePath() {
        $this->handleTest(null, null);

        $this->assertEquals('A file must be provided.', $this->response->error);
    }

    /**
     * Execute the test with the given parameters
     *
     * @param $zipFile
     * @param $name
     */
    protected function handleTest($zipFile, $name) {
        $this->response = $this->useCase->addZipLibrary($zipFile, $name);
    }

    /** @test */
    public function testItDoesNotAddEmptyFilePath() {
        $this->handleTest([], null);

        $this->assertEquals('A file must be provided.', $this->response->error);
    }

    /** @test */
    public function testItRequiresNameAttribute() {
        $this->handleTest(
            [
                'tmp_name' => 'Thanos did nothing wrong',
            ],
            null
        );

        $this->assertEquals('A file must be provided.', $this->response->error);
    }

    /** @test */
    public function testItRequiresTmpNameAttribute() {
        $this->handleTest(
            [
                'name' => 'I didn\'nt save work and lost a bunch of stuff here :( So now I\'m having to rewrite it.',
            ],
            null
        );

        $this->assertEquals('A file must be provided.', $this->response->error);
    }

    /** @test */
    public function testItDoesNotAddNonZipFiles() {
        $this->handleTest(
            [
                'name'     => 'definitely not a zip file.avi',
                'tmp_name' => 'save your work boys...',
            ],
            null
        );

        $this->assertEquals('A .zip file must be provided.', $this->response->error);
    }

    /** @test */
    public function testItAddsValidZip() {
        $this->handleTest(
            [
                'name'     => 'lib.zip',
                'tmp_name' => '171926 ;)',
            ],
            'ireeen man dies in infinity war'
        );

        /** @var MetadataEntity[] $metadata */
        $metadata = $this->metadataGateway->getAll($this->location);

        $this->assertEquals(
            'Successfully installed new library: ireeen man dies in infinity war',
            $this->response->getMessage()
        );
        $this->assertCount(1, $metadata);

        $this->assertTrue(
            $this->libraryGateway->libraryExists(
                new LibraryEntity(
                    $metadata[0]->getLibrary()->getKey(),
                    $this->location
                )
            )
        );
        $this->assertEquals('ireeen man dies in infinity war', $metadata[0]->getName());
        $this->assertEquals('zip', $metadata[0]->getSourceType());
    }

    /** @test */
    public function testItHandlesZipLibraryFailingToBeAdded() {
        $this->libraryGateway->makeNextAddOrUpdateFailWithMessage('rip in pieces');
        $this->handleTest(
            [
                'name'     => 'my first zip file.zip',
                'tmp_name' => 'what am I doing with me life? idk',
            ],
            null
        );

        $this->assertEquals('Error adding the library. rip in pieces', $this->response->error);
    }

    /** @test */
    public function testItHandlesFailingUpdatingOfMetadata() {
        $this->metadataGateway->makeNextUpdateFailWithMessage('ha you messed up. get rekt');
        $this->handleTest(
            [
                'name'     => 'my nintey ninth zip file.zip',
                'tmp_name' => '279444 ;0',
            ],
            null
        );

        $this->assertEquals(
            'Library was created, however the metadata was not able to be created because: ' .
            'ha you messed up. get rekt',
            $this->response->error
        );
        // See if it cleaned up after itself
        $this->assertCount(0, $this->libraryGateway->getAllLibraries($this->location));
    }
}
