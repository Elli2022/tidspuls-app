<?php

declare(strict_types=1);

/**
 * PHPUnit måste ha APP_KEY innan Laravel boots — utan att lägga en nyckel i git.
 * Genereras slumpmässigt vid varje testkörning.
 */
if (empty($_SERVER['APP_KEY'] ?? '') && empty($_ENV['APP_KEY'] ?? getenv('APP_KEY'))) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    putenv('APP_KEY='.$key);
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
}

require dirname(__DIR__).'/vendor/autoload.php';
