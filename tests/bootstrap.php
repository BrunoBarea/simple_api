<?php

use Symfony\Component\Dotenv\Dotenv;

date_default_timezone_set('UTC');

require dirname(__DIR__).'/vendor/autoload.php';

if (!class_exists(Dotenv::class)) {
    throw new RuntimeException('Dotenv component is required to load environment variables.');
}

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env.test');
