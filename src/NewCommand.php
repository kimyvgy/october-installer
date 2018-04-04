<?php

namespace KimNH\OctoberInstaller\Console;

use KimNH\OctoberInstaller\Console\Installers\CLIInstaller;
use KimNH\OctoberInstaller\Console\Installers\GUIInstaller;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

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
            ->addOption('gui', null, InputOption::VALUE_NONE, 'Use GUI installer')
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
        $io->title('WELCOME TO OCTOBER CMS INSTALLER! ^^ <3 <3 <3');

        $this->makeDockerComposeFile($directory);
        if ($input->getOption('gui')) {
            (new GUIInstaller)->execute($io, $directory);
        } else {
            (new CLIInstaller)->execute($io, $directory);
        }

        $io->newLine(2);
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

    protected function makeDockerComposeFile($directory)
    {
        $filesystem = new Filesystem();
        $filesystem->copy(__DIR__.'/../docker-compose.yml', $directory.'/docker-compose.yml');
    }
}
