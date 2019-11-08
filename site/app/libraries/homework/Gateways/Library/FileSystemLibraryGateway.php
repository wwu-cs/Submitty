<?php namespace app\libraries\homework\Gateways\Library;


use ZipArchive;
use app\libraries\FileUtils;
use app\libraries\homework\Entities\LibraryEntity;
use app\libraries\homework\Gateways\LibraryGateway;

class FileSystemLibraryGateway implements LibraryGateway {
    const SUCCESS = 0;
    const STDERR = 2;

    protected function createFolderIfNotExists(string $path): bool {
        return FileUtils::createDir($path);
    }

    /** @inheritDoc */
    public function addGitLibrary(LibraryEntity $library, string $repoUrl): string {
        if ($this->libraryExists($library)) {
            return 'Library already exists.';
        }

        if (!$this->createFolderIfNotExists($library->getLibraryPath())) {
            return 'Error when creating folder.';
        }

        $sanitizedRepoUrl = escapeshellarg($repoUrl);
        $sanitizedLocation = escapeshellarg($library->getLibraryPath());

        $cmd = "git clone $sanitizedRepoUrl $sanitizedLocation";

        $descriptors = [
            ["pipe", "r"],  // stdin
            ["pipe", "w"],  // stdout
            ["pipe", "w"],  // stderr
        ];
        $git = proc_open($cmd, $descriptors, $pipes);

        $stderr = trim(stream_get_contents($pipes[self::STDERR]));

        // All pipes need to be closed before closing the process otherwise a deadlock occurs
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $status = proc_close($git);

        if ($status != self::SUCCESS) {
            FileUtils::recursiveRmdir($library->getLibraryPath());
            return "Error cloning repository. $stderr";
        }

        return 'success';
    }

    /** @inheritDoc */
    public function addZipLibrary(LibraryEntity $library, string $tmpFilePath): string {
        if ($this->libraryExists($library)) {
            return 'Library already exists.';
        }

        if (!$this->createFolderIfNotExists($library->getLibraryPath())) {
            return 'Error when creating folder.';
        }

        $zip = new ZipArchive();
        $res = $zip->open($tmpFilePath);
        if ($res === TRUE) {
            if (!$zip->extractTo($library->getLibraryPath())) {
                FileUtils::recursiveRmdir($library->getLibraryPath());
                return 'Error extracting zip file.';
            }
            $zip->close();
        } else {
            FileUtils::recursiveRmdir($library->getLibraryPath());
            return 'Error opening zip file.';
        }

        return 'success';
    }

    /** @inheritDoc */
    public function getAllLibraries(string $location): array {
        $libs = FileUtils::getAllDirs($location);

        return array_map(function (string $lib) use ($location) {
            return new LibraryEntity(basename($lib), $location);
        }, $libs);
    }

    /** @inheritDoc */
    public function libraryExists(LibraryEntity $library): bool {
        $libraries = $this->getAllLibraries($library->getLocation());

        return count(array_filter($libraries, function (LibraryEntity $item) use ($library) {
            return $item->is($library);
        })) > 0;
    }
}
