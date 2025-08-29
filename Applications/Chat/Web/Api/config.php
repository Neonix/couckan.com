<?php

// Applications/Chat/Web/JSON/config.php
declare(strict_types=1);

include __DIR__ . '/../../../../config.php';

// Détermine ws:// ou wss:// en fonction du schéma HTTP courant
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'wss' : 'ws';
// Hôte courant (ex: 127.0.0.1:55151) => on garde juste le host
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
$host = preg_replace('~:\d+$~', '', $host); // strip port

$config = [
    'app'     => 'workerman-chat',
    'env'     => getenv('APP_ENV') ?: 'prod',
    'version' => '1.0.0',
    'ws'      => [
        // Port WebSocket par défaut du projet : 7272
        'url' => sprintf('%s://%s:%d', $scheme, $host, 7272),
        'roomDefault' => 1,
    ],
    // Exemple d’options UI côté front
    'ui'      => [
        'emotions' => true,
        'maxMsg'   => 200
    ],
];

$body = json_encode($_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// En‐têtes HTTP
header('Content-Type: application/json; charset=utf-8');
// Côté dev: pas de cache. (En prod, mettez plutôt un ETag/max-age)
header('Cache-Control: no-store');

// Décommentez si la page web est servie d’un autre domaine/port
// header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
//echo $body;
