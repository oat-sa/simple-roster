<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (is_array($env = @include dirname(__DIR__) . '/.env.local.php') && ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV']) === $env['APP_ENV']) {
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_ENV[$k] ?? (isset($_SERVER[$k]) && 0 !== strpos($k, 'HTTP_') ? $_SERVER[$k] : $v);
    }
} elseif (!class_exists(Dotenv::class)) {
    throw new RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} else {
    $path = dirname(__DIR__) . '/.env';
    $dotenv = new Dotenv(false);

    // load all the .env files
    if (method_exists($dotenv, 'loadEnv')) {
        $dotenv->loadEnv($path);
    } else {
        // fallback code in case your Dotenv component is not 4.2 or higher (when loadEnv() was added)

        if (file_exists($path) || !file_exists($p = "$path.dist")) {
            $dotenv->load($path);
        } else {
            $dotenv->load($p);
        }

        if (null === $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) {
            $dotenv->populate(['APP_ENV' => $env = 'dev']);
        }

        if ('test' !== $env && file_exists($p = "$path.local")) {
            $dotenv->load($p);
            $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env;
        }

        if (file_exists($p = "$path.$env")) {
            $dotenv->load($p);
        }

        if (file_exists($p = "$path.$env.local")) {
            $dotenv->load($p);
        }
    }
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int)$_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
