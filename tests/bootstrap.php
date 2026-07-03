<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Polyfill\Php84\Php84;

require dirname(__DIR__).'/vendor/autoload.php';

// TEMP DIAGNOSTIC (bcround 調査)
fwrite(STDERR, sprintf(
    "[DIAG bootstrap] Php84::bcround=%d file=%s global_bcround=%s bcround(2.5)=%s\n",
    method_exists(Php84::class, 'bcround') ? 1 : 0,
    (new ReflectionClass(Php84::class))->getFileName(),
    function_exists('bcround') ? (new ReflectionFunction('bcround'))->getFileName().':'.(new ReflectionFunction('bcround'))->getStartLine() : 'nofunc',
    function_exists('bcround') ? @bcround('2.5') : 'nofunc'
));

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
