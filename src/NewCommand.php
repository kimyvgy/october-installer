<?php

namespace KimNH\OctoberInstaller\Console;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addOption('gui', null, InputOption::VALUE_NONE, 'Create by GUI installer')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    }

    /**
     *  Execute a command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
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

        $output->writeln('<info>Crafting application...</info>');
        $this->download($zipFile = $this->makeFilename())
            ->extract($zipFile, $directory)
            ->cleanUp($zipFile);

        if ($input->getOption('gui')) {
            $this->makeDockerComposeFile($directory);
        }

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
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
     * @return $this
     */
    protected function download($zipFile)
    {
        $response = (new Client)->get('https://octobercms.com/download');
        file_put_contents($zipFile, $response->getBody());
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
        $filesystem->copy(getcwd().'/'.'docker-compose.yml', $directory.'/docker-compose.yml');
    }
}
