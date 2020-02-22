<?php

declare(strict_types=1);

namespace Gitlab\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\VcsRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;
use Composer\Semver\VersionParser;

final class GitlabPackageCommand extends BaseCommand
{

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
        $inputArray = new ArrayInput(array('command' => 'archive', '--dir' => './tmp/', '--format' => 'tar'));
        $application = new Application();
        $application->setAutoExit(false);

        $io = $this->getIO();
        $io->write('Creating Package ...');

        $application->run($inputArray);
    }

    private function buildJson(InputInterface $input)
    {
        $io = $this->getIO();
        $io->write('Creating JSON Metadata file ...');

        $json = new JsonFile($input->getOption('json'));
        $json = $json->read();
        $outputJson = new JsonFile('tmp/single-package.json');

        $versionDetails = $this->getPackageDetails();

        $json['version'] = $versionDetails['version'];
        $json['version_normalized'] = $versionDetails['version_normalized'];
        $json['source'] = [
            'type' => 'git',
            'url' => $versionDetails['repository_url'],
            'reference' => $versionDetails['commit_sha'],
        ];
        $json['dist'] = [
            'type' => 'tar',
            'url' => 'http://www.example.com/build/composer/satis/composer-satis-1.0.0-alpha3-e9b2d8.tar',
            'reference' => $versionDetails['commit_sha'],
            'shasum' => $versionDetails['file_sha'],
        ];

        $outputJson->write($json);
//        $outputJson->validateSchema();
    }

    /**
     * Get versions details
     *
     * Branch name:     CI_COMMIT_REF_NAME
     * Tag name:        CI_COMMIT_TAG
     * Repository URL:  CI_REPOSITORY_URL
     * Commit sha:      CI_COMMIT_SHA
     *
     * @return array
     */
    private function getPackageDetails() : array
    {
        $versionParser = new VersionParser();
        $version = [];
        $tag = getenv('CI_COMMIT_TAG');
        $branch = getenv('CI_COMMIT_REF_NAME');
        $url = getenv('CI_REPOSITORY_URL');
        $sha = getenv('CI_COMMIT_SHA');
        $shasum = hash_file('sha256', './tmp/ochorocho-gitlab-composer-383ed84fc9d47bc32a39c151048ebebba52aea05-4377e8.tar');
        $envVersion = !empty($tag) ? $tag : $branch;
        $normalizedVersion = !empty($tag) ? $versionParser->normalize($tag) : $versionParser->normalizeBranch($branch);

        $version['version'] = $envVersion;
        $version['version_normalized'] = $normalizedVersion;
        $version['repository_url'] = $url;
        $version['commit_sha'] = $sha;
        $version['file_sha'] = $shasum;

        return $version;
    }
}
