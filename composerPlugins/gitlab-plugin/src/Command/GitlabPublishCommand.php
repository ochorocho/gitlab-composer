<?php

declare(strict_types=1);

namespace Gitlab\Composer\Command;

use Composer\Command\BaseCommand;
use Gitlab\Composer\Publisher\GitlabPublisher;
use http\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GitlabPublishCommand extends BaseCommand
{
    /**
     * @var string
     */
    protected $buildPath = 'build';

    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Upload archive and json files to Gitlab')
            ->setDefinition([
                new InputArgument('project-url', InputArgument::REQUIRED, 'Gitlab project url'),
                new InputArgument('project-id', InputArgument::REQUIRED, 'Gitlab project id'),
                new InputArgument('private-token', InputArgument::OPTIONAL, 'Gitlab private token'),
            ])
            ->setHelp(<<<EOT
The publish command uploads archive and json files to your Gitlab instance.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            new GitlabPublisher($output, $this->buildPath , $input);
        } catch (\Exception $e) {
            $output->writeln('<error>Could not upload files: ' . $e . '</error>');
        }
    }
}
