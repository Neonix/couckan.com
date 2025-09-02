<?php

$_config = [
    'docker'   => false,
    'ssl'      => true,
    'localdev' => true,
    'geoip'    => false,
];

// STUN/TURN servers used by WebRTC
// Couckan self-hosted TURN server is exposed via nginx on 5349 or proxied on /turn for port 443-only environments
$_config['ice_servers'] = [
    [
        'urls' => $_config['localdev'] ? 'stun:localhost:5349' : 'stun:couckan.com/turn'
    ],
    [
        'urls' => $_config['localdev'] ? 'turns:localhost:5349?transport=tcp' : 'turns:couckan.com/turn?transport=tcp',
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

