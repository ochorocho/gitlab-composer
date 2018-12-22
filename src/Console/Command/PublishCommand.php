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
                new InputArgument('gitlab-url', InputArgument::REQUIRED, 'Gitlab url to push package', null),
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
        $url = $input->getArgument('gitlab-url');
        $uploadDir = $input->getOption('build-upload-dir');

        $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadDir));

        $files = array();

        foreach ($dirs as $file) {
            if ($file->isDir()){
                continue;
            }

            // Load only specific mime types
            if (preg_match("/\.(json|tar|zip)*$/i", $file, $matches)) {
                $files[] = $file->getPathname();
            }
        }

        var_dump($files);

        // load auth.json authentication information and pass it to the io interface
        $io = $this->getIO();
        $io->loadConfiguration($this->getConfiguration());
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