<?php
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;
use Workerman\Protocols\Http\Response;


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../Chat/ChatDb.php';




if (isset($SSL_CONTEXT) && $_config['ssl'] && !$_config['docker']) {
    $api = new Worker('http://0.0.0.0:8002', $SSL_CONTEXT);
    $api->transport = 'ssl';
} else {
    // WebServer
    $api = new Worker('http://0.0.0.0:8002');
}




$api->onMessage = function ($connection, $request) {
    global $_config;

    $headers = [
        'Content-Type'              => 'application/json; charset=utf-8',
        'Cache-Control'             => 'no-cache',
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
    ];

    $path = $request->path();
    $method = strtoupper($request->method());

    if ($path === '/announce') {
        if ($method === 'OPTIONS') {
            $connection->send(new Response(204, $headers, ''));
            return;
        }
        if ($method === 'POST') {
            $data = json_decode($request->rawBody(), true) ?: [];
            $title = trim($data['title'] ?? '');
            $description = trim($data['description'] ?? '');
            $lat = isset($data['latitude']) ? (float)$data['latitude'] : null;
            $lng = isset($data['longitude']) ? (float)$data['longitude'] : null;
            $range = null;
            if (isset($data['range'])) {
                $range = (float)$data['range'];
            } elseif (isset($data['radius'])) {
                $range = (float)$data['radius'];
            }
            $contact = isset($data['contact']) ? (string)$data['contact'] : null;
            $area = isset($data['area']) ? (string)$data['area'] : null;
            $landmarks = isset($data['landmarks']) ? (string)$data['landmarks'] : null;
            $allowed_keywords = null;
            if (isset($data['allowed_keywords'])) {
                if (is_array($data['allowed_keywords'])) {
                    $allowed_keywords = implode(',', $data['allowed_keywords']);
                } else {
                    $allowed_keywords = (string)$data['allowed_keywords'];
                }
            }
            if ($title === '' || $description === '' || ($lat === null && $area === null)) {
                $body = json_encode(['error' => 'Invalid payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $connection->send(new Response(400, $headers, $body));
                return;
            }
            $is_offline = isset($data['is_offline']) ? (bool)$data['is_offline'] : true;
            $auto_reply = isset($data['auto_reply']) ? (string)$data['auto_reply'] : null;
            $notify_interval = isset($data['notify_interval']) ? (string)$data['notify_interval'] : null;
            $visible_until = isset($data['visible_until']) ? (string)$data['visible_until'] : null;
            $is_anonymous = isset($data['is_anonymous']) ? (bool)$data['is_anonymous'] : false;
            $id = ChatDb::addAnnouncement($title, $description, $lat, $lng, $range, $contact, $area, $landmarks, $allowed_keywords, $is_offline, $auto_reply, $notify_interval, $visible_until, $is_anonymous);
            $body = json_encode(['status' => 'ok', 'id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        if ($method === 'GET') {
            $announces = ChatDb::getAnnouncements();
            foreach ($announces as &$a) {
                if (!empty($a['is_anonymous'])) {
                    unset($a['contact']);
                }
            }
            $body = json_encode($announces, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        $connection->send(new Response(405, $headers, json_encode(['error' => 'Method Not Allowed'])));
        return;
    }

    if (preg_match('#^/announce/(\d+)$#', $path, $m)) {
        $announcementId = (int)$m[1];
        if ($method === 'OPTIONS') {
            $connection->send(new Response(204, $headers, ''));
            return;
        }
        if ($method === 'GET') {
            $announcement = ChatDb::getAnnouncement($announcementId);
            if (!$announcement) {
                $body = json_encode(['error' => 'Not Found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $connection->send(new Response(404, $headers, $body));
                return;
            }
            if (!empty($announcement['is_anonymous'])) {
                unset($announcement['contact']);
            }
            $body = json_encode($announcement, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        $connection->send(new Response(405, $headers, json_encode(['error' => 'Method Not Allowed'])));
        return;
    }

    if (preg_match('#^/announce/(\d+)/messages$#', $path, $m)) {
        $announcementId = (int)$m[1];
        if ($method === 'OPTIONS') {
            $connection->send(new Response(204, $headers, ''));
            return;
        }
        if ($method === 'POST') {
            $data = json_decode($request->rawBody(), true) ?: [];
            $content = trim($data['content'] ?? '');
            if ($content === '') {
                $body = json_encode(['error' => 'Invalid payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $connection->send(new Response(400, $headers, $body));
                return;
            }
            $from_contact = isset($data['from_contact']) ? (string)$data['from_contact'] : null;
            $rating = null;
            if (isset($data['rating'])) {
                $rating = (int)$data['rating'];
                if ($rating < 1 || $rating > 5) {
                    $body = json_encode(['error' => 'Invalid rating'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $connection->send(new Response(400, $headers, $body));
                    return;
                }
            }
            $announcement = ChatDb::getAnnouncement($announcementId);
            if ($announcement && !empty($announcement['allowed_keywords'])) {
                $allowed = array_map('trim', explode(',', $announcement['allowed_keywords']));
                $allowed = array_filter($allowed, 'strlen');
                $found = false;
                foreach ($allowed as $kw) {
                    if (stripos($content, $kw) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $body = json_encode(['error' => 'Message not allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $connection->send(new Response(403, $headers, $body));
                    return;
                }
            }
            ChatDb::addAnnouncementMessage($announcementId, $from_contact, $content, $rating);
            $resp = ['status' => 'ok'];
            if ($announcement && $announcement['auto_reply']) {
                $resp['auto_reply'] = $announcement['auto_reply'];
            }
            $body = json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        if ($method === 'GET') {
            $messages = ChatDb::getAnnouncementMessages($announcementId);
            $body = json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        $connection->send(new Response(405, $headers, json_encode(['error' => 'Method Not Allowed'])));
        return;
    }

    if (preg_match('#^/announce/(\d+)/schedule$#', $path, $m)) {
        $announcementId = (int)$m[1];
        if ($method === 'OPTIONS') {
            $connection->send(new Response(204, $headers, ''));
            return;
        }
        if ($method === 'POST') {
            $data = json_decode($request->rawBody(), true) ?: [];
            $content = trim($data['content'] ?? '');
            $send_at = trim($data['send_at'] ?? '');
            if ($content === '' || $send_at === '') {
                $body = json_encode(['error' => 'Invalid payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $connection->send(new Response(400, $headers, $body));
                return;
            }
            ChatDb::scheduleAnnouncementMessage($announcementId, $content, $send_at);
            $body = json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        if ($method === 'GET') {
            $messages = ChatDb::getScheduledAnnouncementMessages($announcementId);
            $body = json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->send(new Response(200, $headers, $body));
            return;
        }
        $connection->send(new Response(405, $headers, json_encode(['error' => 'Method Not Allowed'])));
        return;
    }

    $body = json_encode($_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $connection->send(new Response(200, $headers, $body));
};

