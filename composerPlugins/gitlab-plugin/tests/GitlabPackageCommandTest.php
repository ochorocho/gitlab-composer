<?php

namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Gitlab\Composer\Command\GitlabPackageCommand;

require_once codecept_data_dir('../src/Command/GitlabPackageCommand.php');

class GitlabPackageCommandTest extends Unit
{

    protected function _before()
    {
        putenv("CI_COMMIT_SHA=XXXXXXXXXX");
        putenv("CI_REPOSITORY_URL=http://web.de");
        putenv("CI_COMMIT_REF_NAME=master");
    }

        public function testPackageCommand()
    {

        $application = new Application();
        $application->add(new GitlabPackageCommand());

//        codecept_debug($application);

        $command = $application->find('package');
        $commandTester = new CommandTester($command);

//        $commandTester->execute([
//            'command'  => $command->getName(),
//            '--help' => true
//        ]);
//
//        $this->assertContains('default', $commandTester->getDisplay());
    }
}
