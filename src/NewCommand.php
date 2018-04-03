<?php

namespace KimNH\OctoberInstaller\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new OctoberCMS application')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    }

    /**
     *  Execute a command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $directory = ($input->getArgument('name')) ? getcwd().'/'.$input->getArgument('name') : getcwd();

        if (!$input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('Welcome to OctoberCMS Installer');
        $io->section('<info>Starting october installer</info>');
        $io->text('[GET] https://octobercms.com/download');

        $this->download($zipFile = $this->makeFilename(), new ProgressBar($output))
            ->extract($zipFile, $directory)
            ->cleanUp($zipFile);

        $this->makeDockerComposeFile($directory);

        $io->newLine(2);
        $io->section('<info>Flowing commands above to complete:</info>');
        $io->text('cd '.basename($directory));
        $io->text('docker-compose up -d');
        $io->text('Open: http://localhost:8000/install.php');
        $io->newLine();
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string $directory
     * @throws RuntimeException
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
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

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string $zipFile
     * @param ProgressBar $progressBar
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function download($zipFile, ProgressBar $progressBar)
    {
        $progressBar->setBarCharacter('<info>=</info>');
        $progressBar->setEmptyBarCharacter(' ');
        $progressBar->setProgressCharacter('>');
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %memory:6s%');
        $progressBar->start();
        $response = (new Client)->get('https://octobercms.com/download', [
            RequestOptions::PROGRESS => function ($total, $downloaded) use ($progressBar) {
                if (!$downloaded) {
                    $progressBar->start($total);
                }

                $progressBar->setProgress($downloaded);
            }
        ]);

        file_put_contents($zipFile, $response->getBody());
        $progressBar->finish();

        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param  string $zipFile
     * @param  string $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;
        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();
        system("mv {$directory}/install-master/* {$directory}");
        system("rm -rf {$directory}/install-master");
        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string $zipFile
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);
        return $this;
    }

    protected function makeDockerComposeFile($directory)
    {
        $filesystem = new Filesystem();
        $filesystem->copy(__DIR__.'/../docker-compose.yml', $directory.'/docker-compose.yml');
    }
}
