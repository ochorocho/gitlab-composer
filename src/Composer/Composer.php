<?php

namespace Gitlab\Composer;

use GitWrapper\GitWrapper;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;
use Symfony\Component\Process\Process;

Class Composer {
    /**
     * @var string
     */
    protected $user = "root";


    public function doStuff($huhu) {
        return '>>>>>>>>> ' . $huhu;
    }

    /**
     * Export a package archive
     *
     * @param string $packageName
     * @param string $tag
     * @return string
     */
    public function exportArchive(string $packageName, string $tag) {

        $gitWrapper = new GitWrapper($this->gitRepository->findAll()->getFirst()->getBinaryPath());
        $gitWrapper->cloneRepository($repoClone, $repoPath);

        return '>>>>>>>>> ' . $huhu;
    }

    /**
     * Export archive
     *
     * Update all added projects of all servers available
     *
     * @param string $packageName
     * @param string $tag
     * @return void
     */
    public function updatablePackagesCommand(string $packageName, string $tag) {
        $input = new ArrayInput(array('command' => "archive $packageName $tag", '--format' => 'tar'));
        $application = new Application();
        $application->setAutoExit(false);

        $process = new Process($application->run($input));
        $process->run(function ($type, $buffer) {
            $this->outputLine("<success>$buffer</success>");
        });
    }

}