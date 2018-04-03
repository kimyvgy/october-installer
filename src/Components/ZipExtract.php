<?php

namespace KimNH\OctoberInstaller\Console\Components;

use ZipArchive;

class ZipExtract
{
    protected $subDirectory = null;

    public function removeSubDirectory($innerDirectory)
    {
        $this->subDirectory = $innerDirectory;
        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param  string $zipFile
     * @param  string $directory
     * @return $this
     */
    public function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;
        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();

        // Clean up:
        @chmod($zipFile, 0777);
        @unlink($zipFile);

        // Move contents to main directory:
        if ($this->subDirectory) {
            system("mv {$directory}/{$this->subDirectory}/* {$directory}");
            system("rm -rf {$directory}/{$this->subDirectory}");
        }

        return $this;
    }
}
