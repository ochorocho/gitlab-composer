<?php

namespace Gitlab\Console\Command;

use Composer\Command\BaseCommand;
use Composer\Config;
use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
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
/**
 * @author Jochen Roth <rothjochen@gmail.com>
 */
class BuildLocalCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('build-local')
            ->setDescription('Builds a composer package for single tag')
            ->setDefinition([
                new InputArgument('file', InputArgument::OPTIONAL, 'Json file to use', './satis.json'),
                new InputArgument('output-dir', InputArgument::OPTIONAL, 'Location where to output built files', null),
                new InputOption('version-to-dump', null, InputOption::VALUE_OPTIONAL, 'Version of package to dump e.g. tag/branch', 'dev-master'),
                new InputOption('skip-errors', null, InputOption::VALUE_NONE, 'Skip Download or Archive errors'),
                new InputOption('stats', null, InputOption::VALUE_NONE, 'Display the download progress bar'),
            ])
            ->setHelp(<<<'EOT'
The <info>build-local</info> command reads the given json file
(satis.json is used by default) and outputs a composer
repository in the given output-dir.

The json config file accepts the following keys:

- <info>"version-to-dump"</info>: Version of a package to dump. Can be branch or tag
- <info>"repositories"</info>: defines which repositories are searched
  for packages.
- <info>"output-dir"</info>: where to output the repository files
  if not provided as an argument when calling build.
- <info>"minimum-stability"</info>: sets default stability for packages
  (default: dev), see
  http://getcomposer.org/doc/04-schema.md#minimum-stability
- <info>"config"</info>: all config options from composer, see
  http://getcomposer.org/doc/04-schema.md#config
- <info>"abandoned"</info>: Packages that are abandoned. As the key use the
  package name, as the value use true or the replacement package.
- <info>"archive"</info> archive configuration, see https://getcomposer.org/doc/articles/handling-private-packages-with-satis.md#downloads

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
        $io->loadConfiguration($this->getConfiguration());

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

        if (!$versionToBuild = $input->getOption('version-to-dump')) {
            $versionToBuild = isset($config['version-to-dump']) ? $config['version-to-dump'] : null;
        }

        foreach ($packages as $key => $package) {
            $process->execute("git remote get-url --all origin", $url);
            $package->setSourceUrl(str_replace("\n", '', $url));

            if($package->getPrettyVersion() !== $versionToBuild) {
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

    /**
     * @return Config
     */
    private function getConfiguration()
    {
        $config = new Config();

        // add dir to the config
        $config->merge(['config' => ['home' => $this->getComposerHome()]]);

        // load global auth file
        $file = new JsonFile($config->get('home') . '/auth.json');
        if ($file->exists()) {
            $config->merge(['config' => $file->read()]);
        }
        $config->setAuthConfigSource(new JsonConfigSource($file, true));

        return $config;
    }

    /**
     * @throws \RuntimeException
     *
     * @return string
     */
    private function getComposerHome()
    {
        $home = getenv('COMPOSER_HOME');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException('The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly');
                }
                $home = strtr(getenv('APPDATA'), '\\', '/') . '/Composer';
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException('The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly');
                }
                $home = rtrim(getenv('HOME'), '/') . '/.composer';
            }
        }

        return $home;
    }
}