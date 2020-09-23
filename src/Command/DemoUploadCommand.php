<?php

namespace App\Command;

use App\Config\IgmdbConfig;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DemoUploadCommand extends Command
{
    protected static $defaultName = 'app:demo:upload';

    /**
     * @var FilesystemInterface
     */
    private $demosStorage;

    /**
     * @var FilesystemInterface
     */
    private $defaultStorage;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var IgmdbConfig
     */
    private $igmdbConfig;

    /**
     * @var string
     */
    private $demoPublicUrl;

    public function __construct(
        FilesystemInterface $demosStorage,
        FilesystemInterface $defaultStorage,
        HttpClientInterface $httpClient,
        IgmdbConfig $igmdbConfig,
        string $demoPublicUrl
    ) {
        $this->demosStorage = $demosStorage;
        $this->defaultStorage = $defaultStorage;
        $this->httpClient = $httpClient;
        $this->igmdbConfig = $igmdbConfig;
        $this->demoPublicUrl = $demoPublicUrl;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Add a short description for your command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->defaultStorage->listContents() as $fileData) {
            if ($fileData['type'] !== 'file') {
                continue;
            }
            if ($this->demosStorage->has($fileData['basename'])) {
                $this->demosStorage->delete($fileData['basename']);
            }

            $io->comment('Processing ' . $fileData['basename']);

            $fileContents = $this->defaultStorage->read($fileData['path']);
            $this->demosStorage->write($fileData['basename'], $fileContents);

            $response = $this->httpClient->request('POST', $this->igmdbConfig->getBaseUrl() . '/processor.php?action=submitDemo', [
                'body' => [
                    'api_key' => $this->igmdbConfig->getApiKey(),
                    'demo_url' => $this->demoPublicUrl . $fileData['basename'],
                    'stream_title' => $fileData['filename'],
                ],
            ]);

            $io->note(
                sprintf('%s ::: %s %s', $fileData['basename'], $response->getStatusCode(), $response->getContent())
            );

            $this->demosStorage->delete($fileData['basename']);
        }

        return Command::SUCCESS;
    }
}
