<?php

namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;

class GitlabPublisherTest extends Unit
{
    public function testGitlabPublisher()
    {
        $publisher = $this->make('\Gitlab\Composer\Publisher\GitlabPublisher', ['buildPath' => 'build']);

        $this->assertEquals('build', $publisher->getBuildPath());
        $this->assertEmpty($publisher->setBuildPath('hooray'));

        codecept_debug($publisher->setBuildPath('hooray'));
    }
}
