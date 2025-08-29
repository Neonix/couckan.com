<?php 
use \Workerman\Worker;
use \GatewayWorker\Register;

require_once __DIR__ . '/../../vendor/autoload.php';

$register = new Register('text://127.0.0.1:1236');

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

