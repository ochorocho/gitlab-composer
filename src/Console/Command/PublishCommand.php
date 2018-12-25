<?php

/**
 * This file is part of ochorocho/gitlab-composer.
 *
 * (c) ochorocho <https://github.com/ochorocho/gitlab-composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Gitlab\Console\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
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

- <info>"--gitlab-url"</info>: Gitlab url to push packages
- <info>"--build-upload-dir"</info>: Directory containing files for upload

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
        $uploadDir = $input->getOption('build-upload-dir');
        $satis = $this->getSatisConfiguration($input);
        $privateToken = getenv('PRIVATE_TOKEN');
        $projectId = getenv('CI_PROJECT_ID');
        $projectUrl = parse_url(getenv('CI_PROJECT_URL'));
        $projectUrl = $projectUrl['scheme'] . "://" . $projectUrl['host'] . ':' . $projectUrl['port'];

        if (empty($projectId)) {
            $output->writeln("<error>Env CI_PROJECT_ID not set</error>");
            return;
        }

        /**
         * Find files to upload
         */
        $files = $this->findFilesForUpload((string)$uploadDir);

        $packageJson = null;
        $output->writeln("\n<options=bold,underscore>About to Publish following files ...</>");
        foreach ($files as $file) {
            $output->writeln("\t$file");

            if (preg_match('/.json$/', $file, $fileMatches)) {
                preg_match('/version-(.*).json$/', $file, $packageVersion);
                $packageJson = new JsonFile($file);
                $packageJson = $packageJson->read();
            }

            // Build attachments to send
            $attachments[] = [
                'contents' => file_get_contents($file),
                'filename' => basename($file),
                'length' => filesize($file)
            ];
        }

        $composer = new JsonFile($satis['repositories'][0]['url'] . 'composer.json');
        $composer = $composer->read();

        /**
         * Build Gitlab request
         */
        $client = new Client([
            'timeout' => 20.0,
        ]);

        $packageName = urlencode($composer['name']);
        $apiUrl = $projectUrl . '/api/v4/projects/' . $projectId . "/packages/composer/" . $packageName;

        $response = $client->request(
            'PUT',
            $apiUrl, [
                'body' => json_encode([
                    'name' => $composer['name'],
                    'version' => $packageVersion[1],
                    'version_data' => $packageJson[$packageVersion[1]],
                    'shasum' => '',
                    'attachments' => $attachments,
                ]),
                'query' => [
                    'private_token' => $privateToken
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