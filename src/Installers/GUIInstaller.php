<?php

namespace KimNH\OctoberInstaller\Console\Installers;

use KimNH\OctoberInstaller\Console\Components\Downloader;
use KimNH\OctoberInstaller\Console\Components\ZipExtract;
use Symfony\Component\Console\Style\SymfonyStyle;

class GUIInstaller
{
    const URL = 'https://octobercms.com/download';

    public function execute(SymfonyStyle $io, $directory)
    {
        $io->section('<info>We are going to start GUI October installer</info>');
        $zipFile = $this->makeFilename();
        (new Downloader($io))
            ->download(self::URL, $zipFile);

        (new ZipExtract)
            ->removeSubDirectory('install-master')
            ->extract($zipFile, $directory);

        $io->newLine(2);
        $io->section('<info>Flowing commands above to complete:</info>');
        $io->listing([
            'cd '.basename($directory),
            'docker-compose up -d',
            'Visit: <info>http://localhost:8000/install.php</info> to complete.',
        ]);
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/october_installer_'.md5(time().uniqid()).'.zip';
    }
}
