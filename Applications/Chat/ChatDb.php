<?php
class ChatDb
{
    private static ?\PDO $db = null;

    private static function init(): void
    {
        if (self::$db !== null) {
            return;
        }
        $file = __DIR__ . '/chat.sqlite';
        self::$db = new \PDO('sqlite:' . $file);
        self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$db->exec('CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id TEXT,
            from_id TEXT,
            from_name TEXT,
            to_id TEXT,
            content TEXT,
            time TEXT
        )');
        self::$db->exec('CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            path TEXT,
            user_agent TEXT,
            ip TEXT,
            time TEXT
        )');
    }

    public static function logMessage(string $room, string $from_id, string $from_name, string $to_id, string $content): void
    {
        self::init();
        $stmt = self::$db->prepare('INSERT INTO messages (room_id, from_id, from_name, to_id, content, time) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$room, $from_id, $from_name, $to_id, $content, date('Y-m-d H:i:s')]);
    }

    public static function getMessages(string $room, int $limit = 50): array
    {
        self::init();
        $stmt = self::$db->prepare('SELECT room_id, from_id as from_client_id, from_name as from_client_name, to_id as to_client_id, content, time FROM messages WHERE room_id = ? ORDER BY id DESC LIMIT ?');
        $stmt->execute([$room, $limit]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_reverse($rows);
    }

    public static function logRequest(string $path, string $user_agent, string $ip): void
    {
        self::init();
        $stmt = self::$db->prepare('INSERT INTO requests (path, user_agent, ip, time) VALUES (?,?,?,?)');
        $stmt->execute([$path, $user_agent, $ip, date('Y-m-d H:i:s')]);
    }
}
