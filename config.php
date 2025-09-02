<?php

$_config = [
    'docker'   => false,
    'ssl'      => true,
    'localdev' => true,
    'geoip'    => false,
];

// STUN/TURN servers used by WebRTC
// Couckan self-hosted TURN server exposed via nginx
$_config['ice_servers'] = [
    [
        'urls' => $_config['localdev'] ? 'stun:localhost:3478' : 'stun:couckan.com:3478',
    ],
    [
        'urls' => $_config['localdev'] ? 'turns:localhost:5349?transport=tcp' : 'turns:couckan.com:5349?transport=tcp',
        'username' => 'couckan',
        'credential' => 'couckan',
    ],
    // ['urls' => 'stun:stun.l.google.com:19302'], // Google STUN as fallback
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

