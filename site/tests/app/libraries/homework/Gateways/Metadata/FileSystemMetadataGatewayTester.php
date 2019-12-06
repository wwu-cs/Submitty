<?php

/*
 * When running in vagrant, PHPStorm doesn't have access to composer.json and doesn't
 * know about some requirements,
 *thereby complaining.
 */

/** @noinspection PhpComposerExtensionStubsInspection */

namespace tests\app\libraries\homework\Gateways\Metadata;

use ZipArchive;
use app\libraries\FileUtils;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use tests\app\libraries\homework\Gateways\BaseTestCase;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;
use app\libraries\homework\Gateways\Library\FileSystemLibraryGateway;
use app\libraries\homework\Gateways\Metadata\FileSystemMetadataGateway;

class FileSystemMetadataGatewayTester extends BaseTestCase {

    /** @var FileSystemLibraryGateway */
    protected $libraryGateway;

    /** @var FileSystemMetadataGateway */
    protected $metadataGateway;

    public function setUp(): void {
        parent::setUp();

        $this->libraryGateway = new FileSystemLibraryGateway();
        $this->metadataGateway = new FileSystemMetadataGateway($this->libraryGateway);
    }

    /** @test */
    public function testItDoesntUpdateNonExistentLibraries() {
        $metadata = MetadataEntity::createNewMetadata(
            new LibraryEntity('dont matter', $this->location),
            'name',
            'source'
        );

        $response = $this->metadataGateway->update($metadata);

        $this->assertEquals('Library does not exist.', $response->error);
    }

    /** @test */
    public function testItSetsMetadata() {
        $library = $this->addZipLibrary(
            'asdf',
            [
                'file1.txt' => 'Hello there!',
                'file2.txt' => 'GENERAL KENOBI!',
            ]
        );

        $result = $this->metadataGateway->update(
            MetadataEntity::createNewMetadata(
                $library,
                'nomen!',
                'bring me dat sauce!'
            )
        )->result;

        $this->assertEquals('nomen!', $result->getName());
        $this->assertTrue($result->hasSourceTypeOf('bring me dat sauce!'));
        $this->assertFileExists(FileUtils::joinPaths($library->getLibraryPath(), 'library.json'));
    }

    /**
     * Create zip file and add it to the repo
     *
     * @param string $name
     * @param array  $fileList
     * @return LibraryEntity
     */
    protected function addZipLibrary(string $name, array $fileList): LibraryEntity {
        $zip = $this->createTestZipWithFiles($name . '.zip', $fileList);
        $library = new LibraryEntity($name, $this->location);
        return $this->libraryGateway->addZipLibrary($library, $zip)->library;
    }

    /**
     * Creates a temporary zip file in the tempdir location with the given
     * files and content form the key value pair array fileList
     *
     * @param string   $zipName
     * @param string[] $fileList
     * @return string
     */
    protected function createTestZipWithFiles(string $zipName, array $fileList): string {
        $zip = new ZipArchive();

        $f = FileUtils::joinPaths($this->location, $zipName);

        $res = $zip->open($f, ZipArchive::CREATE);
        $this->assertTrue($res);
        foreach ($fileList as $fileName => $content) {
            $zip->addFromString($fileName, $content);
        }
        $zip->close();
        return $f;
    }

    /** @test */
    public function testItCountsGradeables() {
        $library = $this->addZipLibrary(
            'asdf',
            [
                'config.json'                   => 'Hello there!',
                'folder1/config.json'           => 'GENERAL KENOBI!',
                'folder2/subfolder/config.json' => 'YOU ARE A BOLD ONE!',
                'notconfig.json'                => 'not a moment too soon',
                'folder1/folder3/asdf'          => 'This is where the fun begins',
            ]
        );

        $result = $this->metadataGateway->update(
            MetadataEntity::createNewMetadata(
                $library,
                'nomen!',
                'bring me dat sauce!'
            )
        )->result;

        $this->assertEquals(3, $result->getGradeableCount());
    }

    /** @test */
    public function testItCannotGetMetadataForANonExistentLibrary() {
        $response = $this->metadataGateway->get(new LibraryEntity('key', $this->location));

        $this->assertEquals('Library does not exist.', $response->error);
    }

    /** @test */
    public function testItGetsALibraryItPutsDown() {
        $library = $this->addZipLibrary(
            'fdsa',
            [
                'filder/yipyip.txt'  => 'The earth king invites you to Lake Laogai',
                'filder/config.json' => 'There is no war in the walls',
            ]
        );

        $this->metadataGateway->update(
            MetadataEntity::createNewMetadata(
                $library,
                'name',
                'source'
            )
        );

        $response = $this->metadataGateway->get($library);

        $this->assertEmpty($response->error);

        $result = $response->result;

        $this->assertTrue($library->is($result->getLibrary()));
        $this->assertEquals(1, $result->getGradeableCount());
        $this->assertEquals('name', $result->getName());
        $this->assertTrue($result->hasSourceTypeOf('source'));
    }

    /** @test */
    public function testItCannotGetMetadataWhenThereIsNone() {
        $library = $this->addZipLibrary('lib', ['file' => 'content']);

        $response = $this->metadataGateway->get($library);

        $this->assertEquals(
            'No metadata for the library. Please update the library to update the metadata.',
            $response->error
        );
    }

    /** @test */
    public function testItLooksForAllKeys() {
        $library = $this->addZipLibrary(
            'whee',
            [
                'file'         => 'rip in pieces',
                'library.json' => json_encode(
                    [
                        'name'        => 'name',
                        'source_type' => 'typeee',
                    ]
                ),
            ]
        );

        $response = $this->metadataGateway->get($library);

        $this->assertEquals('Incomplete library.json file', $response->error);
    }

    /** @test */
    public function testItChecksTypesOnJson() {
        $library = $this->addZipLibrary(
            'almost done',
            [
                'file'         => 'sodihgashdgoah',
                'library.json' => json_encode(
                    [
                        'name'            => 1,
                        'source_type'     => true,
                        'gradeable_count' => 'haha',
                        'created_at'      => 'not a date, oh well',
                        'updated_at'      => 'update me pls',
                    ]
                ),
            ]
        );

        $response = $this->metadataGateway->get($library);

        $this->assertEquals('Invalid library.json file', $response->error);
    }

    /** @test */
    public function testItGetsAllLibrariesWhenThereAreNone() {
        $results = $this->metadataGateway->getAll($this->location);

        $this->assertEquals([], $results);
    }

    /** @test */
    public function testItGetsAllLibraries() {
        $library = $this->addZipLibrary(
            'library numer uno',
            [
                'ring' => 'The one file to rule them all.',
            ]
        );

        $this->metadataGateway->update(
            MetadataEntity::createNewMetadata(
                $library,
                'name',
                'source'
            )
        );

        /** @var MetadataEntity[] $results */
        $results = $this->metadataGateway->getAll($this->location);
        $this->assertCount(1, $results);
        $this->assertEquals('name', $results[0]->getName());
        $this->assertTrue($results[0]->hasSourceTypeOf('source'));
    }

    /** @test */
    public function testDefaultGatewayIsFileSystemGateway() {
        MetadataGatewayFactory::clearInstance();
        $instance = MetadataGatewayFactory::getInstance();
        $this->assertInstanceOf(FileSystemMetadataGateway::class, $instance);
    }
}
