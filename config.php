<?php
// 信令服务器(Signaling Server)地址，需要用wss协议，并且必须是域名



$_config = array(
    'docker' => false,
    'ssl' => true,
    'localdev' => true,
    'geoip' => false
);

//if($_config['docker'])
//    $SIGNALING_ADDRESS = 
$SIGNALING_ADDRESS = 'wss://couckan.com/signal';
$SIGNALING_ADDRESS = 'wss://localhost/signal';

/***
 * Exemple de configuation SSL
 *
 */ 

$SSL_CONTEXT = array(
    'ssl' => array(
        //local_cert.crt, local_pk.key
        'local_cert'        => '/etc/letsencrypt/live/couckan.com/fullchain.pem',
        'local_pk'          => '/etc/letsencrypt/live/couckan.com/privkey.pem',
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    )
);




$SSL_CONTEXT = array(

    'ssl' => array(
        //local_cert.crt, local_pk.key
        'local_cert'        => 'localhost.crt',
        'local_pk'          => 'localhost.key',
        'verify_peer'       => false,
        'allow_self_signed' => true,
    )
);



/*
$SSL_CONTEXT = array(
    // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
    'ssl' => array(
        // 请使用绝对路径
//        'local_cert'        => '/etc/ssl/couckan.crt', // 也可以是crt文件
//        'local_pk'          => '/etc/ssl/couckan.key',
	'local_cert'  => __DIR__.'/fullchain.pem',
    	'local_pk'    => __DIR__.'/privkey.pem',
        'verify_peer'       => false,
        'allow_self_signed' => true, //如果是自签名证书需要开启此选项
    )
);
*/
