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

declare(strict_types=1);

use Blackfire\Client;
use Blackfire\ClientConfiguration;
use OAT\SimpleRoster\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();

$isBlackfireProfilingRequested =
    $_ENV['BLACKFIRE_ENABLED'] == true
    && $request->headers->has('X-Blackfire');

if ($isBlackfireProfilingRequested) {
    $config = new ClientConfiguration(
        $_ENV['BLACKFIRE_CLIENT_ID'],
        $_ENV['BLACKFIRE_CLIENT_TOKEN']
    );
    $blackfire = new Client($config);
    $probe = $blackfire->createProbe();
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

if ($isBlackfireProfilingRequested) {
    $blackfire->endProbe($probe);
}
