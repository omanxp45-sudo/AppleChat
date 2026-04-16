-- ApplesChat MySQL Schema
-- Run this once on your MySQL server to create the database and tables.

CREATE DATABASE IF NOT EXISTS appleschat
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE appleschat;

-- Online users / presence
CREATE TABLE IF NOT EXISTS users (
    username   VARCHAR(15)  PRIMARY KEY,
    avatar     LONGTEXT,
    status     VARCHAR(25),
    last_seen  BIGINT,
    INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB;

-- Public chat messages
CREATE TABLE IF NOT EXISTS messages (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    username          VARCHAR(15),
    avatar            LONGTEXT,
    status            VARCHAR(25),
    message           TEXT,
    image_data        LONGTEXT,
    audio_data        LONGTEXT,
    video_data        LONGTEXT,
    sticker_url       VARCHAR(500),
    audio_file_data   LONGTEXT,
    audio_file_name   VARCHAR(255),
    doc_data          LONGTEXT,
    doc_name          VARCHAR(255),
    doc_size          INT,
    contact_name      VARCHAR(60),
    contact_phone     VARCHAR(30),
    contact_email     VARCHAR(80),
    event_title       VARCHAR(100),
    event_date        VARCHAR(20),
    event_time        VARCHAR(10),
    event_location    VARCHAR(100),
    poll_id           VARCHAR(50),
    poll_question     VARCHAR(120),
    poll_options      TEXT,
    reply_to_username VARCHAR(15),
    reply_to_text     TEXT,
    created_at        BIGINT,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Private messages
CREATE TABLE IF NOT EXISTS private_messages (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    chat_id           VARCHAR(50),
    from_user         VARCHAR(15),
    to_user           VARCHAR(15),
    message           TEXT,
    image_data        LONGTEXT,
    audio_data        LONGTEXT,
    video_data        LONGTEXT,
    sticker_url       VARCHAR(500),
    audio_file_data   LONGTEXT,
    audio_file_name   VARCHAR(255),
    doc_data          LONGTEXT,
    doc_name          VARCHAR(255),
    doc_size          INT,
    contact_name      VARCHAR(60),
    contact_phone     VARCHAR(30),
    contact_email     VARCHAR(80),
    event_title       VARCHAR(100),
    event_date        VARCHAR(20),
    event_time        VARCHAR(10),
    event_location    VARCHAR(100),
    poll_id           VARCHAR(50),
    poll_question     VARCHAR(120),
    poll_options      TEXT,
    created_at        BIGINT,
    INDEX idx_chat_created (chat_id, created_at),
    INDEX idx_to_user (to_user, created_at)
) ENGINE=InnoDB;

-- Poll definitions
CREATE TABLE IF NOT EXISTS polls (
    poll_id    VARCHAR(50) PRIMARY KEY,
    question   VARCHAR(120),
    options    TEXT,
    created_at BIGINT
) ENGINE=InnoDB;

-- Poll votes (one vote per user per poll)
CREATE TABLE IF NOT EXISTS poll_votes (
    poll_id      VARCHAR(50),
    username     VARCHAR(15),
    option_index INT,
    PRIMARY KEY (poll_id, username)
) ENGINE=InnoDB;

-- WebRTC call sessions
CREATE TABLE IF NOT EXISTS calls (
    call_id    VARCHAR(50) PRIMARY KEY,
    caller     VARCHAR(15),
    callee     VARCHAR(15),
    offer      LONGTEXT,
    answer     LONGTEXT,
    status     VARCHAR(20)  DEFAULT 'ringing',
    created_at BIGINT,
    INDEX idx_callee_status (callee, status),
    INDEX idx_caller (caller)
) ENGINE=InnoDB;

-- WebRTC ICE candidates
CREATE TABLE IF NOT EXISTS ice_candidates (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    call_id    VARCHAR(50),
    role       ENUM('caller','callee'),
    candidate  LONGTEXT,
    created_at BIGINT,
    INDEX idx_call_role (call_id, role)
) ENGINE=InnoDB;
