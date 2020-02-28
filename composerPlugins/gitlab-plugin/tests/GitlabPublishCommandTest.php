<?php

namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Gitlab\Composer\Command\GitlabPublishCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Gitlab\Composer\Command\GitlabPackageCommand;

require_once codecept_data_dir('../src/Command/GitlabPackageCommand.php');

class GitlabPublishCommandTest extends Unit
{

    protected function _before()
    {
        putenv("CI_COMMIT_SHA=XXXXXXXXXX");
        putenv("CI_REPOSITORY_URL=http://web.de");
        putenv("CI_COMMIT_REF_NAME=master");
        putenv("CI_JOB_TOKEN=PRIVATE_TOKEN");
    }

    public function testPublishCommand()
    {
        $application = new Application();
        $application->add(new GitlabPublishCommand());
        $command = $application->find('publish');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command'  => $command->getName(),
            'project-url' => 'https://www.example.com/',
            'project-id' => 19,
            '--build-path' => __DIR__ . '/fixtures/'
        ]);

        $this->assertRegExp('/\/fixtures\/package.json/', $commandTester->getDisplay());
        $this->assertRegExp('/\/fixtures\/package.tar/', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());

        codecept_debug($commandTester);
    }
}
