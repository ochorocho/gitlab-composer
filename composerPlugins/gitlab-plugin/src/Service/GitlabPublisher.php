<?php
declare(strict_types=1);
/*
 * This file is part of gitlab/composer-plugin.
 *
 * (c) ochorocho <https://github.com/ochorocho>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Gitlab\Composer\Service;

use Composer\Json\JsonFile;
use GuzzleHttp\Client;
use phpDocumentor\Reflection\Types\Integer;
use PHPUnit\Util\Exception;

class GitlabPublisher
{
    /** @var string $buildPath Path where files are stored. */
    protected string $buildPath;

    /** @var array $authHeader Gitlab auth header. */
    protected array $authHeader;

    /** @var integer $projectUrl Gitlab project url. */
    protected $projectUrl;

    /** @var string $privateToken */
    protected $privateToken;

    public function __construct(string $buildPath, string $projectUrl, $privateToken)
    {
        $this->buildPath = $buildPath;
        $this->projectUrl = $this->getProjectUrl($projectUrl);
        $this->authHeader = $this->getAuthHeader($privateToken);
        $this->privateToken = $privateToken;
    }

    /**
     * Find all files needed for this package
     *
     * @param $buildPath
     * @return array
     */
    public static function findFilesToUpload($buildPath)
    {
        if (!is_dir($buildPath)) {
            mkdir($buildPath, 0777, true);
        }

        $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($buildPath));
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
     * @param $url
     * @return string
     */
    public function getProjectUrl($url) : string
    {
        try {
            if (!empty($url)) {
                $projectUrl = parse_url($url);
            }

            $port = empty($projectUrl['port']) ? '' : ':' . $projectUrl['port'];
            return sprintf("%s://%s%s", $projectUrl['scheme'], $projectUrl['host'], $port);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * Set Gitlab API authentication header
     *
     * @return array
     * @throws \Exception
     */
    public function getAuthHeader($privateToken)
    {
        $jobToken = getenv('CI_JOB_TOKEN');
        $authHeader = $privateToken ? ["Private-Token" => $privateToken] : ["JOB-TOKEN" => $jobToken];
        $mimeHeader = ['content-type' => 'application/json', 'Accept' => 'application/json'];

        if (empty($privateToken) && empty($jobToken)) {
            throw new \Exception('Authentication not set. You have following options: \n * Empty will try to use \'CI_JOB_TOKEN\' env var \n * Set cli argument \'private-token\'');
        }

        return array_merge($mimeHeader, $authHeader);
    }

    /**
     * Upload package metadata
     *
     * @param $file
     * @param Client $client
     * @param array $attachment
     * @param Integer $projectId
     * @return array
     */
    public function uploadPackageJson($file, Client $client, $attachment, $projectId) : array
    {
        $composer = new JsonFile($file);
        $composer = $composer->read();

        $packageName = urlencode($composer['name']);
        $apiPackageJsonUrl = $this->projectUrl . '/api/v4/projects/' . $projectId . "/packages/composer/" . $packageName;

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
                    'headers' => $this->getAuthHeader($this->privateToken),
                    'body' => json_encode($body)
                ]
            );

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Couldn\'t upload package ' . $composer['name'] . ' ' . $composer['version'] . ' ...');
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $body;
    }

    /**
     * Build array for attachment
     *
     * @param $file
     * @return array
     */
    public function prepareAttachment($file) : array
    {
        $attachment = [
            'contents' => base64_encode(file_get_contents($file)),
            'filename' => basename($file),
            'length' => filesize($file)
        ];

        return $attachment;
    }


}
