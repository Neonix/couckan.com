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
        self::$db->exec('CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            description TEXT,
            latitude REAL,
            longitude REAL,
            range_km REAL,
            contact TEXT,
            area TEXT,
            landmarks TEXT,
            allowed_keywords TEXT,
            is_offline INTEGER DEFAULT 1,
            auto_reply TEXT,
            notify_interval TEXT,
            visible_until TEXT,
            is_anonymous INTEGER DEFAULT 0,
            last_notified TEXT,
            created_at TEXT
        )');
        try {
            self::$db->exec('ALTER TABLE announcements ADD COLUMN last_notified TEXT');
        } catch (\PDOException $e) {
            // column already exists
        }
        self::$db->exec('CREATE TABLE IF NOT EXISTS announcement_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            announcement_id INTEGER,
            from_contact TEXT,
            content TEXT,
            rating INTEGER,
            created_at TEXT
        )');
        self::$db->exec('CREATE TABLE IF NOT EXISTS announcement_scheduled_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            announcement_id INTEGER,
            content TEXT,
            send_at TEXT,
            created_at TEXT
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

    public static function addAnnouncement(string $title, string $description, ?float $latitude, ?float $longitude, ?float $range,
        ?string $contact = null, ?string $area = null, ?string $landmarks = null, ?string $allowed_keywords = null,
        bool $is_offline = true, ?string $auto_reply = null, ?string $notify_interval = null,
        ?string $visible_until = null, bool $is_anonymous = false): int
    {
        self::init();
        $stmt = self::$db->prepare('INSERT INTO announcements (title, description, latitude, longitude, range_km, contact, area, landmarks, allowed_keywords, is_offline, auto_reply, notify_interval, visible_until, is_anonymous, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $title,
            $description,
            $latitude,
            $longitude,
            $range,
            $contact,
            $area,
            $landmarks,
            $allowed_keywords,
            $is_offline ? 1 : 0,
            $auto_reply,
            $notify_interval,
            $visible_until,
            $is_anonymous ? 1 : 0,
            date('Y-m-d H:i:s')
        ]);
        return (int) self::$db->lastInsertId();
    }

    public static function addAnnouncementMessage(int $announcement_id, ?string $from_contact, string $content, ?int $rating = null): int
    {
        self::init();
        $stmt = self::$db->prepare('INSERT INTO announcement_messages (announcement_id, from_contact, content, rating, created_at) VALUES (?,?,?,?,?)');
        $stmt->execute([$announcement_id, $from_contact, $content, $rating, date('Y-m-d H:i:s')]);
        return (int) self::$db->lastInsertId();
    }

    public static function getAnnouncementMessages(int $announcement_id): array
    {
        self::init();
        $stmt = self::$db->prepare('SELECT id, from_contact, content, rating, created_at FROM announcement_messages WHERE announcement_id = ? ORDER BY id DESC');
        $stmt->execute([$announcement_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function scheduleAnnouncementMessage(int $announcement_id, string $content, string $send_at): int
    {
        self::init();
        $stmt = self::$db->prepare('INSERT INTO announcement_scheduled_messages (announcement_id, content, send_at, created_at) VALUES (?,?,?,?)');
        $stmt->execute([$announcement_id, $content, $send_at, date('Y-m-d H:i:s')]);
        return (int) self::$db->lastInsertId();
    }

    public static function getScheduledAnnouncementMessages(int $announcement_id): array
    {
        self::init();
        $stmt = self::$db->prepare('SELECT id, content, send_at, created_at FROM announcement_scheduled_messages WHERE announcement_id = ? ORDER BY id DESC');
        $stmt->execute([$announcement_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAnnouncementSummary(int $announcement_id, ?string $since = null): array
    {
        self::init();
        if ($since === null) {
            $stmt = self::$db->prepare('SELECT last_notified FROM announcements WHERE id = ?');
            $stmt->execute([$announcement_id]);
            $since = $stmt->fetchColumn();
        }
        if (!$since) {
            $since = '1970-01-01 00:00:00';
        }
        $stmt = self::$db->prepare('SELECT id, from_contact, content, rating, created_at FROM announcement_messages WHERE announcement_id = ? AND created_at > ? ORDER BY id ASC');
        $stmt->execute([$announcement_id, $since]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function markAnnouncementNotified(int $announcement_id): void
    {
        self::init();
        $stmt = self::$db->prepare('UPDATE announcements SET last_notified = ? WHERE id = ?');
        $stmt->execute([date('Y-m-d H:i:s'), $announcement_id]);
    }

    public static function getAnnouncement(int $id): ?array
    {
        self::init();
        $stmt = self::$db->prepare('SELECT id, title, description, latitude, longitude, range_km, contact, area, landmarks, allowed_keywords, is_offline, auto_reply, notify_interval, visible_until, is_anonymous, created_at FROM announcements WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        $rstmt = self::$db->prepare('SELECT AVG(rating) as avg_rating, COUNT(rating) as rating_count FROM announcement_messages WHERE announcement_id = ? AND rating IS NOT NULL');
        $rstmt->execute([$id]);
        $rating = $rstmt->fetch(\PDO::FETCH_ASSOC);
        $row['average_rating'] = $rating['avg_rating'] !== null ? (float)$rating['avg_rating'] : null;
        $row['rating_count'] = (int) ($rating['rating_count'] ?? 0);
        return $row;
    }

    public static function getAnnouncements(): array
    {
        self::init();
        $stmt = self::$db->prepare('SELECT id, title, description, latitude, longitude, range_km, contact, area, landmarks, allowed_keywords, is_offline, notify_interval, visible_until, is_anonymous, created_at,
            (SELECT AVG(rating) FROM announcement_messages WHERE announcement_id = announcements.id AND rating IS NOT NULL) AS average_rating,
            (SELECT COUNT(rating) FROM announcement_messages WHERE announcement_id = announcements.id AND rating IS NOT NULL) AS rating_count
            FROM announcements WHERE visible_until IS NULL OR visible_until > ? ORDER BY id DESC');
        $stmt->execute([date('Y-m-d H:i:s')]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
