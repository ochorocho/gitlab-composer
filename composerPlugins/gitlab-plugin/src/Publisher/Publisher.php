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

namespace Gitlab\Composer\Publisher;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Publisher
 * @package Composer\Satis\Publisher
 */
abstract class Publisher
{
    /** @var OutputInterface $output The output Interface. */
    protected $output;
    /** @var InputInterface */
    protected $input;
    /** @var string $buildPath Path where files are stored. */
    protected $buildPath;

    public function __construct(
        OutputInterface $output,
        string $buildPath,
        InputInterface $input = null
    ) {
        $this->output = $output;
        $this->input = $input;
        $this->buildPath = $buildPath;
    }
}
