<?php namespace tests\app\libraries\homework\UseCases\LibraryUpdate;


use app\libraries\homework\Entities\LibraryEntity;
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
    public function testUpdateLibrary() {
        $library = new LibraryEntity('name', $this->location);
        $this->libraryGateway->addLibrary($library);

        $this->handleTest($library->getName());

        $this->assertEquals('Successfully updated \'name\'', $this->response->getMessage());
    }

    public function handleTest($library) {
        $this->response = $this->useCase->updateLibrary($library);
    }

    /** @test */
    public function testUpdateLibraryWithNullName() {
        $this->handleTest(null);

        $this->assertEquals('You must specify the library to remove.', $this->response->error);
    }

    /** @test */
    public function testUpdateLibraryWithEmptyName() {
        $this->handleTest('');

        $this->assertEquals('You must specify the library to remove.', $this->response->error);
    }

    /** @test */
    public function testUpdateLibraryThatDoesntExist() {
        $this->handleTest('name');

        $this->assertEquals('Library does not exist.', $this->response->error);
    }
}
