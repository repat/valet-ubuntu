<?php

namespace Valet;

use Exception;
use DomainException;

class AptGet
{
    var $cli;
    var $files;

    /**
     * Create a new AptGet instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Determine if the given formula is installed.
     *
     * @param  string  $formula
     * @return bool
     */
    function installed($formula)
    {
        return in_array($formula, explode(PHP_EOL, $this->cli->run('dpkg --get-selections | grep '.$formula)));
    }

    /**
     * Determine if a compatible PHP version is installed.
     *
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->installed('php7.0') || $this->installed('php5.6');
    }

    /**
     * Ensure that the given formula is installed.
     *
     * @param  string  $formula
     * @param  array  $taps
     * @return void
     */
    function ensureInstalled($formula, array $taps = [])
    {
        if (! $this->installed($formula)) {
            $this->installOrFail($formula, $taps);
        }
    }

    /**
     * Install the given formula and throw an exception on failure.
     *
     * @param  string  $formula
     * @param  array  $taps
     * @return void
     */
    function installOrFail($formula, array $taps = [])
    {
        if (count($taps) > 0) {
            $this->tap($taps);
        }

        output('<info>['.$formula.'] is not installed, installing it now via apt-get...</info> 🍻');

        $this->cli->run('apt-get install '.$formula, function ($errorOutput) use ($formula) {
            output($errorOutput);

            throw new DomainException('apt-get was unable to install ['.$formula.'].');
        });
    }

    /**
     * Tag the given formulas.
     *
     * @param  dynamic[string]  $formula
     * @return void
     */
    function tap($formulas)
    {
        $formulas = is_array($formulas) ? $formulas : func_get_args();

        foreach ($formulas as $formula) {
            $this->cli->passthru('sudo -u '.user().' apt-get install '.$formula);
        }
    }

    /**
     * Restart the given service
     *
     * @param
     */
    function restartService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo service '.$service . ' restart');
        }
    }

    /**
     * Stop the given service
     *
     * @param
     */
    function stopService($services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $this->cli->quietly('sudo service '.$service . ' stop');
        }
    }

    /**
     * Determine which version of PHP is linked on the system.
     *
     * @return string
     */
    function linkedPhp()
    {
        if (! $this->files->isLink('/usr/bin/php')) {
            throw new DomainException("Unable to determine linked PHP.");
        }

        $resolvedPath = $this->files->readLink('/etc/alternatives/php');

        if (strpos($resolvedPath, 'php7.0') !== false) {
            return 'php7.0-fpm';
        } elseif (strpos($resolvedPath, 'php5.6') !== false) {
            return 'php56';
        } else {
            throw new DomainException("Unable to determine linked PHP.");
        }
    }

    /**
     * Restart the linked PHP-FPM service.
     *
     * @return void
     */
    function restartLinkedPhp()
    {
        return $this->restartService($this->linkedPhp());
    }
}
