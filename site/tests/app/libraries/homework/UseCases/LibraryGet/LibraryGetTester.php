<?php

namespace tests\app\libraries\homework\UseCases\LibraryGet;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\UseCases\LibraryGetUseCase;
use tests\app\libraries\homework\UseCases\BaseTestCase;
use app\libraries\homework\Responses\LibraryGetResponse;

class LibraryGetTester extends BaseTestCase {

    /** @var LibraryGetUseCase */
    protected $useCase;

    /** @var LibraryGetResponse */
    protected $response;

    public function setUp(): void {
        parent::setUp();

        $this->useCase = new LibraryGetUseCase($this->core);
    }

    /** @test */
    public function testItShouldReturnEmpty() {
        $this->handleTest();

        $this->assertEquals([], $this->response->getResults());
    }

    protected function handleTest() {
        $this->response = $this->useCase->getLibraries();
    }

    /** @test */
    public function testItShouldReturnResults() {
        $metadata = [
            MetadataEntity::createNewMetadata(
                new LibraryEntity('name', $this->location),
                'name',
                'source'
            ),
            MetadataEntity::createNewMetadata(
                new LibraryEntity('name2', $this->location),
                'name2',
                'source'
            ),
        ];

        foreach ($metadata as $metadatum) {
            $this->metadataGateway->add($metadatum);
        }

        $this->handleTest();

        $this->assertEquals(
            $metadata,
            $this->response->getResults()
        );
    }
}
