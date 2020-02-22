<?php

declare(strict_types=1);

namespace Gitlab\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Repository\RepositoryInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;

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

        $this->buildJson($input);
        $this->buildArchive($input);
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
        $outputJson->write($json);
        $outputJson->validateSchema();
    }
}
