<?php
# Config Prod Docker:
#    'docker'   => true,
#    'ssl'      => false,
#    'localdev' => false,

# Config Dev - sudo php start.php start:
#    'docker'   => false,
#    'ssl'      => true,
#    'localdev' => true,

$_config = [
    'docker'   => false,
    'ssl'      => true,
    'localdev' => true,
    'geoip'    => false,
    // Default STUN/TURN servers used by WebRTC
    // Additional TURN servers can be added here to ensure connectivity
    // between peers behind restrictive NATs.
    'ice_servers' => [
        ['urls' => 'stun:stun.l.google.com:19302'],
        // Example TURN configuration (replace with your own server)
        // ['urls' => 'turn:turn.example.com:3478', 'username' => 'user', 'credential' => 'pass'],
    ],
];

$scheme = $_config['ssl'] ? 'wss://' : 'ws://';
$host   = $_config['localdev'] ? 'localhost' : 'couckan.com';
$SIGNALING_ADDRESS = 'wss://' . $host . '/signal';

$SSL_CONTEXT = [
    'ssl' => [
        'local_cert'       => $_config['localdev'] ? 'localhost.crt' : '/etc/letsencrypt/live/couckan.com/fullchain.pem',
        'local_pk'         => $_config['localdev'] ? 'localhost.key' : '/etc/letsencrypt/live/couckan.com/privkey.pem',
        'verify_peer'      => false,
        'verify_peer_name' => false,
        'allow_self_signed'=> true,
    ],
];

