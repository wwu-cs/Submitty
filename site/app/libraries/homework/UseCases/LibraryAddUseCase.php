<?php

namespace app\libraries\homework\UseCases;

use app\libraries\Core;
use app\libraries\Utils;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;
use app\libraries\homework\Entities\MetadataEntity;
use app\libraries\homework\Gateways\MetadataGateway;
use app\libraries\homework\Responses\LibraryAddResponse;
use app\libraries\homework\Gateways\Library\LibraryGatewayFactory;
use app\libraries\homework\Gateways\Metadata\MetadataGatewayFactory;

class LibraryAddUseCase extends BaseUseCase {
    /** @var LibraryGateway */
    protected $gateway;

    /** @var MetadataGateway */
    protected $metadata;

    public function __construct(Core $core) {
        parent::__construct($core);

        $this->gateway = LibraryGatewayFactory::getInstance();
        $this->metadata = MetadataGatewayFactory::getInstance();
    }

    /**
     * Takes a string representing the git url to clone, and adds it to the library
     *
     * @param string|null $repoUrl
     * @param string|null $metaName
     * @return LibraryAddResponse
     */
    public function addGitLibrary($repoUrl, $metaName): LibraryAddResponse {
        if (!$repoUrl) {
            return LibraryAddResponse::error('A repo url is required.');
        }

        // Validate the url with regex
        // Regex can be viewed in detail here.
        // https://www.debuggex.com/r/H4kRw1G0YPyBFjfm
        // It validates .git repository urls.
        $match = preg_match(
            '/((git|ssh|http(s)?)|(git@[\w.]+))(:(\/\/)?)([\w.@:\/\-~]+)(\.git)(\/)?/',
            $repoUrl,
            $matches
        );
        if (!$match) {
            return LibraryAddResponse::error(
                'The git url is not of the right format.'
            );
        }

        // Parse the git url from capture groups in the regex
        /*
         * From the link above, one can easily see that group 7 is the wanted group.
         * We use index 7 because index 0 from preg match is the whole string.
         * We then split and take the name which is usually at the end of the url.
         * This will not work for same repo names with different authors, so
         * that will probably want to be fixed later.
         */
        $parts = explode('/', $matches[7]);
        $gitName = array_pop($parts);

        $status = $this->gateway->addGitLibrary($this->generateNewLibrary(), $repoUrl);

        if ($status->failed()) {
            return LibraryAddResponse::error(
                'Error adding the library. ' .
                $status->message
            );
        }

        // Default the library name to the git repository name
        if (!$metaName) {
            $metaName = $gitName;
        }

        // Create metadata for the library
        $metadataStatus = $this->metadata->update(
            MetadataEntity::createNewMetadata(
                $status->library,
                $metaName,
                'git'
            )
        );

        // Check for error when adding the metadata
        if ($metadataStatus->error) {
            // Cleanup
            $this->gateway->removeLibrary($status->library);
            return LibraryAddResponse::error(
                'Library was cloned, however the metadata was not able to be created because: ' . $metadataStatus->error
            );
        }

        return LibraryAddResponse::success("Successfully cloned $repoUrl.");
    }

    /**
     * Generates random folder names until it finds a random string that is not a folder name
     *
     * @return LibraryEntity
     */
    protected function generateNewLibrary(): LibraryEntity {
        do {
            $library = new LibraryEntity(Utils::generateRandomString(), $this->location);
        } while ($this->gateway->libraryExists($library));
        return $library;
    }

    /**
     * Takes in a $_FILES file and adds it to the library
     *
     * @param array|null  $zipFile
     * @param string|null $metaName
     * @return LibraryAddResponse
     */
    public function addZipLibrary($zipFile, $metaName): LibraryAddResponse {
        // Basic validation
        if (!$zipFile || !isset($zipFile['name']) || !isset($zipFile['tmp_name'])) {
            return LibraryAddResponse::error('A file must be provided.');
        }

        // Parsing
        $origName = $zipFile['name'];
        // Separate extension from name
        $parts = explode('.', $origName);
        $extension = array_pop($parts);

        // Check for .zip
        if (strtolower($extension) != 'zip' || count($parts) < 1) {
            return LibraryAddResponse::error('A .zip file must be provided.');
        }

        // Attempt to add the library
        $status = $this->gateway->addZipLibrary($this->generateNewLibrary(), $zipFile['tmp_name']);

        if ($status->failed()) {
            return LibraryAddResponse::error(
                'Error adding the library. ' .
                $status->message
            );
        }

        // Default the library name to the original zip file name
        if (!$metaName) {
            $metaName = implode('.', $parts);
        }

        // Update metadata
        $metadataStatus = $this->metadata->update(
            MetadataEntity::createNewMetadata(
                $status->library,
                $metaName,
                'zip'
            )
        );

        // Check for error when adding the metadata
        if ($metadataStatus->error) {
            // Cleanup
            $this->gateway->removeLibrary($status->library);
            return LibraryAddResponse::error(
                'Library was created, however the metadata was not able to be created because: ' .
                $metadataStatus->error
            );
        }

        return LibraryAddResponse::success("Successfully installed new library: $metaName");
    }
}
