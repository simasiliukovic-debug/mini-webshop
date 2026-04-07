-- ============================================================
--  ModStore — Game Mods & Assets Database Schema
--  Compatible with MySQL 5.7+ / MariaDB 10.3+ (XAMPP)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Create database
CREATE DATABASE IF NOT EXISTS `modstore`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `modstore`;

-- ============================================================
--  TABLES
-- ============================================================

CREATE TABLE `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`    (`email`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id`   INT(11)     NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-box',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id`             INT(11)        NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(100)   NOT NULL,
  `slug`           VARCHAR(100)   NOT NULL,
  `description`    TEXT           NOT NULL,
  `price`          DECIMAL(10,2)  NOT NULL DEFAULT '0.00',
  `category_id`    INT(11)        DEFAULT NULL,
  `download_count` INT(11)        NOT NULL DEFAULT '0',
  `rating`         DECIMAL(3,2)   NOT NULL DEFAULT '0.00',
  `review_count`   INT(11)        NOT NULL DEFAULT '0',
  `version`        VARCHAR(20)    NOT NULL DEFAULT '1.0.0',
  `file_size`      VARCHAR(20)    NOT NULL DEFAULT '—',
  `release_date`   DATE           NOT NULL,
  `is_active`      TINYINT(1)     NOT NULL DEFAULT '1',
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `fk_product_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id`          INT(11)                                   NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)                                   NOT NULL,
  `full_name`   VARCHAR(100)                              NOT NULL,
  `email`       VARCHAR(100)                              NOT NULL,
  `address`     VARCHAR(255)                              NOT NULL,
  `city`        VARCHAR(100)                              NOT NULL,
  `postal_code` VARCHAR(20)                               NOT NULL,
  `country`     VARCHAR(100)                              NOT NULL,
  `total_price` DECIMAL(10,2)                             NOT NULL,
  `status`      ENUM('pending','completed','cancelled')   NOT NULL DEFAULT 'completed',
  `created_at`  TIMESTAMP                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_user` (`user_id`),
  CONSTRAINT `fk_order_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`   INT(11)       NOT NULL,
  `product_id` INT(11)       NOT NULL,
  `price`      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order`   (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_assets` (
  `id`          INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)   NOT NULL,
  `product_id`  INT(11)   NOT NULL,
  `acquired_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`,`product_id`),
  KEY `idx_ua_user`    (`user_id`),
  KEY `idx_ua_product` (`product_id`),
  CONSTRAINT `fk_ua_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ua_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)   NOT NULL,
  `product_id` INT(11)   NOT NULL,
  `rating`     TINYINT   NOT NULL DEFAULT '5',
  `comment`    TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_user_product` (`user_id`,`product_id`),
  KEY `idx_rv_user`    (`user_id`),
  KEY `idx_rv_product` (`product_id`),
  CONSTRAINT `fk_rv_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED DATA — Categories
-- ============================================================
INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES
('Weapons',    'weapons',    'bi-bullseye'),
('Maps',       'maps',       'bi-map'),
('Characters', 'characters', 'bi-person-fill'),
('UI & HUD',   'ui-hud',     'bi-display'),
('Sound FX',   'sound-fx',   'bi-music-note-beamed'),
('Vehicles',   'vehicles',   'bi-truck');

-- ============================================================
--  SEED DATA — Products
-- ============================================================
INSERT INTO `products`
  (`name`, `slug`, `description`, `price`, `category_id`, `download_count`, `rating`, `review_count`, `version`, `file_size`, `release_date`)
VALUES
(
  'Neon City Map Pack',
  'neon-city-map-pack',
  'A stunning collection of 5 cyberpunk-themed city maps with detailed neon lighting, atmospheric fog effects, and high-polygon building assets. Perfect for sci-fi shooters and open-world RPGs. Each map includes day and night lighting variants, fully optimised collision meshes, ambient audio triggers, and a complete level streaming setup. Compatible with Unreal Engine 5 and Unity 2022+.',
  9.99, 2, 1423, 4.80, 38, '2.1.0', '342 MB', '2024-01-15'
),
(
  'Plasma Rifle Bundle',
  'plasma-rifle-bundle',
  'A complete sci-fi weapon pack featuring three distinct plasma rifles — the Vex-7, Helion SR, and Cascade SMG — each with custom particle VFX, reload animations, muzzle flash effects, and spatial audio. Fully rigged with IK support and ready for Unreal Engine and Unity. All weapons include 3 LOD levels and PBR-ready texture sets at 4K resolution.',
  14.99, 1, 892, 4.60, 24, '1.4.2', '218 MB', '2024-02-20'
),
(
  'Basic UI Kit',
  'basic-ui-kit',
  'A free collection of clean, minimal UI components including animated health bars, ammo counters, minimap frames, inventory panels, and dialogue boxes. Built for rapid prototyping with both dark and light theme variants. Every element is provided as a vector-ready SVG and a Unity/Unreal prefab. Ideal starting point for any game UI and fully open-source under CC0.',
  0.00, 4, 5621, 4.30, 91, '3.0.1', '45 MB', '2024-01-05'
),
(
  'Cyberpunk Soldier Pack',
  'cyberpunk-soldier-pack',
  'Three fully rigged cyberpunk soldier characters — Rave, Ghost, and Vex — with modular armour pieces, custom subsurface skin shaders, and 40+ motion-capture animations including locomotion, combat, and cinematic idles. Each character ships with 4 colour variants and is compatible with standard humanoid rigs in both Unity and Unreal Engine. Includes a demo level.',
  19.99, 3, 674, 4.90, 57, '1.2.0', '680 MB', '2024-03-10'
),
(
  'Desert Storm Vehicle Set',
  'desert-storm-vehicle-set',
  'Military vehicle pack featuring two armoured trucks (Goliath-M and Raptor-APC), a twin-rotor assault helicopter, and a main battle tank. All four vehicles are fully textured with 8K PBR materials, include detailed interior models, destructible states, and working suspension physics. Optimised for real-time rendering at 120 fps. Blueprint/prefab variants included.',
  24.99, 6, 341, 4.70, 19, '1.0.5', '1.2 GB', '2024-03-22'
),
(
  'Ambient FX Pack',
  'ambient-fx-pack',
  'Free atmospheric sound effects: industrial wind, underground hum, neon buzz, heavy rain, electronic city ambience, distant explosions, and horror atmosphere loops. 15 seamlessly looping tracks, all royalty-free and mixed at 48 kHz / 24-bit. Ideal for horror, sci-fi, and post-apocalyptic game environments. Includes FMOD and Wwise-ready project files.',
  0.00, 5, 8903, 4.50, 172, '2.0.0', '120 MB', '2024-02-01'
),
(
  'Sci-Fi Terminal UI',
  'sci-fi-terminal-ui',
  'A complete in-game terminal and computer interface system featuring animated CRT scanlines, a blinking cursor, real-time data stream effects, and fully customisable colour schemes. Ships with 6 pre-built terminal themes (green phosphor, amber, holo-blue, red alert, and two more) plus a runtime theme switcher. Includes prefabs, scripts, and a comprehensive documentation site.',
  7.99, 4, 1205, 4.40, 33, '1.1.3', '28 MB', '2024-04-05'
),
(
  'Ancient Ruins Map',
  'ancient-ruins-map',
  'A 1 km × 1 km ancient ruins map with custom crumbling architecture, dense overgrown vegetation, volumetric atmospheric lighting, and ambient audio triggers baked into the level. The map includes 3 fully explorable interior dungeons, a modular ruin kit for expansion, and a runtime day/night cycle shader. Comes with 5 pre-configured lighting moods and a cinematic camera rig.',
  12.99, 2, 789, 4.75, 44, '1.3.0', '890 MB', '2024-04-18'
);
