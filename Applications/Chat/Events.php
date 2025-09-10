<?php
use \GatewayWorker\Lib\Gateway;
require_once __DIR__ . '/ChatDb.php';

/**
 * Démo sans persistance : on diffuse des événements (login/status/new_room/room_closed/say).
 * Pour des ACL strictes (créateur de room, accès, persistance), brancher sur DB/Redis.
 */
class Events
{
    public static $locations = [];

    public static function onMessage($client_id, $message)
    {
        $data = json_decode($message, true);
        if(!$data) return;

        switch($data['type'] ?? '') {
            case 'pong':
                return;

            /**
             * Connexion à une room (publique ou privée)
             *  - envoie à TOUS de la room: "login" (avec client_list synchronisée)
             *  - envoie AU CLIENT: "welcome" (self_id + client_list)
             */
            case 'login': {
                $room_id     = $data['room_id']    ?? 'general';
                $client_name = htmlspecialchars($data['client_name'] ?? 'Invité');
                $status      = $data['status']     ?? 'online';
                $ua          = $data['ua']         ?? '';
                $client_uuid = $data['client_uuid'] ?? bin2hex(random_bytes(8));

                // Remplit la session avant joinGroup pour que getClientSessionsByGroup voie status/name
                $_SESSION['room_id']     = $room_id;
                $_SESSION['client_name'] = $client_name;
                $_SESSION['status']      = $status;
                $_SESSION['client_uuid'] = $client_uuid;

                Gateway::joinGroup($client_id, $room_id);
                Gateway::bindUid($client_id, $client_uuid);

                // Liste synchronisée des clients de la room
                $client_list = [];
                $sessions = Gateway::getClientSessionsByGroup($room_id);
                foreach ($sessions as $id => $s) {
                    $uid = $s['client_uuid'] ?? $id;
                    $client_list[$uid] = [
                        'name'   => $s['client_name'] ?? 'Invité',
                        'status' => $s['status']      ?? 'online',
                    ];
                }

                // Diffuse "login" (avec liste complète) à la room
                $login_msg = [
                    'type'        => 'login',
                    'client_id'   => $client_uuid,
                    'client_name' => $client_name,
                    'room_id'     => $room_id,
                    'status'      => $status,
                    'client_list' => $client_list,
                    'time'        => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToGroup($room_id, json_encode($login_msg));

                // Répond au client courant avec son id + la liste
                $welcome_msg = [
                    'type'        => 'welcome',
                    'self_id'     => $client_uuid,
                    'room_id'     => $room_id,
                    'client_list' => $client_list,
                    'time'        => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToClient($client_id, json_encode($welcome_msg));

                $limit = ($room_id === 'general') ? 10 : 50;
                $history = ChatDb::getMessages($room_id, $limit);
                Gateway::sendToClient($client_id, json_encode([
                    'type'     => 'history',
                    'room_id'  => $room_id,
                    'messages' => $history
                ]));

                // Position par défaut proche de Null Island tant que l'utilisateur
                // n'a pas partagé sa véritable localisation
                $randLat = mt_rand(-50, 50) / 1000; // +/-0.05°
                $randLon = mt_rand(-50, 50) / 1000;

                // stocke la position dans la session pour la partager entre les workers
                $_SESSION['lat']  = $randLat;
                $_SESSION['lon']  = $randLon;
                $_SESSION['real'] = false; // localisation par défaut proche de Null Island

                self::$locations[$client_uuid] = [
                    'client_id'   => $client_uuid,
                    'client_name' => $client_name,
                    'lat'         => $randLat,
                    'lon'         => $randLon,
                    'real'        => false,
                ];
                $loc = self::$locations[$client_uuid];
                $loc['type'] = 'location';
                Gateway::sendToAll(json_encode($loc));

                // récupère les positions de tous les clients connectés
                $all_sessions = Gateway::getAllClientSessions();
                $locations = [];
                foreach ($all_sessions as $id => $sess) {
                    if (!isset($sess['lat'], $sess['lon'])) {
                        continue;
                    }
                    $uid = $sess['client_uuid'] ?? $id;
                    $locations[] = [
                        'client_id'   => $uid,
                        'client_name' => $sess['client_name'] ?? 'Invité',
                        'lat'         => $sess['lat'],
                        'lon'         => $sess['lon'],
                        'real'        => $sess['real'] ?? false,
                    ];
                }

                Gateway::sendToClient($client_id, json_encode([
                    'type'      => 'locations',
                    'locations' => $locations
                ]));

                ChatDb::logRequest('ws_login', $ua, '');
                return;
            }

            /**
             * Changement de statut (online/busy/away)
             */
            case 'status': {
                $new = $data['status'] ?? 'online';
                $_SESSION['status'] = $new;
                $room_id = $_SESSION['room_id'] ?? 'general';
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                $msg = [
                    'type'      => 'status',
                    'client_id' => $uuid,
                    'status'    => $new,
                ];
                Gateway::sendToGroup($room_id, json_encode($msg));
                return;
            }

            case 'rename': {
                $new = htmlspecialchars(trim($data['client_name'] ?? ''));
                if ($new === '') $new = 'Invité';
                $_SESSION['client_name'] = $new;
                $room_id = $_SESSION['room_id'] ?? 'general';
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                $msg = [
                    'type'       => 'rename',
                    'client_id'  => $uuid,
                    'client_name'=> $new,
                ];
                Gateway::sendToGroup($room_id, json_encode($msg));
                if (isset(self::$locations[$uuid])) {
                    self::$locations[$uuid]['client_name'] = $new;
                    $loc = self::$locations[$uuid];
                    $loc['type'] = 'location';
                    Gateway::sendToAll(json_encode($loc));
                }
                return;
            }

            case 'location': {
                $lat = $data['lat'] ?? null;
                $lon = $data['lon'] ?? null;
                if ($lat === null || $lon === null) return;
                $client_name = $_SESSION['client_name'] ?? 'Invité';
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                self::$locations[$uuid] = [
                    'client_id'   => $uuid,
                    'client_name' => $client_name,
                    'lat'         => (float)$lat,
                    'lon'         => (float)$lon,
                    'real'        => true,
                ];
                $_SESSION['lat']  = (float)$lat;
                $_SESSION['lon']  = (float)$lon;
                $_SESSION['real'] = true;
                $msg = self::$locations[$uuid];
                $msg['type'] = 'location';
                Gateway::sendToAll(json_encode($msg));
                return;
            }

            case 'location_remove': {
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                if (isset(self::$locations[$uuid])) {
                    unset(self::$locations[$uuid]);
                    unset($_SESSION['lat'], $_SESSION['lon']);
                    $_SESSION['real'] = false;
                    Gateway::sendToAll(json_encode(['type'=>'location_remove','client_id'=>$uuid]));
                }
                return;
            }

            /**
             * Création d'une room.
             *  - publique  => "new_room" broadcast (apparait pour tout le monde)
             *  - privée    => "room_created" seulement au créateur (accès via lien)
             */
            case 'create_room': {
                $room_id    = $data['room_id']    ?? null;
                $visibility = $data['visibility'] ?? 'public';
                if(!$room_id) return;

                $payload = [
                    'room_id'      => $room_id,
                    'visibility'   => $visibility,
                    'creator_id'   => $client_id,
                    'creator_name' => $_SESSION['client_name'] ?? 'Invité',
                ];

                if ($visibility === 'public') {
                    $payload['type'] = 'new_room';
                    Gateway::sendToAll(json_encode($payload));
                } else {
                    $payload['type'] = 'room_created';
                    Gateway::sendToClient($client_id, json_encode($payload));
                }
                return;
            }

            /**
             * Fermeture d'une room par son créateur (démo : confiance côté client).
             */
            case 'close_room': {
                $room_id    = $data['room_id']    ?? null;
                $creator_id = $data['creator_id'] ?? null;
                if(!$room_id || !$creator_id) return;

                if ($creator_id != $client_id) {
                    // En prod : vérifier côté DB/ACL
                    return;
                }
                $msg = [
                    'type'    => 'room_closed',
                    'room_id' => $room_id,
                ];
                Gateway::sendToAll(json_encode($msg));
                return;
            }

            /**
             * "Wizz" : notifie un utilisateur ciblé.
             */
            case 'wizz': {
                $to = $data['to'] ?? null;
                if (!$to) return;
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                $msg = [
                    'type'      => 'wizz',
                    'from'      => $uuid,
                ];
                Gateway::sendToUid($to, json_encode($msg));
                return;
            }

            /**
             * Invitation à rejoindre une room WebRTC.
             */
            case 'call_invite': {
                $to    = $data['to']    ?? null;
                $room  = $data['room']  ?? null;
                $video = !empty($data['video']);
                if(!$to || !$room) return;
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                $msg = [
                    'type'  => 'call_invite',
                    'from'  => $uuid,
                    'room'  => $room,
                    'video' => $video,
                ];
                Gateway::sendToUid($to, json_encode($msg));
                return;
            }

            /**
             * Historique des messages privés avec un utilisateur.
             */
            case 'dm_history': {
                $to = $data['to_client_id'] ?? null;
                if(!$to) return;
                $uuid = $_SESSION['client_uuid'] ?? $client_id;
                $room_key = 'dm:' . min($uuid, $to) . ':' . max($uuid, $to);
                $history = ChatDb::getMessages($room_key, 50);
                foreach ($history as &$m) {
                    $m['dm'] = true;
                }
                unset($m);
                Gateway::sendToClient($client_id, json_encode([
                    'type'     => 'history',
                    'messages' => $history,
                ]));
                return;
            }

            /**
             * Message :
             *  - DM : envoie à l'autre ET au sender (une seule fois chacun)
             *  - Room : broadcast à la room courante
             */
            case 'say': {
                $room_id     = $_SESSION['room_id'] ?? 'general';
                $client_name = $_SESSION['client_name'] ?? 'Invité';
                $content     = nl2br(htmlspecialchars($data['content'] ?? ''));
                $uuid        = $_SESSION['client_uuid'] ?? $client_id;

                // DM
                if (!empty($data['to_client_id']) && $data['to_client_id'] !== 'all') {
                    $to_id = $data['to_client_id'];
                    $msg = [
                        'type'             => 'say',
                        'from_client_id'   => $uuid,
                        'from_client_name' => $client_name,
                        'to_client_id'     => $to_id,
                        'content'          => $content,
                        'time'             => date('Y-m-d H:i:s'),
                        'dm'               => true
                    ];
                    Gateway::sendToUid($to_id, json_encode($msg));
                    Gateway::sendToUid($uuid, json_encode($msg));
                    $room_key = 'dm:' . min($uuid, $to_id) . ':' . max($uuid, $to_id);
                    ChatDb::logMessage($room_key, (string)$uuid, $client_name, (string)$to_id, $content);
                    return;
                }

                // Room
                $msg = [
                    'type'             => 'say',
                    'from_client_id'   => $uuid,
                    'from_client_name' => $client_name,
                    'to_client_id'     => 'all',
                    'room_id'          => $room_id,
                    'content'          => $content,
                    'time'             => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToGroup($room_id, json_encode($msg));
                ChatDb::logMessage($room_id, (string)$uuid, $client_name, 'all', $content);
                return;
            }
        }
    }

    public static function onClose($client_id)
    {
        $uuid = $_SESSION['client_uuid'] ?? $client_id;
        if(isset($_SESSION['room_id'])) {
            $room_id = $_SESSION['room_id'];
            $msg = [
                'type'             => 'logout',
                'from_client_id'   => $uuid,
                'from_client_name' => $_SESSION['client_name'] ?? 'Invité',
                'time'             => date('Y-m-d H:i:s'),
            ];
            Gateway::sendToGroup($room_id, json_encode($msg));
        }
        if (isset(self::$locations[$uuid])) {
            unset(self::$locations[$uuid]);
            Gateway::sendToAll(json_encode(['type'=>'location_remove','client_id'=>$uuid]));
        }
    }
}
