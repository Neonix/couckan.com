<?php
use \Workerman\Worker;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

require_once __DIR__ . '/../../vendor/autoload.php';

// bussinessWorker
$worker = new BusinessWorker();
// worker
$worker->name = 'ChatBusinessWorker';
// bussinessWorker
$worker->count = 4;
$worker->registerAddress = '127.0.0.1:1236';

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

