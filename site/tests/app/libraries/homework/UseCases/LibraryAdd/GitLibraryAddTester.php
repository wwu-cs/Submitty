<?php

namespace tests\app\libraries\homework\UseCases\LibraryAdd;

use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\UseCases\LibraryAddUseCase;
use tests\app\libraries\homework\UseCases\BaseTestCase;
use app\libraries\homework\Responses\LibraryAddResponse;

class GitLibraryAddTester extends BaseTestCase {
    const REPO_URL = 'git@github.com:user/lib.git';

    /** @var LibraryAddUseCase */
    protected $useCase;

    /** @var LibraryAddResponse */
    protected $response;

    public function setUp(): void {
        parent::setUp();

        $this->useCase = new LibraryAddUseCase($this->core);
    }

    /** @test */
    public function testItDoesNotAddNullGitUrl() {
        $this->handleTest(null, 'name');

        $this->assertEquals('A repo url is required.', $this->response->error);
    }

    /**
     * Execute the test with the given parameters
     *
     * @param $gitUrl
     * @param $name
     */
    protected function handleTest($gitUrl, $name) {
        $this->response = $this->useCase->addGitLibrary($gitUrl, $name);
    }

    /** @test */
    public function testItDoesNotAddEmptyGitUrl() {
        $this->handleTest('', 'name');

        $this->assertEquals('A repo url is required.', $this->response->error);
    }

    /** @test */
    public function testItDoesNotAddInvalidGitUrl() {
        $this->handleTest('not an actual git url lol', 'name');

        $this->assertEquals(
            'The git url is not of the right format.',
            $this->response->error
        );
    }

    /** @test */
    public function testCreatesAValidLibraryWithName() {
        $this->handleTest(self::REPO_URL, 'steve');

        /** @var MetadataEntity[] $metadata */
        $metadata = $this->metadataGateway->getAll($this->location);

        $this->assertEquals('Successfully cloned git@github.com:user/lib.git.', $this->response->getMessage());
        $this->assertCount(1, $metadata);

        $this->assertTrue(
            $this->libraryGateway->libraryExists(
                new LibraryEntity(
                    $metadata[0]->getLibrary()->getKey(),
                    $this->location
                )
            )
        );
        $this->assertEquals('steve', $metadata[0]->getName());
        $this->assertEquals('git', $metadata[0]->getSourceType());
    }

    /** @test */
    public function testItDefaultsNameToGitRepoName() {
        $this->handleTest(self::REPO_URL, null);

        /** @var MetadataEntity[] $metadata */
        $metadata = $this->metadataGateway->getAll($this->location);
        $this->assertCount(1, $metadata);

        $metadata = $metadata[0];
        $this->assertEquals('lib', $metadata->getName());
    }

    /** @test */
    public function testItHandlesFailingAClone() {
        $this->libraryGateway->makeNextAddOrUpdateFailWithMessage('rip in pieces');
        $this->handleTest(self::REPO_URL, null);

        $this->assertEquals('Error adding the library. rip in pieces', $this->response->error);
    }

    public function testItHandlesFailingUpdatingMetadata() {
        $this->metadataGateway->makeNextUpdateFailWithMessage('ha you messed up. get rekt');
        $this->handleTest(self::REPO_URL, null);

        $this->assertEquals(
            'Library was cloned, however the metadata was not able to be created because: ' .
            'ha you messed up. get rekt',
            $this->response->error
        );
        // See if it cleaned up after itself
        $this->assertCount(0, $this->libraryGateway->getAllLibraries($this->location));
    }
}
