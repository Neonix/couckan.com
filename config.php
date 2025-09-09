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
        // Public TURN server provided by the Open Relay project for NAT traversal
        [
            'urls' => [
                'turn:global.relay.metered.ca:80',
                'turn:global.relay.metered.ca:80?transport=tcp',
                'turn:global.relay.metered.ca:443',
                'turns:global.relay.metered.ca:443?transport=tcp'
            ],
            'username' => 'openrelayproject',
            'credential' => 'openrelayproject'
        ],
    ],
];

$scheme = $_config['ssl'] ? 'wss://' : 'ws://';
$host   = $_config['localdev'] ? 'localhost' : 'couckan.com';
if ($_config['localdev']) {
    // In local development we connect directly to the signaling port
    $SIGNALING_ADDRESS = $scheme . $host . ':8877';
} else {
    // In production the signaling server is proxied behind /signal
    $SIGNALING_ADDRESS = $scheme . $host . '/signal';
}

$SSL_CONTEXT = [
    'ssl' => [
        'local_cert'       => $_config['localdev'] ? 'localhost.crt' : '/etc/letsencrypt/live/couckan.com/fullchain.pem',
        'local_pk'         => $_config['localdev'] ? 'localhost.key' : '/etc/letsencrypt/live/couckan.com/privkey.pem',
        'verify_peer'      => false,
        'verify_peer_name' => false,
        'allow_self_signed'=> true,
    ],
];

