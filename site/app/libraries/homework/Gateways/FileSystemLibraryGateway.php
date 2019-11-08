<?php namespace app\libraries\homework\Gateways;


use ZipArchive;
use app\libraries\FileUtils;

class FileSystemLibraryGateway implements LibraryGateway {
    const SUCCESS = 0;
    const STDERR = 2;

    protected function createFolderIfNotExists(string $path): bool {
        return FileUtils::createDir($path);
    }

    /** @inheritDoc */
    public function addGitLibrary(string $repoUrl, string $location): string {
        if (!$this->createFolderIfNotExists($location)) {
            return 'Error when creating folder.';
        }

        $sanitizedRepoUrl = escapeshellarg($repoUrl);
        $sanitizedLocation = escapeshellarg($location);

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
            FileUtils::recursiveRmdir($location);
            return "Error when cloning the repository: $stderr";
        }

        return 'success';
    }

    /** @inheritDoc */
    public function addZipLibrary(string $filePath, string $location): string {
        if (!$this->createFolderIfNotExists($location)) {
            return 'Error when creating folder.';
        }

        $zip = new ZipArchive();
        $res = $zip->open($filePath);
        if ($res === TRUE) {
            if (!$zip->extractTo($location)) {
                FileUtils::recursiveRmdir($location);
                return 'Error extracting zip file.';
            }
            $zip->close();
        } else {
            FileUtils::recursiveRmdir($location);
            return 'Error opening zip file.';
        }

        return 'success';
    }

    /** @inheritDoc */
    public function getAllLibraries(string $location): array {
        return FileUtils::getAllDirs($location);
    }

    /** @inheritDoc */
    public function libraryExists(string $name, string $location): bool {
        return in_array($name, $this->getAllLibraries($location));
    }
}
