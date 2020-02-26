<?php

namespace Gitlab;

use Codeception\Stub;
use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Gitlab\Composer\Publisher\GitlabPublisher;
use Symfony\Component\Console\Tester\CommandTester;

//use Symfony\Component\Console\Output\OutputInterface;
//use Symfony\Component\Console\Input\InputInterface;

class GitlabPublisherTest extends Unit
{
    /** @var OutputInterface $output The output Interface. */
    protected $output;
    /** @var InputInterface */
    protected $input;
    /** @var string $buildPath Path where files are stored. */
    protected $buildPath;

    protected function _before()
    {

    }

    public function testGitlabPublisher()
    {


//        $publisher = $this->make('\Gitlab\Composer\Publisher\GitlabPublisher', ['buildPath' => 'build', 'output' => $this->output]);
//        $output = new OutputInterface();

//        $output = $this->getMockBuilder(Symfony\Component\Console\Output\OutputInterface::class)->getMock();
        $output = $this->makeEmpty('\Symfony\Component\Console\Output\OutputInterface');
//        $input = $this->getMockBuilder(Symfony\Component\Console\Input\InputInterface::class)->getMock();
//        $input = $this->make('\Symfony\Component\Console\Input\InputInterface', ['buildPath' => 'build', 'output' => 'sadasdsa']);
        $input = $this->makeEmpty('\Symfony\Component\Console\Input\InputInterface',
            ['buildPath' => 'build', 'project-url' => 'https://exmaple.com/']);

//        $input = new \Symfony\Component\Console\Input\InputInterface();
//        $input->setArgument('project-url', 'http://www.google.de');

//        $commandTester = new CommandTester('package');

//        $input->argument = ['project-url' => 'www.example.com'];
//        $this->input->getArgument('project-url');

//        codecept_debug($input);
        $publisher = new GitlabPublisher($output, 'build', $input);

//        var_dump($publisher);

        codecept_debug($publisher->setBuildPath('########'));
        codecept_debug($publisher->getBuildPath());
//        $this->assertEquals('build', $publisher->getBuildPath());
//        $this->assertEmpty($publisher->setBuildPath('hooray'));
//        $this->assertEmpty($publisher->setBuildPath('hooray'));

//        codecept_debug($output);
//        codecept_debug($input);
//        codecept_debug($publisher);
    }
}
