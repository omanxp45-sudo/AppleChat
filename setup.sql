-- Run this once in phpMyAdmin or MySQL CLI to create the database and tables.
-- mysql -u root -p < setup.sql

CREATE DATABASE IF NOT EXISTS appleschat
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE appleschat;

-- ── Users table ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(15)  NOT NULL UNIQUE,
    status     VARCHAR(25)  NOT NULL DEFAULT '',
    avatar     MEDIUMTEXT   DEFAULT NULL,   -- base64-encoded image
    last_seen  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Messages table ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(15)  NOT NULL,
    avatar      MEDIUMTEXT   DEFAULT NULL,   -- snapshot of avatar at send time
    message     TEXT         DEFAULT NULL,
    image_data  MEDIUMTEXT   DEFAULT NULL,   -- base64 uploaded image
    sticker_url VARCHAR(512) DEFAULT NULL,   -- imgur sticker URL
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_id      (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
