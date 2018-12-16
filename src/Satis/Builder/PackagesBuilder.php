<?php

namespace Gitlab\Satis\Builder;

use Composer\Json\JsonFile;
use Composer\Package\Dumper\ArrayDumper;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Satis\Builder\Builder;

Class PackagesBuilder extends Builder {
    /** @var string packages.json file name. */
    private $filename;

    /** @var string included json filename template */
    private $includeFileName;

    private $writtenIncludeJsons = [];

    /**
     * Dedicated Packages Constructor.
     *
     * @param OutputInterface $output     The output Interface
     * @param string          $outputDir  The directory where to build
     * @param array           $config     The parameters from ./satis.json
     * @param bool            $skipErrors Escapes Exceptions if true
     */
    public function __construct(OutputInterface $output, $outputDir, $config, $skipErrors)
    {
        parent::__construct($output, $outputDir, $config, $skipErrors);

        $this->filename = $this->outputDir . '/packages.json';
        $this->includeFileName = isset($config['include-filename']) ? $config['include-filename'] : 'include/all$%hash%.json';
    }

    /**
     * Builds the JSON stuff of the repository.
     *
     * @param \Composer\Package\PackageInterface[] $packages List of packages to dump
     */
    public function dump(array $packages)
    {
        $packagesByName = [];
        $dumper = new ArrayDumper();
        foreach ($packages as $package) {
            $packagesByName[$package->getName()][$package->getPrettyVersion()] = $dumper->dump($package);
        }

        $repo = ['packages' => []];

        if (isset($this->config['providers']) && $this->config['providers']) {
            $providersUrl = 'p/%package%$%hash%.json';
            if (!empty($this->config['homepage'])) {
                $repo['providers-url'] = parse_url(rtrim($this->config['homepage'], '/'), PHP_URL_PATH) . '/' . $providersUrl;
            } else {
                $repo['providers-url'] = $providersUrl;
            }
            $repo['providers'] = [];
            $i = 1;
            // Give each version a unique ID
            foreach ($packagesByName as $packageName => $versionPackages) {
                foreach ($versionPackages as $version => $versionPackage) {
                    $packagesByName[$packageName][$version]['uid'] = $i++;
                }
            }
            // Dump the packages along with packages they're replaced by

            foreach ($packagesByName as $packageName => $versionPackages) {
                $dumpPackages = $this->findReplacements($packagesByName, $packageName);
                $dumpPackages[$packageName] = $versionPackages;

                $includes = $this->dumpPackageIncludeJson(
                    $dumpPackages,
                    str_replace('%package%', $packageName, $providersUrl),
                    'sha256'
                );
                $repo['providers'][$packageName] = current($includes);
            }
        } else {
            $repo['includes'] = $this->dumpPackageIncludeJson($packagesByName, $this->includeFileName);
        }


    }

    /**
     * Writes includes JSON Files.
     *
     * @param array $packages List of packages to dump
     * @param string $includesUrl The includes url (optionally containing %hash%)
     * @param string $hashAlgorithm Hash algorithm {@see hash()}
     *
     * @return array The object for includes key in packages.json
     */
    public function dumpPackageIncludeJson(array $packages, $includesUrl, $hashAlgorithm = 'sha1')
    {


        $filename = str_replace('%hash%', 'prep', $this->includeFileName);
        $path = $tmpPath = $this->outputDir . '/' . ltrim($filename, '/');

        $repoJson = new JsonFile($path);
        $contents = $repoJson->encode(['packages' => $packages]) . "\n";

        $hash = hash($hashAlgorithm, $contents);

        if (strpos($includesUrl, '%hash%') !== false) {
            $this->writtenIncludeJsons[] = [$hash, $includesUrl];
            $filename = str_replace('%hash%', $hash, $includesUrl);
            if (file_exists($path = $this->outputDir . '/' . ltrim($filename, '/'))) {
                // When the file exists, we don't need to override it as we assume,
                // the contents satisfy the hash
                $path = null;
            }
        }

//        var_dump($contents);
        $pathHash = $tmpPath = $this->outputDir . '/' . ltrim($filename . '.shasum', '/');

        $this->writeToFile($pathHash, $hash);

        if ($path) {
            $this->writeToFile($path, $contents);
            $this->output->writeln("<info>wrote packages to $path</info>");
        }

        return [
            $filename => [$hashAlgorithm => $hash],
        ];
    }

    /**
     * Write to a file
     *
     * @param string $path
     * @param string $contents
     *
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    private function writeToFile($path, $contents)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (file_exists($dir)) {
                throw new \UnexpectedValueException(
                    $dir . ' exists and is not a directory.'
                );
            }
            if (!@mkdir($dir, 0777, true)) {
                throw new \UnexpectedValueException(
                    $dir . ' does not exist and could not be created.'
                );
            }
        }

        $retries = 3;
        while ($retries--) {
            try {
                file_put_contents($path, $contents);
                break;
            } catch (\Exception $e) {
                if ($retries) {
                    usleep(500000);
                    continue;
                }

                throw $e;
            }
        }
    }

}