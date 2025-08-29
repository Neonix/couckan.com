CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64) UNIQUE,
  nick VARCHAR(64),
  avatar_url VARCHAR(255),
  lat DOUBLE NULL,
  lon DOUBLE NULL,
  status ENUM('idle','typing','on_air') DEFAULT 'idle',
  online TINYINT(1) DEFAULT 0,
  last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ip VARCHAR(64),
  ua TEXT,
  referrer TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
  id VARCHAR(64) PRIMARY KEY,
  topic TEXT,
  welcome TEXT,
  owner VARCHAR(64) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_mods (
  room_id VARCHAR(64),
  user_id VARCHAR(64),
  PRIMARY KEY(room_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id VARCHAR(64),
  user_id VARCHAR(64),
  body TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  from_id VARCHAR(64),
  to_id VARCHAR(64),
  body TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS friends (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64),
  friend_id VARCHAR(64),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64),
  type VARCHAR(32),
  payload TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64),
  url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS signals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id VARCHAR(64),
  from_id VARCHAR(64),
  to_id VARCHAR(64),
  kind ENUM('offer','answer','candidate'),
  sdp TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO rooms (id, topic, welcome, owner) VALUES
('general', '<p><b>Bienvenue&nbsp;!</b> Discutez, placez votre pin, lancez un DM ou une visio.</p>', '<p>Présentez-vous, ajoutez vos amis, et cliquez sur un point pour voir les options.</p>', NULL);
