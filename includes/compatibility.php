<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS != 'Linux' && ! $inTestingEnvironment && php_uname('m') === 'x86_64' && strpos($php_uname('m'), 'Ubuntu') !== false)
) {
    echo 'Valet-Ubuntu only supports the Ubuntu operating system with 64-Bit.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    echo "Valet requires PHP 5.5.9 or later.";

    exit(1);
}

if (exec('which apt-get') != '/usr/bin/apt-get' && ! $inTestingEnvironment) {
    echo 'Valet-Ubuntu requires apt-get to be installed on your Ubuntu machine.';

    exit(1);
}
