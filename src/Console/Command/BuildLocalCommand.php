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
use Composer\Config;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Package\Version\VersionParser;
use Gitlab\Builder\ArchiveBuilder;
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


class BuildLocalCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setName('build-local')
            ->setDescription('Builds a composer package for single tag')
            ->setDefinition([
                new InputArgument('file', InputArgument::OPTIONAL, 'Json file to use', './satis.json'),
                new InputOption('skip-errors', null, InputOption::VALUE_NONE, 'Skip Download or Archive errors'),
                new InputOption('version-to-dump', null, InputOption::VALUE_OPTIONAL, 'Version of package to dump e.g. tag/branch', 'dev-master'),
                new InputOption('stats', null, InputOption::VALUE_NONE, 'Display the download progress bar'),
            ])
            ->setHelp(<<<'EOT'
The <info>build-local</info> command reads the given json file
(satis.json is used by default) and outputs a composer
repository in the given archive -> absolute-directory.

- <info>"version-to-dump"</info>: Version of a package to dump. Can be branch or tag
- <info>"stats"</info>: Show download progress 

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
        $verbose = $input->getOption('verbose');
        $configFile = $input->getArgument('file');
        $skipErrors = (bool) $input->getOption('skip-errors');


        // load auth.json authentication information and pass it to the io interface
        $io = $this->getIO();

        if (preg_match('{^https?://}i', $configFile)) {
            $rfs = new RemoteFilesystem($io);
            $contents = $rfs->getContents(parse_url($configFile, PHP_URL_HOST), $configFile, false);
            $config = JsonFile::parseJson($contents, $configFile);
        } else {
            $file = new JsonFile($configFile);
            if (!$file->exists()) {
                $output->writeln('<error>File not found: ' . $configFile . '</error>');

                return 1;
            }
            $config = $file->read();
        }

        // disable packagist by default
        unset(Config::$defaultRepositories['packagist'], Config::$defaultRepositories['packagist.org']);

        /** @var $application Application */
        $application = $this->getApplication();
        $composer = $application->getComposer(true, $config);
        $packageSelection = new PackageSelection($output, null, $config, $skipErrors);

        $packages = $packageSelection->select($composer, $verbose);

        /**
         * Set git repo url as source
         * Limit to given version/tag
         */
        $process = new ProcessExecutor($io);

        $versionParser = new VersionParser;

        try {
            $parsedBranch = $versionParser->normalize($input->getOption('version-to-dump'));
        } catch (\Exception $e) {

            $CI_BUILD_REF_NAME = getenv(CI_BUILD_REF_NAME);
            $CI_BUILD_REF = getenv(CI_BUILD_REF);
            if(isset($CI_BUILD_REF_NAME) && isset($CI_BUILD_REF)) {
                $process->execute('git checkout -b "$CI_BUILD_REF_NAME" "$CI_BUILD_REF"');
            }

            $parsedBranch = $versionParser->normalizeBranch($input->getOption('version-to-dump'));
        }

        $parsedBranch = str_replace('== ', '', $parsedBranch);

        foreach ($packages as $key => $package) {
            $process->execute("git remote get-url --all origin", $url);
            $package->setSourceUrl(str_replace("\n", '', $url));

            /**
             * Determine branch or tag and limit to set version in --version-to-dump
             */
            if ('dev-' === substr($parsedBranch, 0, 4) || '9999999-dev' === $parsedBranch) {
                $versionToBuild = 'dev-' . $input->getOption('version-to-dump');
                $packageVersion = $package->getPrettyVersion();

            } else {
                $prefix = substr($input->getOption('version-to-dump'), 0, 1) === 'v' ? 'v' : '';
                $versionToBuild = $prefix . preg_replace('{(\.9{7})+}', '.x', $parsedBranch);
                $packageVersion = $package->getVersion();
                $versionToBuild = preg_replace('/^v/', '$1', $versionToBuild);
            }

            if($packageVersion !== $versionToBuild) {
              unset($packages[$key]);
            }
        }

        /**
         * Download tar files
         */
        $downloads = new ArchiveBuilder($output, null, $config, $skipErrors);
        $downloads->setComposer($composer);
        $downloads->setInput($input);
        $downloads->dump($packages);

        /**
         * Build Package based include file
         */
        $packagesBuilder = new PackagesBuilder($output, null, $config, $skipErrors);
        $packagesBuilder->dump($packages);

    }

}