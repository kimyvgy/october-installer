<?php

namespace KimNH\OctoberInstaller\Console\Components;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Style\SymfonyStyle;

class Downloader
{
    /** @var SymfonyStyle $console */
    protected $console;

    /** @var string $url */
    protected $url;

    public function __construct(SymfonyStyle $console)
    {
        $this->console = $console;
    }

    public function download($url, $destination)
    {
        $progressBar = $this->createProgressBar(100);
        $progressBar->start();

        $response = (new Client)->get($url, [
            RequestOptions::PROGRESS => function ($total, $downloaded) use ($progressBar) {
                if ($total) {
                    $progressBar->setProgress($downloaded / $total * 100);
                }
            },
        ]);

        file_put_contents($destination, $response->getBody());
        $progressBar->finish();
        $this->console->newLine();

        return $this;
    }

    /**
     * Create an progress bar
     *
     * @param string $url
     * @param $max
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    protected function createProgressBar($max = 0)
    {
        $progressBar = $this->console->createProgressBar($max);
        $progressBar->setBarCharacter('<info>=</info>');
        $progressBar->setEmptyBarCharacter(' ');
        $progressBar->setProgressCharacter('>');
        $progressBar->setFormat("Downloading... %percent:3s%% [%bar%]");
        return $progressBar;
    }
}
