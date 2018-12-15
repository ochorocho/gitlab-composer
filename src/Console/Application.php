<?php

/*
 * This file is part of ochorocho/gitlab-composer.
 *
 * (c) Ochorocho <https://github.com/ochorocho/gitlab-composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Gitlab\Console;

use Composer\Satis\Console\Command;
use Gitlab\Console\Command as GitlabCommand;
use Composer\Satis\Console\Application as BaseApplication;

/**
 * @author Jochen Roth <rothjochen@gmail.com>
 */
class Application extends BaseApplication
{

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = array_merge(parent::getDefaultCommands(), [
            new GitlabCommand\BuildLocalCommand(),
        ]);

        return $commands;
    }
}
