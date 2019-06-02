<?php

/*
 * This file is part of ochorocho/gitlab-composer.
 *
 * (c) ochorocho <https://github.com/ochorocho/gitlab-composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Gitlab\Builder;

use Composer\Satis\Builder\ArchiveBuilder as SatisArchiveBuilder;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveBuilder extends SatisArchiveBuilder
{
    /**
     * ArchiveBuilder Constructor.
     *
     * @param OutputInterface $output     The output Interface
     * @param string          $outputDir  The directory where to build
     * @param array           $config     The parameters from ./satis.json
     * @param bool            $skipErrors Skips Exceptions if true
     */
    public function __construct(OutputInterface $output, $outputDir, $config, $skipErrors)
    {

        $projectUrl = parse_url(getenv('CI_PROJECT_URL'));
        $port = isset($projectUrl['port']) ? ':' . $projectUrl['port'] : '';
        $projectUrl = $projectUrl['scheme'] . "://" . $projectUrl['host'] . $port;

        $this->output = $output;
        $this->outputDir = $outputDir;
        $this->config = $config;
        $this->config['homepage'] = $projectUrl;
        $this->config['archive']['directory'] = $this->config['archive']['absolute-directory'];
        $this->skipErrors = (bool) $skipErrors;
    }

}
