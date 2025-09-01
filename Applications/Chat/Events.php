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

                // Remplit la session avant joinGroup pour que getClientSessionsByGroup voie status/name
                $_SESSION['room_id']     = $room_id;
                $_SESSION['client_name'] = $client_name;
                $_SESSION['status']      = $status;

                Gateway::joinGroup($client_id, $room_id);

                // Liste synchronisée des clients de la room
                $client_list = [];
                $sessions = Gateway::getClientSessionsByGroup($room_id);
                foreach ($sessions as $id => $s) {
                    $client_list[$id] = [
                        'name'   => $s['client_name'] ?? 'Invité',
                        'status' => $s['status']      ?? 'online',
                    ];
                }

                // Diffuse "login" (avec liste complète) à la room
                $login_msg = [
                    'type'        => 'login',
                    'client_id'   => $client_id,
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
                    'self_id'     => $client_id,
                    'room_id'     => $room_id,
                    'client_list' => $client_list,
                    'time'        => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToClient($client_id, json_encode($welcome_msg));

                $history = ChatDb::getMessages($room_id);
                Gateway::sendToClient($client_id, json_encode([
                    'type'     => 'history',
                    'room_id'  => $room_id,
                    'messages' => $history
                ]));

                Gateway::sendToClient($client_id, json_encode([
                    'type' => 'locations',
                    'locations' => array_values(self::$locations)
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
                $msg = [
                    'type'      => 'status',
                    'client_id' => $client_id,
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
                $msg = [
                    'type'       => 'rename',
                    'client_id'  => $client_id,
                    'client_name'=> $new,
                ];
                Gateway::sendToGroup($room_id, json_encode($msg));
                if (isset(self::$locations[$client_id])) {
                    self::$locations[$client_id]['client_name'] = $new;
                    $loc = self::$locations[$client_id];
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
                self::$locations[$client_id] = [
                    'client_id'   => $client_id,
                    'client_name' => $client_name,
                    'lat'         => (float)$lat,
                    'lon'         => (float)$lon,
                ];
                $msg = self::$locations[$client_id];
                $msg['type'] = 'location';
                Gateway::sendToAll(json_encode($msg));
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
             * Message :
             *  - DM : envoie à l'autre ET au sender (une seule fois chacun)
             *  - Room : broadcast à la room courante
             */
            case 'say': {
                $room_id     = $_SESSION['room_id'] ?? 'general';
                $client_name = $_SESSION['client_name'] ?? 'Invité';
                $content     = nl2br(htmlspecialchars($data['content'] ?? ''));

                // DM
                if (!empty($data['to_client_id']) && $data['to_client_id'] !== 'all') {
                    $to_id = $data['to_client_id'];
                    $msg = [
                        'type'             => 'say',
                        'from_client_id'   => $client_id,
                        'from_client_name' => $client_name,
                        'to_client_id'     => $to_id,
                        'content'          => $content,
                        'time'             => date('Y-m-d H:i:s'),
                        'dm'               => true
                    ];
                    Gateway::sendToClient($to_id, json_encode($msg));
                    Gateway::sendToClient($client_id, json_encode($msg));
                    $room_key = 'dm:' . min($client_id, $to_id) . ':' . max($client_id, $to_id);
                    ChatDb::logMessage($room_key, (string)$client_id, $client_name, (string)$to_id, $content);
                    return;
                }

                // Room
                $msg = [
                    'type'             => 'say',
                    'from_client_id'   => $client_id,
                    'from_client_name' => $client_name,
                    'to_client_id'     => 'all',
                    'room_id'          => $room_id,
                    'content'          => $content,
                    'time'             => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToGroup($room_id, json_encode($msg));
                ChatDb::logMessage($room_id, (string)$client_id, $client_name, 'all', $content);
                return;
            }
        }
    }

    public static function onClose($client_id)
    {
        if(isset($_SESSION['room_id'])) {
            $room_id = $_SESSION['room_id'];
            $msg = [
                'type'             => 'logout',
                'from_client_id'   => $client_id,
                'from_client_name' => $_SESSION['client_name'] ?? 'Invité',
                'time'             => date('Y-m-d H:i:s'),
            ];
            Gateway::sendToGroup($room_id, json_encode($msg));
        }
        if (isset(self::$locations[$client_id])) {
            unset(self::$locations[$client_id]);
            Gateway::sendToAll(json_encode(['type'=>'location_remove','client_id'=>$client_id]));
        }
    }
}
