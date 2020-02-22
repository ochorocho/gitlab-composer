<?php
declare(strict_types=1);
/*
 * This file is part of composer/satis.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Satis\Publisher;

use Composer\Composer;
use Composer\Json\JsonFile;
use GuzzleHttp\Client;
use http\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitlabPublisher extends Publisher
{
    /** @var array $authHeader Gitlab auth header. */
    protected $authHeader;
    /** @var integer $projectUrl Gitlab project url. */
    protected $projectUrl;

    public function __construct(OutputInterface $output, string $outputDir, array $config, bool $skipErrors, InputInterface $input)
    {
        parent::__construct($output, $outputDir, $config, $skipErrors, $input);
        $this->projectUrl = $this->getProjectUrl();
        $this->authHeader = $this->getAuthHeader();
        $this->outputDir = $outputDir;
        $this->sendPackageToGitlab();
    }

    /**
     * Upload attachment and json metadata to Gitlab
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPackageToGitlab()
    {
        $files = $this->findFilesToUpload($this->outputDir);
        $client = new Client(['timeout' => 20.0,]);
        $attachment = $this->prepareAttachment($files['archive']);
        $this->uploadePackageJson($files['json'], $client, $attachment);
    }

    /**
     * Find all files needed for this package
     *
     * @param $outputDir
     * @return array
     */
    public static function findFilesToUpload($outputDir)
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($outputDir));
        $files = array();

        foreach ($dirs as $file) {
            if ($file->isDir()) {
                continue;
            }

            if (preg_match("/\.(tar|zip)*$/i", $file->getPathname(), $matches)) {
                $files['archive'] = $file->getPathname();
            }

            if (preg_match("/\.(json)*$/i", $file->getPathname(), $matches)) {
                $files['json'] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Get project url
     *
     * @return string
     */
    private function getProjectUrl()
    {
        try {
            $url = $this->input->getArgument('project-url');

            if (!empty($url)) {
                $projectUrl = parse_url($url);
            }

            return sprintf("%s://%s:%s", $projectUrl['scheme'], $projectUrl['host'], $projectUrl['port']);
        } catch (\Exception $e) {
            $this->output->writeln($e);
            exit;
        }
    }

    /**
     * Set Gitlab API authentication header
     *
     * @return array
     */
    private function getAuthHeader()
    {
        $privateToken = $this->input->getArgument('private-token');
        $jobToken = getenv('CI_JOB_TOKEN');
        $authHeader = $privateToken ? ["Private-Token" => $privateToken] : ["JOB-TOKEN" => $jobToken];
        $mimeHeader = ['content-type' => 'application/json', 'Accept' => 'application/json'];

        if (empty($privateToken) && empty($jobToken)) {
            $this->output->writeln("<error>Authentication not set. You have following options: \n * Empty will try to use 'CI_JOB_TOKEN' env var \n * Set cli option '--private-token' </error>");
        }

        return array_merge($mimeHeader, $authHeader);
    }

    /**
     * Upload package metadata
     *
     * @param $file
     * @param Client $client
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function uploadePackageJson($file, Client $client, $attachment)
    {
        $composer = new JsonFile($file);
        $composer = $composer->read();
        $composer = reset($composer);
        $packageName = urlencode($composer['name']);
        $apiPackageJsonUrl = $this->projectUrl . '/api/v4/projects/' . $this->input->getArgument('project-id') . "/packages/composer/" . $packageName;

        $body = [
            'name' => $composer['name'],
            'version' => $composer['version'],
            'json' => $composer,
            'package_file' => $attachment,
        ];

        try {
            $response = $client->request(
                'PUT',
                $apiPackageJsonUrl,
                [
                    'headers' => $this->authHeader,
                    'body' => json_encode($body)
                ]
            );

            if ($response->getStatusCode() == 200) {
                $this->output->writeln('<info>Package ' . $composer['name'] . ' ' . $composer['version'] . ' published ...</info>');
            } else {
                $this->output->writeln('<error>Couldn\'t upload package ' . $composer['name'] . ' ' . $composer['version'] . ' ...</error>');
            }
        } catch (\Exception $e) {
            $this->output->writeln($e);
        }

        return $body;
    }

    /**
     * Build array for attachment
     *
     * @param $file
     * @return array
     */
    private function prepareAttachment($file)
    {
        $attachment = [
            'contents' => base64_encode(file_get_contents($file)),
            'filename' => basename($file),
            'length' => filesize($file)
        ];

        return $attachment;
    }
}
