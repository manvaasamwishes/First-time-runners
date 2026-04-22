-- ============================================================
-- 1st Time Runners Club — MySQL Setup Script
-- Run this in Hostinger's phpMyAdmin or MySQL panel ONCE
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── EVENTS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(255) NOT NULL,
  `event_date` DATE,
  `venue`      TEXT,
  `distances`  TEXT,
  `reg_link`   TEXT,
  `organizer`  VARCHAR(255),
  `disc_code`  VARCHAR(100),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── RUNNERS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `runners` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(255) NOT NULL,
  `phone`        VARCHAR(50),
  `email`        VARCHAR(255),
  `password`     VARCHAR(255),
  `level`        VARCHAR(50) DEFAULT 'Beginner',
  `join_date`    DATE,
  `km`           DOUBLE DEFAULT 0,
  `events_count` INT DEFAULT 0,
  `is_head`      TINYINT(1) DEFAULT 0,
  `status`       VARCHAR(50) DEFAULT 'Active',
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REGISTRATIONS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `registrations` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(255) NOT NULL,
  `phone`      VARCHAR(50),
  `email`      VARCHAR(255),
  `level`      VARCHAR(100),
  `source`     VARCHAR(100),
  `reg_date`   DATE,
  `password`   VARCHAR(255),
  `status`     VARCHAR(50) DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── KM LOG ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `km_log` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `runner_id` INT,
  `km_added`  DOUBLE,
  `note`      TEXT,
  `proof_url` VARCHAR(255),
  `status`    VARCHAR(50) DEFAULT 'Verified',
  `log_date`  DATE,
  FOREIGN KEY (`runner_id`) REFERENCES `runners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── WINNERS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `winners` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `runner_id`  INT,
  `category`   VARCHAR(100),
  `award_year` INT,
  `notes`      TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`runner_id`) REFERENCES `runners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ── SEED: RUNNERS ────────────────────────────────────────────
INSERT INTO `runners` (`name`, `phone`, `level`, `join_date`, `km`, `events_count`, `is_head`, `status`) VALUES
('Karthick AR',  '', 'Ultra',        '2025-01-01', 580, 24, 1, 'Active'),
('Manoj T',      '', 'Advanced',     '2025-02-20', 401, 18, 0, 'Active'),
('Rajkumar',     '', 'Intermediate', '2025-02-10', 312, 15, 0, 'Active'),
('Arun Kumar',   '', 'Intermediate', '2025-03-15', 243, 12, 0, 'Active'),
('Priya Sharma', '', 'Beginner',     '2025-03-01', 178,  9, 0, 'Active'),
('Divya S',      '', 'Beginner',     '2025-04-01',  95,  6, 0, 'Active');

-- ── SEED: EVENTS ─────────────────────────────────────────────
INSERT INTO `events` (`name`, `event_date`, `venue`, `distances`, `reg_link`, `organizer`, `disc_code`) VALUES
('Kannada Rajyotsava Run',          '2025-11-01', 'Bengaluru',                          '21.1km, 10K, 5K, 3K Fun Run', 'https://thevistaevents.in/kannada-rajyotsava-marathon-2025/', 'Vista Events',        ''),
('Hoysala Hustle',                  '2025-11-02', 'Nice Road, Hosakerehalli Toll',       'HM, 10K, 5K',                 'https://hoysalahustle.bhasinsports.com/',                     'Bhasin Sports',       'BLRRUNNERS'),
('Niveus Mangalore Marathon',       '2025-11-09', 'Mangalore',                          '42.2K, 32K, 21.1K, 10K, 5K, 2K', 'https://mangaloremarathon.com/',                           'Niveus',              ''),
('Movember Run',                    '2025-11-09', 'HSR Layout, Atal Bihari Vajpayee Stadium', 'Various',              'https://www.assisthelp.org/event-details-/movember-run-event', 'Assisthelp',         ''),
('1st Time Runners - Cubbon Park Run', '2025-11-16', 'Sree Kanteerva Stadium, Cubbon Park', '10K, 5K, 3K',           'https://www.townscript.com/e/5km-runningcubbon-park-114302',  '1st Time Runners Club', ''),
('Lions Diabetes Awareness Run',    '2025-11-16', 'BGS Grounds, Vijayanagar, Bengaluru', '5K, 3K',                   'https://www.lions317a.com/',                                  'Lions Club 317A',     ''),
('Canara Bank Marathon',            '2025-11-23', 'Cubbon Park, Bengaluru',             '5K',                          'https://canarabankmarathon.com/',                             'Canara Bank',         ''),
('Hosachiguru Earth Run',           '2025-11-23', 'Ramanagar',                          'HM, 10K, 5K × 4',             'https://www.eventzalley.com/event-details?eventId=253',       'Hosachiguru',         ''),
('SBI Green Marathon Season 6',     '2025-11-30', 'Nice Road, Bengaluru',               'HM, 21K, 10K',                'https://dev.fitterindia.com/sbi-green-marathon-season-6-bangalore.html', 'SBI', '');
