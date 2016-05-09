<?php

namespace Valet;

use Exception;
use Symfony\Component\Process\Process;

class PhpFpm
{
    var $aptGet, $cli, $files;

    var $taps = [
        'php7.0'
    ];

    /**
     * Create a new PHP FPM class instance.
     *
     * @param  AptGet  $aptGet
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(AptGet $aptGet, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->aptGet = $aptGet;
        $this->files = $files;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    function install()
    {
        if (! $this->aptGet->installed('php70') && ! $this->aptGet->installed('php56')) {
            $this->aptGet->ensureInstalled('php70', $this->taps);
        }

        $this->updateConfiguration();

        $this->restart();
    }

    /**
     * Update the PHP FPM configuration to use the current user.
     *
     * @return void
     */
    function updateConfiguration()
    {
        $contents = $this->files->get($this->fpmConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = '.user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);

        $this->files->put($this->fpmConfigPath(), $contents);
    }

    /**
     * Restart the PHP FPM process.
     *
     * @return void
     */
    function restart()
    {
        $this->stop();

        $this->aptGet->restartLinkedPhp();
    }

    /**
     * Stop the PHP FPM process.
     *
     * @return void
     */
    function stop()
    {
        $this->aptGet->stopService('php56', 'php70');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    function fpmConfigPath()
    {
        // TODO I think those filepaths are wrong for Ubuntu
        if ($this->aptGet->linkedPhp() === 'php70') {
            return '/usr/local/etc/php/7.0/php-fpm.d/www.conf';
        } else {
            return '/usr/local/etc/php/5.6/php-fpm.conf';
        }
    }
}
