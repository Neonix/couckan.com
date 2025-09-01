<?php 
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;
use Workerman\Protocols\Http\Response;


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/ChatDb.php';



if (isset($SSL_CONTEXT) && $_config['ssl'] && !$_config['docker']) {
    $web = new Worker("http://0.0.0.0:443", $SSL_CONTEXT);
    $web->transport = 'ssl';
} else {
    // WebServer
    $web = new Worker("http://0.0.0.0:55151");
}

//$web->reloadable = true; // REload debug
$web->count = 2;

define('WEBROOT', __DIR__ . DIRECTORY_SEPARATOR .  'Web');
define('APIROOT', __DIR__ . DIRECTORY_SEPARATOR .  'Api');

$web->onMessage = function (TcpConnection $connection, Request $request) {
    ChatDb::logRequest($request->path(), $request->header('user-agent', ''), $connection->getRemoteIp());
    $_GET = $request->get();
    $path = $request->path();
    if ($path === '/') {
        $connection->send(exec_php_file(WEBROOT.'/index.php'));
        return;
    }
    $file = realpath(WEBROOT. $path);
    if (false === $file) {
        $connection->send(new Response(404, array(), '<h3>404 Not Found</h3>'));
        return;
    }
    // Security check! Very important!!!
    if (strpos($file, WEBROOT) !== 0) {
        $connection->send(new Response(400));
        return;
    }
    if (\pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $connection->send(exec_php_file($file));
        return;
    }

    $if_modified_since = $request->header('if-modified-since');
    if (!empty($if_modified_since)) {
        // Check 304.
        $info = \stat($file);
        $modified_time = $info ? \date('D, d M Y H:i:s', $info['mtime']) . ' ' . \date_default_timezone_get() : '';
        if ($modified_time === $if_modified_since) {
            $connection->send(new Response(304));
            return;
        }
    }
    $connection->send((new Response())->withFile($file));
};

function exec_php_file($file) {
    \ob_start();
    try {
        include $file;
    } catch (\Exception $e) {
        echo $e;
    }
    return \ob_get_clean();
}


if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

