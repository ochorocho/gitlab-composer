<?php

namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;

class PublisherTest extends Unit
{
    public function testPublisher()
    {
        $publisher = $this->make('\Gitlab\Composer\Publisher\Publisher', ['buildPath' => 'build']);

        $this->assertEquals('build', $publisher->getBuildPath());
        $this->assertEmpty($publisher->setBuildPath('hooray'));

        codecept_debug($publisher->setBuildPath('hooray'));
    }
}
