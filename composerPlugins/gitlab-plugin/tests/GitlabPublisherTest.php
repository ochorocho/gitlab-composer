<?php

namespace Gitlab;

use Codeception\Test\Unit;
use Codeception\Util\Debug;
use Gitlab\Composer\Service\GitlabPublisher;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;


class GitlabPublisherTest extends Unit
{
    private $publisher;

    protected function _before()
    {
        parent::_before();
        $this->publisher = $this->make('\Gitlab\Composer\Service\GitlabPublisher');
    }

    public function testGetProjectUrl()
    {
        $url = $this->publisher->getProjectUrl('https://example.com/');
        $urlNoSlash = $this->publisher->getProjectUrl('https://example.com');
        $portUrl = $this->publisher->getProjectUrl('https://example.com:8080/');

        $this->assertEquals('https://example.com', $url);
        $this->assertEquals('https://example.com', $urlNoSlash);
        $this->assertEquals('https://example.com:8080', $portUrl);

        codecept_debug($this->publisher);
    }

    public function testPrepareAttachment() {
        $attachment = $this->publisher->prepareAttachment('/Users/jochen/php-projekt-data/gitlab-composer/composerPlugins/gitlab-plugin/tests/fixtures/package.json');

        $this->assertEquals('package.json', $attachment['filename']);
        $this->assertEquals('1437', $attachment['length']);

        codecept_debug($attachment);
    }

    public function testGetAuthHeader() {
        $authHeader = $this->publisher->getAuthHeader('PRIVATE_TOKEN');

        $this->assertEquals('application/json', $authHeader['content-type']);
        $this->assertEquals('application/json', $authHeader['Accept']);
        $this->assertEquals('PRIVATE_TOKEN', $authHeader['Private-Token']);

        codecept_debug($authHeader);
    }

    public function testFindFilesToUpload() {
        $files = $this->publisher->findFilesToUpload(__DIR__ . '/fixtures/');

        $this->assertEquals(__DIR__ . '/fixtures/package.json', $files['json']);
        $this->assertEquals(__DIR__ . '/fixtures/package.tar', $files['archive']);

        codecept_debug($files);
    }

    public function testUploadJson() {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $attachment = $this->publisher->prepareAttachment(__DIR__ . '/fixtures/package.tar');
        $response = $this->publisher->uploadPackageJson(__DIR__ . '/fixtures/package.json', $client, $attachment, 19);

        $this->assertEquals('ochorocho/gitlab-composer', $response['name']);
        $this->assertEquals('v1.x-dev', $response['version']);
        $this->assertIsArray($response['json']);
        $this->assertEquals('835', $response['package_file']['length']);
        $this->assertEquals('package.tar', $response['package_file']['filename']);

        codecept_debug($response);
    }
}
