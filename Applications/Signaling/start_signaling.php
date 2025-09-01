<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use Workerman\Worker;

// Map of room id to connections
$subject_connnection_map = [];

// Create websocket server for signaling
if (isset($SSL_CONTEXT) && $_config['ssl'] && !$_config['docker']) {
    $signal = new Worker('websocket://0.0.0.0:8877', $SSL_CONTEXT);
    $signal->transport = 'ssl';
} else {
    $signal = new Worker('websocket://0.0.0.0:8877');
}

// Single process is enough for signaling
$signal->count = 1;
$signal->name  = 'Signaling Server';

// Keep track of rooms subscribed by this connection
$signal->onConnect = function ($connection) {
    $connection->subjects = [];
};

$signal->onMessage = function ($connection, $data) {
    $data = json_decode($data, true);
    switch ($data['cmd'] ?? '') {
        case 'register':
            $subject = (int) ($data['roomid'] ?? 0);
            subscribe($subject, $connection);
            break;
        case 'send':
            $subject = (int) ($data['roomid'] ?? 0);
            $payload = $data['msg'] ?? null;
            publish($subject, null, $payload, $connection);
            break;
    }
};

$signal->onClose = function ($connection) {
    destroy_connection($connection);
};

function subscribe($subject, $connection) {
    global $subject_connnection_map;
    $connection->subjects[$subject] = $subject;
    $subject_connnection_map[$subject][$connection->id] = $connection;
}

function unsubscribe($subject, $connection) {
    global $subject_connnection_map;
    unset($subject_connnection_map[$subject][$connection->id]);
    if (empty($subject_connnection_map[$subject])) {
        unset($subject_connnection_map[$subject]);
    }
}

function publish($subject, $event, $data, $exclude) {
    global $subject_connnection_map;
    if (empty($subject_connnection_map[$subject])) {
        return;
    }
    foreach ($subject_connnection_map[$subject] as $connection) {
        if ($connection === $exclude) {
            continue;
        }
        $connection->send(json_encode([
            'cmd' => 'send',
            'msg' => $data,
        ]));
    }
}

function destroy_connection($connection) {
    foreach ($connection->subjects as $subject) {
        unsubscribe($subject, $connection);
    }
}

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
