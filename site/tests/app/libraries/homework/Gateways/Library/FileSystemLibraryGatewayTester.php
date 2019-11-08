<?php namespace tests\app\libraries\homework\Gateways\Library;

use app\libraries\homework\Entities\LibraryEntity;
use tests\app\libraries\homework\Gateways\BaseTestCase;
use ZipArchive;
use app\libraries\FileUtils;
use app\libraries\homework\Gateways\Library\FileSystemLibraryGateway;

class FileSystemLibraryGatewayTester extends BaseTestCase {
    /** @var FileSystemLibraryGateway */
    protected $gateway;

    const VALID_GIT_URL = "https://github.com/Submitty/Submitty.git";

    public function setUp(): void {
        parent::setUp();

        $this->gateway = new FileSystemLibraryGateway();
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
    public function testItClonesAGitRepository() {
        $library = new LibraryEntity('Submitty', $this->location);
        $return = $this->gateway->addGitLibrary($library, self::VALID_GIT_URL);

        $this->assertEquals('success', $return);
        $this->assertDirectoryExists($library->getLibraryPath());
        $this->assertDirectoryExists(FileUtils::joinPaths($library->getLibraryPath(), '.git'));
    }

    /** @test */
    public function testItFailsCloningABadGitRepository() {
        $library = new LibraryEntity('name', $this->location);

        $return = $this->gateway->addGitLibrary($library, 'invalid url');

        $this->assertEquals(
            'Error cloning repository. fatal: repository \'invalid url\' does not exist',
            $return
        );
        $this->assertDirectoryNotExists($library->getLibraryPath());
    }

    /** @test */
    public function testItDoesNotAddAnInvalidZipFile() {
        $library = new LibraryEntity('name', $this->location);
        $return = $this->gateway->addZipLibrary($library, 'invalid zip');

        $this->assertEquals('Error opening zip file.', $return);
        $this->assertDirectoryNotExists($library->getLibraryPath());
    }

    /** @test */
    public function testItAddsAZipFile() {
        $zip = $this->createTestZip('test.zip');

        $library = new LibraryEntity('test', $this->location);

        $return = $this->gateway->addZipLibrary($library, $zip);
        $this->assertEquals('success', $return);
        $this->assertDirectoryExists($library->getLibraryPath());
        $this->assertFileExists($library->getLibraryPath() . '/test.txt');
    }
}
