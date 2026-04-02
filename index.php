<?php

use App\Infrastructure\Kernel;
use Symfony\Component\HttpFoundation\Request;

require 'vendor/autoload.php';
$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? $env !== 'prod');

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
