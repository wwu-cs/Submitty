<?php namespace tests\app\libraries\homework\Gateways;

use ZipArchive;
use app\libraries\FileUtils;
use app\libraries\homework\Gateways\FileSystemLibraryGateway;

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
        $loc = FileUtils::joinPaths($this->location, 'Submitty');
        $return = $this->gateway->addGitLibrary(self::VALID_GIT_URL, $loc);

        $this->assertEquals('success', $return);
        $this->assertDirectoryExists($loc);
        $this->assertDirectoryExists(FileUtils::joinPaths($loc, '.git'));
    }

    /** @test */
    public function testItFailsCloningABadGitRepository() {
        $loc = FileUtils::joinPaths($this->location, 'Submitty');
        $return = $this->gateway->addGitLibrary('invalid url', $loc);

        $this->assertEquals('Error when cloning the repository.', $return);
        $this->assertDirectoryNotExists($loc);
    }

    /** @test */
    public function testItDoesNotAddAnInvalidZipFile() {
        $loc = FileUtils::joinPaths($this->location, 'Submitty');
        $return = $this->gateway->addZipLibrary('invalid zip', $loc);

        $this->assertEquals('Error opening zip file.', $return);
        $this->assertDirectoryNotExists($loc);
    }

    /** @test */
    public function testItAddsAZipFile() {
        $loc = FileUtils::joinPaths($this->location, 'test');
        $zip = $this->createTestZip('test.zip');

        $return = $this->gateway->addZipLibrary($zip, $loc);
        $this->assertEquals('success', $return);
        $this->assertDirectoryExists($loc);
        $this->assertFileExists(FileUtils::joinPaths($loc, 'test.txt'));
    }
}
