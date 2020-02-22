<?php

declare(strict_types=1);

namespace Gitlab\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;
use Composer\Semver\VersionParser;

final class GitlabPackageCommand extends BaseCommand
{
    /**
     * @var string
     */
    protected $buildPath = 'build';

    protected function configure()
    {
        $this
            ->setName('package')
            ->setDescription('Generate a package for Gitlab')
            ->setDefinition([
                new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output: text or json', 'text'),
                new InputOption('json', 'j', InputOption::VALUE_REQUIRED, 'Composer json file', 'composer.json'),
            ])
            ->setHelp(<<<EOT
The package command creates an archive file (tar) and json file for Gitlab Packages.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'package', $input, $output);
        $composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);

        $this->buildArchive($input);
        $this->buildJson($input);
    }

    private function buildArchive(InputInterface $input)
    {
        $versionDetails = $this->getPackageDetails($input);

        $inputArray = new ArrayInput([
            'command' => 'archive',
            '--dir' => './',
            '--format' => 'tar',
            '--file' => $versionDetails['output_path_filename']
        ]);
        $application = new Application();
        $application->setAutoExit(false);

        $io = $this->getIO();
        $io->write('Creating Package ...');

        $application->run($inputArray);
    }

    /**
     * Build json file for upload to Gitlab
     *
     * @param InputInterface $input
     * @return string
     * @throws \Exception
     */
    private function buildJson(InputInterface $input) : void
    {
        $io = $this->getIO();
        $io->write('Creating JSON Metadata file ...');

        $versionDetails = $this->getPackageDetails($input);
        $json = $versionDetails['raw_composer'];

        $outputJson = new JsonFile($versionDetails['output_path_filename'] . '.json');

        $json['version'] = $versionDetails['version'];
        $json['version_normalized'] = $versionDetails['version_normalized'];
        $json['time'] = $versionDetails['time'];
        $json['source'] = [
            'type' => 'git',
            'url' => $versionDetails['repository_url'],
            'reference' => $versionDetails['commit_sha'],
        ];
        $json['dist'] = [
            'type' => 'tar',
            'url' => 'http://www.example.com/build/composer/satis/composer-satis-1.0.0-alpha3-e9b2d8.tar',
            'reference' => $versionDetails['commit_sha'],
            'shasum' => $this->getArchiveSha($versionDetails['output_path_filename']),
        ];

        $outputJson->write($json);
        $outputJson->validateSchema(JsonFile::LAX_SCHEMA);
    }

    /**
     * Get versions details
     *
     * Branch name:     CI_COMMIT_REF_NAME
     * Tag name:        CI_COMMIT_TAG
     * Repository URL:  CI_REPOSITORY_URL
     * Commit sha:      CI_COMMIT_SHA
     *
     * @param array $json
     * @return array
     */
    private function getPackageDetails(InputInterface $input) : array
    {
        $json = new JsonFile($input->getOption('json'));
        $json = $json->read();
        $versionParser = new VersionParser();
        $version = [];
        $tag = getenv('CI_COMMIT_TAG');
        $branch = getenv('CI_COMMIT_REF_NAME');
        $url = getenv('CI_REPOSITORY_URL');
        $sha = getenv('CI_COMMIT_SHA');
        $envVersion = !empty($tag) ? $tag : $branch;
        $normalizedVersion = !empty($tag) ? $versionParser->normalize($tag) : $versionParser->normalizeBranch($branch);
        $shaShort = substr($sha,0,7);
        $outputPath = $this->buildPath . '/' . str_replace('/', '-', $json['name']) . '-' . $envVersion . '-' . $shaShort;

        $version['version'] = $envVersion;
        $version['version_normalized'] = $normalizedVersion;
        $version['repository_url'] = $url;
        $version['commit_sha'] = $sha;
        $version['output_path_filename'] = $outputPath;
        $version['raw_composer'] = $json;
        $version['time'] = gmDate(DATE_ATOM);

        return $version;
    }

    /**
     * @param string $path
     * @return string
     */
    private function getArchiveSha(string $path) : string {
        return hash_file('sha256', $path . '.tar');
    }
}
