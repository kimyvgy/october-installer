<?php

namespace KimNH\OctoberInstaller\Console\Installers;

use KimNH\OctoberInstaller\Console\Components\Downloader;
use KimNH\OctoberInstaller\Console\Components\ZipExtract;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class CLIInstaller
{
    const URL = 'https://github.com/octobercms/october/archive/master.zip';

    public function execute(SymfonyStyle $io, $directory)
    {
        $io->section('<info>We are going to start October CLI installer</info>');
        $zipFile = $this->makeFilename();

        (new Downloader($io))
            ->download(self::URL, $zipFile);

        (new ZipExtract)
            ->removeSubDirectory('october-master')
            ->extract($zipFile, $directory);

        $io->newLine();
        $io->section('<info>October is installed at: '.$directory.'</info>');
        $agree = $io->confirm(
            '<comment>Do you want set up this application such as: Database, admin?</comment>',
            false
        );

        if ($agree) {
            $commands = [
                $this->findComposer().' install',
                '"'.PHP_BINARY.'" artisan october:install',
            ];
            $process = new Process(implode(' && ', $commands), $directory);
            if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                $process->setTty(true);
            }

            $process->run(function ($__, $line) use ($io) {
                $io->write($line);
            });
        }
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
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
