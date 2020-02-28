<?php

declare(strict_types=1);

namespace Gitlab\Composer\Command;

use Composer\Command\BaseCommand;
use Gitlab\Composer\Service\GitlabPublisher;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GitlabPublishCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Upload archive and json files to Gitlab')
            ->setDefinition([
                new InputArgument('project-url', InputArgument::REQUIRED, 'Gitlab project url'),
                new InputArgument('project-id', InputArgument::REQUIRED, 'Gitlab project id'),
                new InputArgument('private-token', InputArgument::OPTIONAL, 'Gitlab private token'),
                new InputOption('build-path', 'p', InputOption::VALUE_OPTIONAL, 'Package files path', 'build'),
            ])
            ->setHelp(<<<EOT
The publish command uploads archive and json files to your Gitlab instance.
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $publisher = new GitlabPublisher($input->getOption('build-path'), $input->getArgument('project-url'), $input->getArgument('private-token'));

            $client = new Client(['timeout' => 20.0,]);

            $files = $publisher->findFilesToUpload($input->getOption('build-path'));
            $attachment = $publisher->prepareAttachment($files['archive']);
            $publisher->uploadPackageJson($files['json'], $client, $attachment, $input->getArgument('project-id'));

            $output->writeln('<info>Files uploaded: '. PHP_EOL . "\t" . implode(PHP_EOL . "\t", $files) . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Could not upload files: ' . $e . '</error>');
        }

        return 1;
    }
}
