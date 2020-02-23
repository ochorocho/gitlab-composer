<?php

declare(strict_types=1);

namespace Gitlab\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Repository\VcsRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;
use Composer\Semver\VersionParser;
use Symfony\Component\Process\Process;

final class GitlabPackageCommand extends BaseCommand
{
    /**
     * @var string
     */
    protected $buildPath = 'build';

    /**
     * @var array
     */
    protected $versionDetails = [];

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->versionDetails = $this->getPackageDetails($input);
    }

    protected function configure()
    {
        $this
            ->setName('package')
            ->setDescription('Generate a package for Gitlab')
            ->setDefinition([
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
        $inputArray = new ArrayInput([
            'command' => 'archive',
            '--dir' => './',
            '--format' => 'tar',
            '--file' => $this->versionDetails['output_path_filename']
        ]);
        $application = new Application();
        $application->setAutoExit(false);

        $io = $this->getIO();

        $name = $this->versionDetails['raw_composer']['name'];
        $version = $this->versionDetails['version'];
        $io->write("Creating Package $name / $version");

        $application->run($inputArray);
    }

    /**
     * Build json file for upload to Gitlab
     *
     * @param InputInterface $input
     * @return string
     * @throws \Exception
     */
    private function buildJson(InputInterface $input): void
    {
        $versionDetails = $this->versionDetails;
        $json = $versionDetails['raw_composer'];
        $io = $this->getIO();
        $io->write('Creating JSON Metadata file ' . $versionDetails['output_path_filename'] . '.json  ...');
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
     * @param InputInterface $input
     * @return array
     */
    private function getPackageDetails(InputInterface $input): array
    {
        $io = new NullIO();
        $config = Factory::createConfig();
        $io->loadConfiguration($config);
        $repository = new VcsRepository(['url' => './', 'type' => 'git'], $io, $config);

        $json = new JsonFile($input->getOption('json'));
        $json = $json->read();
        $tag = getenv('CI_COMMIT_TAG');
        $url = getenv('CI_REPOSITORY_URL');
        $sha = getenv('CI_COMMIT_SHA');
        $versionParser = new VersionParser();
        $branch = $versionParser->normalizeBranch(getenv('CI_COMMIT_REF_NAME'));
        $envVersion = !empty($tag) ? $tag : $branch;

        $this->prepareRepo($tag);

        $repository = $repository->findPackage($json['name'], $envVersion);
        $repositoryVersion = $repository->getPrettyVersion();
        $normalizedVersion = $repository->getVersion();
        $shaShort = substr($sha, 0, 7);
        $outputPath = $this->buildPath . '/' . str_replace('/', '-', $json['name']) . '-' . $repositoryVersion . '-' . $shaShort;

        $version = [];
        $version['version'] = $repositoryVersion;
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
    private function getArchiveSha(string $path): string
    {
        return hash_file('sha256', $path . '.tar');
    }

    /**
     * @param $tag
     */
    private function prepareRepo($tag): void
    {
        $fetch = new Process(['git fetch --unshallow || true']);
        $fetch->run();
        $pull = new Process(['git', 'pull', 'origin']);
        $pull->run();

//        if (empty($tag)) {
//            $checkout = new Process(['git', 'checkout', getenv('CI_COMMIT_REF_NAME')]);
//        } else {
//            $checkout = new Process(['git', 'checkout', "tags/$tag", '-b', "$tag-branch"]);
//        }
//        $checkout->run();
    }
}
