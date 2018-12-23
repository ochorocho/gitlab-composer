<?php

namespace Gitlab\Console\Command;

use Composer\Command\BaseCommand;
use Composer\Config;
use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Satis\Builder\ArchiveBuilder;
use Composer\Util\ProcessExecutor;
use Gitlab\Builder\PackagesBuilder;
use Composer\Satis\Console\Application;
use Composer\Satis\PackageSelection\PackageSelection;
use Composer\Util\RemoteFilesystem;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;

/**
 * @author Jochen Roth <rothjochen@gmail.com>
 */
class PublishCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Publish package to gitlab')
            ->setDefinition([
                new InputArgument('file', InputArgument::OPTIONAL, 'Json file to use', './satis.json'),
                new InputOption('gitlab-url', InputArgument::REQUIRED, InputOption::VALUE_OPTIONAL, 'Gitlab url to push package', 'https://www.gitlab.com'),
                new InputOption('build-upload-dir', null, InputOption::VALUE_OPTIONAL, 'Directory containing files for upload', 'build'),

            ])
            ->setHelp(<<<'EOT'
The <info>publish</info> command will push a generated archive and its json to a Gitlab instance.

- <info>"gitlab-url"</info>: Gitlab url to push packages

EOT
            );
    }

    /**
     * @param InputInterface $input The input instance
     * @param OutputInterface $output The output instance
     *
     * @throws JsonValidationException
     * @throws ParsingException
     * @throws \Exception
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // $url = $input->getArgument('gitlab-url');
        $uploadDir = $input->getOption('build-upload-dir');
        $satis = $this->getSatisConfiguration($input);

        /**
         * Find files to upload
         */
        $files = $this->findFilesForUpload((string)$uploadDir);

        $output->writeln("\n<options=bold,underscore>About to Publish following files ...</>");
        foreach ($files as $file) {
            $output->writeln("\t$file");

            // Build attachments to send
            $attachments[] = [
                'contents' => base64_encode(file_get_contents($file)),
                'filename' => basename($file),
                'length' => filesize($file)
            ];
        }

        $composer = new JsonFile($satis['repositories'][0]['url'] . 'composer.json');
        $composer = $composer->read();

        /**
         * Upload files
         */
        $client = new Client([
            'timeout' => 20.0,
        ]);

        /**
         * Build Gitlab request
         */
        $response = $client->request(
            'PUT',
            'http://localhost:3001/api/v4/projects/24/packages/composer/my_nice_package', [
                'body' => json_encode([
                    'name' => $composer['name'],
                    'version' => '1.1.666',
                    'attachments' => $attachments,
                ]),
                'query' => [
                    'private_token' => $satis['token']
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {
            $output->writeln('<info>Package ' . $composer['name'] . ' published ...</info>');
        }

    }

    /**
     * Get Satis local project configuration
     *
     * @param InputInterface $input The input instance
     */
    private function getSatisConfiguration(InputInterface $input)
    {
        $satisConfig = new JsonFile($input->getArgument('file'));
        return $satisConfig->read();
    }

    /**
     * Find all files needed for this package
     *
     * @param string $uploadDir
     */
    private function findFilesForUpload($uploadDir)
    {
        $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadDir));
        $files = array();

        foreach ($dirs as $file) {
            if ($file->isDir()) {
                continue;
            }

            if (preg_match("/\.(json|tar|zip)*$/i", $file, $matches)) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

}