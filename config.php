<?php

$_config = [
    'docker'   => false,
    'ssl'      => true,
    'localdev' => true,
    'geoip'    => false,
];

$scheme = $_config['ssl'] ? 'wss://' : 'ws://';
$host   = $_config['localdev'] ? 'localhost' : 'couckan.com';
$SIGNALING_ADDRESS = $scheme . $host . '/signal';

$SSL_CONTEXT = [
    'ssl' => [
        'local_cert'       => $_config['localdev'] ? 'localhost.crt' : '/etc/letsencrypt/live/couckan.com/fullchain.pem',
        'local_pk'         => $_config['localdev'] ? 'localhost.key' : '/etc/letsencrypt/live/couckan.com/privkey.pem',
        'verify_peer'      => false,
        'verify_peer_name' => false,
        'allow_self_signed'=> true,
    ],
];

