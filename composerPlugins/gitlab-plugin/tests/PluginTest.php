<?php

namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Gitlab\Composer\Plugin;

class PluginTest extends Unit
{
    public function testGetCapabilities()
    {
        $plugin = new Plugin();
        $capable = $plugin->getCapabilities();
        $key = array_key_first($capable);
        $value = $capable[$key];

        $this->assertCount(1, $capable);
        $this->assertEquals('Composer\Plugin\Capability\CommandProvider', $key);
        $this->assertEquals('Gitlab\Composer\Command\CommandProvider', $value);

        codecept_debug($capable);
    }

    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin();
        $events = $plugin->getSubscribedEvents();

        $this->assertEmpty($events);

        codecept_debug($events);
    }
}
