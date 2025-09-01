<?php
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;
use Workerman\Protocols\Http\Response;


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';




if (isset($SSL_CONTEXT) && $_config['ssl'] && !$_config['docker']) {
    $api = new Worker('http://0.0.0.0:8002', $SSL_CONTEXT);
    $api->transport = 'ssl';
} else {
    // WebServer
    $api = new Worker('http://0.0.0.0:8002');
}




$api->onMessage = function ($connection, $request) {
    global $_config;

    $headers = [
        'Content-Type'              => 'application/json; charset=utf-8',
        'Cache-Control'             => 'no-cache',
        // CORS (optionnel)
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
    ];

    $body = json_encode($_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $connection->send(new Response(200, $headers, $body));
};

