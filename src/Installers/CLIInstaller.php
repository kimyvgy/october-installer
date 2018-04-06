<?php

namespace KimNH\OctoberInstaller\Console\Installers;

use KimNH\OctoberInstaller\Console\Components\Downloader;
use KimNH\OctoberInstaller\Console\Components\ZipExtract;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
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

        $this->prepareWritableDirectories($directory, $io);

        $io->newLine();
        $io->section('<info>October is installed at: '.$directory.'</info>');
        $agree = $io->confirm(
            '<comment>Do you want set up this application such as: Composer, Database, admin?</comment>',
            false
        );

        if ($agree) {
            $commands = [
                $this->findComposer().' install',
                '"'.PHP_BINARY.'" artisan october:install',
                '"'.PHP_BINARY.'" artisan october:update',
            ];
            $process = new Process(implode(' && ', $commands), $directory);
            if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                $process->setTty(true);
            }

            $process->run(function ($__, $line) use ($io) {
                $io->write($line);
            });
        } else {
            $io->section('<info>Flowing commands above to complete:</info>');
            $io->listing([
                'cd '.basename($directory),
                'composer install',
                'docker-compose up -d',
                'docker exec -it october_workspace bash',
                'php artisan october:install',
                'php artisan october:update',
            ]);
        }
    }

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @param  string $appDirectory
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return $this
     */
    protected function prepareWritableDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        try {
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR."bootstrap/cache", 0755, 0000, true);
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR."storage", 0755, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>You should verify that the "storage" and "bootstrap/cache" directories are writable.</comment>');
        }

        return $this;
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
