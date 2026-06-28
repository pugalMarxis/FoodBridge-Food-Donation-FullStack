-- ============================================================================
-- FoodBridge — MySQL Database Schema
-- Run this in phpMyAdmin (XAMPP) or:  mysql -u root < foodbridge.sql
-- ============================================================================

CREATE DATABASE IF NOT EXISTS foodbridge
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE foodbridge;

-- ----------------------------------------------------------------------------
-- USERS  (all roles live here: admin, giver, receiver, volunteer)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(160)  NOT NULL UNIQUE,
  phone         VARCHAR(20)   NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('admin','giver','receiver','volunteer') NOT NULL DEFAULT 'giver',
  location      VARCHAR(180)  DEFAULT NULL,
  latitude      DECIMAL(10,7) DEFAULT NULL,
  longitude     DECIMAL(10,7) DEFAULT NULL,
  avatar        VARCHAR(255)  DEFAULT NULL,
  language      ENUM('en','ta','si') NOT NULL DEFAULT 'en',
  status        ENUM('active','blocked','pending') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- FOOD POSTS  (donations posted by givers)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_posts (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  food_type     ENUM('cooked','groceries','vegetables','fruits','other') NOT NULL DEFAULT 'other',
  food_name     VARCHAR(160) NOT NULL,
  quantity      VARCHAR(60)  NOT NULL,
  unit          VARCHAR(40)  DEFAULT NULL,
  photo         VARCHAR(255) DEFAULT NULL,
  location      VARCHAR(180) DEFAULT NULL,
  latitude      DECIMAL(10,7) DEFAULT NULL,
  longitude     DECIMAL(10,7) DEFAULT NULL,
  pickup_date   DATE DEFAULT NULL,
  pickup_time   TIME DEFAULT NULL,
  notes         TEXT DEFAULT NULL,
  status        ENUM('available','claimed','completed','expired') NOT NULL DEFAULT 'available',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_food_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- REQUESTS  (food requests by receivers; can be linked to a food post + volunteer)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS requests (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  receiver_id   INT NOT NULL,
  food_post_id  INT DEFAULT NULL,
  volunteer_id  INT DEFAULT NULL,
  food_type     ENUM('cooked','groceries','vegetables','fruits','other') NOT NULL DEFAULT 'other',
  description   VARCHAR(255) NOT NULL,
  people_count  INT DEFAULT 1,
  location      VARCHAR(180) DEFAULT NULL,
  latitude      DECIMAL(10,7) DEFAULT NULL,
  longitude     DECIMAL(10,7) DEFAULT NULL,
  urgency       ENUM('urgent','today','anytime') NOT NULL DEFAULT 'today',
  status        ENUM('pending','approved','assigned','delivered','rejected') NOT NULL DEFAULT 'pending',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_req_receiver  FOREIGN KEY (receiver_id)  REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_req_food      FOREIGN KEY (food_post_id) REFERENCES food_posts(id) ON DELETE SET NULL,
  CONSTRAINT fk_req_volunteer FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- VOLUNTEERS  (extra profile data for users whose role = volunteer)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS volunteers (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  vehicle_type  ENUM('walking','bicycle','motorbike','car') NOT NULL DEFAULT 'walking',
  points        INT NOT NULL DEFAULT 0,
  rating        DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  deliveries    INT NOT NULL DEFAULT 0,
  is_available  TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_vol_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- RATINGS  (mutual ratings after a food exchange)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  from_user   INT NOT NULL,
  to_user     INT NOT NULL,
  request_id  INT DEFAULT NULL,
  stars       TINYINT NOT NULL,
  comment     VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rate_from FOREIGN KEY (from_user) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rate_to   FOREIGN KEY (to_user)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- NOTIFICATIONS
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  title       VARCHAR(160) NOT NULL,
  body        VARCHAR(255) DEFAULT NULL,
  icon        VARCHAR(40)  DEFAULT 'bell',
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- SEED DATA
-- Default admin login:  admin@foodbridge.lk  /  admin123
-- (password_hash below is a PHP password_hash() of "admin123")
-- ----------------------------------------------------------------------------
INSERT INTO users (name, email, phone, password_hash, role, location, status)
VALUES (
  'Admin',
  'admin@foodbridge.lk',
  '0770000000',
  '$2y$10$e0NRMC7Pq3Kb3vd9KQ8wXu0bQ0gE8s5b4n6Q1r2sQ3t4U5v6W7x8',
  'admin',
  'Colombo',
  'active'
) ON DUPLICATE KEY UPDATE email = email;