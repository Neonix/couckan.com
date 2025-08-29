<?php 
use \Workerman\Worker;
use \GatewayWorker\Gateway;
use \Workerman\Autoloader;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';


//Gateway
if (isset($SSL_CONTEXT)) {
    $gateway = new Gateway("Websocket://0.0.0.0:7272", $SSL_CONTEXT);
    $gateway->transport = 'ssl';
} else {
    //Socket
    $gateway = new Gateway("Websocket://0.0.0.0:7272");
}

$gateway->name = 'ChatGateway';
$gateway->count = 2;
$gateway->lanIp = '127.0.0.1';
$gateway->startPort = 2300;
$gateway->pingInterval = 10;
$gateway->pingData = '{"type":"ping"}';
$gateway->registerAddress = '127.0.0.1:1236';



if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

