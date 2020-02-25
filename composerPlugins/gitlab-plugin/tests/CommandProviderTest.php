<?php namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Gitlab\Composer\Command\CommandProvider;

class CommandProviderTest extends Unit
{
    public function testGetCommands()
    {
        $provider = new CommandProvider();
        $commands = $provider->getCommands();

        $this->assertCount(2, $commands);
        $this->assertEquals('Gitlab\Composer\Command\GitlabPackageCommand', get_class($commands[0]));
        $this->assertEquals('Gitlab\Composer\Command\GitlabPublishCommand', get_class($commands[1]));

        codecept_debug($commands);
    }
}
