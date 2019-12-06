<?php namespace tests\app\libraries\homework\Gateways\Library;

use ZipArchive;
use app\libraries\FileUtils;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\LibraryAddStatus;
use tests\app\libraries\homework\Gateways\BaseTestCase;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Library\FileSystemLibraryGateway;

class FileSystemLibraryGatewayTester extends BaseTestCase {
    const VALID_GIT_URL = "https://github.com/Submitty/Submitty.git";

    /** @var FileSystemLibraryGateway */
    protected $gateway;

    public function setUp(): void {
        parent::setUp();

        $this->gateway = new FileSystemLibraryGateway();
    }

    /** @test */
    public function testItClonesAGitRepository() {
        $library = new LibraryEntity('Submitty', $this->location);
        $return = $this->gateway->addGitLibrary($library, self::VALID_GIT_URL);

        $this->assertLibraryAddStatusSuccess($return, $library);
        $this->assertDirectoryExists($library->getLibraryPath());
        $this->assertDirectoryExists(FileUtils::joinPaths($library->getLibraryPath(), '.git'));
    }

    /**
     * @param LibraryAddStatus $status
     * @param LibraryEntity    $library
     */
    protected function assertLibraryAddStatusSuccess(LibraryAddStatus $status, LibraryEntity $library) {
        $this->assertNotNull($status->library);
        $this->assertTrue($library->is($status->library));
        $this->assertEquals(LibraryAddStatus::SUCCESS, $status->message);
    }

    /** @test */
    public function testItFailsCloningABadGitRepository() {
        $library = new LibraryEntity('name', $this->location);

        $return = $this->gateway->addGitLibrary($library, 'invalid url');

        $this->assertLibraryAddStatusError(
            $return,
            'Error cloning repository. fatal: repository \'invalid url\' does not exist'
        );
        $this->assertDirectoryNotExists($library->getLibraryPath());
    }

    /**
     * @param LibraryAddStatus $status
     * @param string           $message
     */
    protected function assertLibraryAddStatusError(LibraryAddStatus $status, string $message) {
        $this->assertNull($status->library);

        $this->assertEquals($message, $status->message);
    }

    /** @test */
    public function testItDoesNotAddAnInvalidZipFile() {
        $library = new LibraryEntity('name', $this->location);
        $return = $this->gateway->addZipLibrary($library, 'invalid zip');

        $this->assertLibraryAddStatusError($return, 'Error opening zip file.');
        $this->assertDirectoryNotExists($library->getLibraryPath());
    }

    /** @test */
    public function testItAddsAZipFile() {
        $zip = $this->createTestZip('test.zip');

        $library = new LibraryEntity('test', $this->location);

        $return = $this->gateway->addZipLibrary($library, $zip);
        $this->assertLibraryAddStatusSuccess($return, $library);
        $this->assertDirectoryExists($library->getLibraryPath());
        $this->assertFileExists($library->getLibraryPath() . '/test.txt');
    }

    protected function createTestZip(string $zipName): string {
        $zip = new ZipArchive();

        $f = FileUtils::joinPaths($this->location, $zipName);

        $res = $zip->open($f, ZipArchive::CREATE);
        $this->assertTrue($res);
        $zip->addFromString('test.txt', 'File content. Hurray.');
        $zip->close();
        return $f;
    }

    /** @test */
    public function testItRetrievesAllLibrariesWhenEmpty() {
        $results = $this->gateway->getAllLibraries($this->location);

        $this->assertEquals([], $results);
    }

    /** @test */
    public function testItRetrievesAllLibraries() {
        $this->createLibraryWithName('lib1');
        $this->createLibraryWithName('lib2');
        $this->createLibraryWithName('lib3');

        /** @var LibraryEntity[] $results */
        $results = $this->gateway->getAllLibraries($this->location);

        $this->assertCount(3, $results);
        $this->assertEquals('lib1', $results[0]->getName());
        $this->assertEquals('lib2', $results[1]->getName());
        $this->assertEquals('lib3', $results[2]->getName());
    }

    protected function createLibraryWithName(string $name) {
        FileUtils::createDir(FileUtils::joinPaths($this->location, $name));
    }

    /** @test */
    public function testItDoesNotOverwriteLibrariesZip() {
        $library = new LibraryEntity('name', $this->location);
        $this->createLibrary($library);

        $status = $this->gateway->addZipLibrary($library, 'invalid zip');

        $this->assertLibraryAddStatusError($status, 'Library already exists.');
    }

    protected function createLibrary(LibraryEntity $library) {
        $this->createLibraryWithName($library->getName());
    }

    /** @test */
    public function testItDoesNotOverwriteLibrariesGit() {
        $library = new LibraryEntity('name', $this->location);
        $this->createLibrary($library);

        $status = $this->gateway->addGitLibrary($library, 'url');

        $this->assertLibraryAddStatusError($status, 'Library already exists.');
    }

    /** @test */
    public function testItRemovesLibraries() {
        $library = new LibraryEntity('name', $this->location);
        $this->createLibrary($library);

        $this->assertDirectoryExists($library->getLibraryPath());
        $this->assertTrue($this->gateway->removeLibrary($library));
        $this->assertDirectoryNotExists($library->getLibraryPath());
    }

    /** @test */
    public function testItRemovesNonExistentLibraries() {
        $library = new LibraryEntity('name', $this->location);

        $this->assertTrue($this->gateway->removeLibrary($library));
        $this->assertDirectoryNotExists($library->getLibraryPath());
    }

    /** @test */
    public function testTheDefaultGatewayIsFileSystemGateway() {
        LibraryGatewayFactory::clearInstance();
        $instance = LibraryGatewayFactory::getInstance();
        $this->assertInstanceOf(FileSystemLibraryGateway::class, $instance);
    }

    /** @test */
    public function testItUpdatesGitLibraries() {
        $library = new LibraryEntity('Submitty', $this->location);
        $this->gateway->addGitLibrary($library, self::VALID_GIT_URL);

        $response = $this->gateway->updateLibrary($library);

        $this->assertTrue($response->success);
        $this->assertEquals('Successfully updated Submitty', $response->message);
    }

    /** @test */
    public function testItDoesntUpdateNonGitLibraries() {
        $library = new LibraryEntity('test', $this->location);
        $this->createLibrary($library);

        $response = $this->gateway->updateLibrary($library);

        $this->assertFalse($response->success);
        $this->assertEquals(
            'Error updating repository. fatal: not a git repository (or any of the parent directories): .git',
            $response->message);
    }

    /** @test */
    public function testItDoesntUpdateNonExistentLibraries() {
        $library = new LibraryEntity('Thanos did nothing wrong.', $this->location);

        $response = $this->gateway->updateLibrary($library);

        $this->assertFalse($response->success);
        $this->assertEquals('Library does not exist.', $response->message);
    }
}
