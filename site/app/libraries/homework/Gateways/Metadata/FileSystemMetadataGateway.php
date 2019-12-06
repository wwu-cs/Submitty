<?php

namespace app\libraries\homework\Gateways\Metadata;

use DateTime;
use app\libraries\FileUtils;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Entities\MetadataGetStatus;
use app\libraries\homework\Entities\MetadataUpdateStatus;
use app\libraries\homework\Gateways\Library\FileSystemLibraryGateway;

class FileSystemMetadataGateway implements MetadataGateway {
    const DATE_FORMAT = 'Y-m-d\TH:i:s.u';

    /** @var FileSystemLibraryGateway */
    protected $libraryGateway;

    /**
     * @param FileSystemLibraryGateway $libraryGateway
     */
    public function __construct(FileSystemLibraryGateway $libraryGateway) {
        $this->libraryGateway = $libraryGateway;
    }

    /** @inheritDoc */
    public function update(MetadataEntity $entity): MetadataUpdateStatus {
        // We already have the name, base entity, source type, and dates set.
        // We just need to go count the number of gradeables now.

        // First check to see if the library is there
        if (!file_exists($entity->getLibrary()->getLibraryPath())) {
            return MetadataUpdateStatus::error('Library does not exist.');
        }

        // Now we count the number of gradeables by the configs we can see.
        $configs = $this->fileSearchRecursive($entity->getLibrary()->getLibraryPath(), 'config.json');

        $entity = MetadataEntity::copyWithGradeableCount($entity, count($configs));

        $jsonFile = $this->getJsonFilePathFromLibrary($entity->getLibrary());
        FileUtils::writeJsonFile(
            $jsonFile,
            [
                'name'            => $entity->getName(),
                'source_type'     => $entity->getSourceType(),
                'gradeable_count' => $entity->getGradeableCount(),
                'updated_at'      => $this->formatDate($entity->getLastUpdatedDate()),
                'created_at'      => $this->formatDate($entity->getCreatedDate()),
            ]
        );

        return MetadataUpdateStatus::success($entity);
    }

    /**
     * Searches for a specific filename of $filename recursively from staring point $location
     *
     * @param string $location
     * @param string $filename
     * @return string[]
     */
    protected function fileSearchRecursive(string $location, string $filename): array {
        $files = [];
        $path = realpath($location);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if (basename($file) === $filename) {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * Gets the path to the library.json file in a library
     *
     * @param LibraryEntity $library
     * @return string
     */
    protected function getJsonFilePathFromLibrary(LibraryEntity $library): string {
        return FileUtils::joinPaths($library->getLibraryPath(), 'library.json');
    }

    /**
     * Formats a date
     *
     * @param DateTime $d
     * @return string
     */
    protected function formatDate(DateTime $d): string {
        return $d->format('Y-m-d\TH:i:s.u');
    }

    /** @inheritDoc */
    public function getAll(string $location): array {
        $return = [];

        /** @var LibraryEntity $library */
        foreach ($this->libraryGateway->getAllLibraries($location) as $library) {
            $response = $this->get($library);
            // Skip it if it has an error
            if (!$response->error) {
                $return[] = $response->result;
            }
        }

        return $return;
    }

    /** @inheritDoc */
    public function get(LibraryEntity $entity): MetadataGetStatus {
        // First check to see if the library is there
        if (!file_exists($entity->getLibraryPath())) {
            return MetadataGetStatus::error('Library does not exist.');
        }

        $jsonFile = $this->getJsonFilePathFromLibrary($entity);

        if (!file_exists($jsonFile)) {
            return MetadataGetStatus::error(
                'No metadata for the library. Please update the library to update the metadata.'
            );
        }

        $json = FileUtils::readJsonFile($jsonFile);

        // Validate JSON
        if (!(array_key_exists('name', $json) &&
              array_key_exists('source_type', $json) &&
              array_key_exists('gradeable_count', $json) &&
              array_key_exists('updated_at', $json) &&
              array_key_exists('created_at', $json)
        )) {
            return MetadataGetStatus::error('Incomplete library.json file');
        }

        // Validate JSON
        if (!(
            is_string($json['name']) &&
            is_string($json['source_type']) &&
            is_int($json['gradeable_count']) &&
            is_string($json['updated_at']) &&
            is_string($json['created_at'])
        )) {
            return MetadataGetStatus::error('Invalid library.json file');
        }

        return MetadataGetStatus::success(
            new MetadataEntity(
                $entity,
                $json['name'],
                $json['source_type'],
                $json['gradeable_count'],
                $this->constructDate($json['updated_at']),
                $this->constructDate($json['created_at'])
            )
        );
    }

    /**
     * Pulls a date back in from a config file
     *
     * @param string $s
     * @return DateTime
     */
    protected function constructDate(string $s): DateTime {
        $format = DateTime::createFromFormat(self::DATE_FORMAT, $s);

        if (!$format) {
            $format = DateTime::createFromFormat('Y-m-d H:i:s', '0000-00-00 00:00:00');
        }

        return $format;
    }
}
