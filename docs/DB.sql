-- ============================================
-- SMARTLIFE AI ORGANIZER - DATABASE SCHEMA
-- MySQL 8.0 / MariaDB 10.5+
-- ============================================

-- Impostazioni iniziali
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABELLA: users
-- ============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `plan` ENUM('free', 'pro') DEFAULT 'free',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Quote mensili
  `ai_queries_count` INT UNSIGNED DEFAULT 0,
  `ai_queries_reset_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `storage_used_bytes` BIGINT UNSIGNED DEFAULT 0,
  
  -- Stato account
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_login_at` TIMESTAMP NULL,
  
  INDEX idx_email (`email`),
  INDEX idx_plan (`plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: categories
-- ============================================
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(7) NOT NULL, -- hex: #3B82F6
  `icon` VARCHAR(50) NOT NULL, -- emoji o nome icona
  `is_default` BOOLEAN DEFAULT FALSE,
  `event_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_id (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: events
-- ============================================
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  
  -- Dati evento
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NULL,
  `amount` DECIMAL(10,2) NULL,
  
  -- Stato e ricorrenza
  `status` ENUM('pending', 'completed') DEFAULT 'pending',
  `recurrence_pattern` VARCHAR(255) NULL, -- iCalendar RRule format
  
  -- Metadata
  `source` ENUM('local', 'google') DEFAULT 'local',
  `google_event_id` VARCHAR(255) NULL,
  `has_document` BOOLEAN DEFAULT FALSE,
  `color` VARCHAR(7) NULL, -- override colore categoria
  
  -- Reminder (JSON array: [15, 60, 1440] = minuti prima)
  `reminders` JSON NULL,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  
  INDEX idx_user_datetime (`user_id`, `start_datetime`),
  INDEX idx_status (`status`),
  INDEX idx_category (`category_id`),
  INDEX idx_google_event (`google_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: event_instances
-- ============================================
-- Solo per modifiche puntuali di eventi ricorrenti
DROP TABLE IF EXISTS `event_instances`;
CREATE TABLE `event_instances` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT UNSIGNED NOT NULL,
  `instance_date` DATE NOT NULL, -- data originale istanza
  
  -- Override campi evento master
  `custom_start_datetime` DATETIME NULL,
  `custom_end_datetime` DATETIME NULL,
  `custom_title` VARCHAR(255) NULL,
  `custom_status` ENUM('pending', 'completed', 'deleted') NULL,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_instance (`event_id`, `instance_date`),
  INDEX idx_event_date (`event_id`, `instance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: documents
-- ============================================
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `event_id` INT UNSIGNED NULL,
  
  -- File info
  `filename` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(500) NOT NULL, -- /uploads/user_123/doc_456.pdf
  `filesize_bytes` INT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT 'application/pdf',
  
  -- Analisi AI
  `document_type` VARCHAR(100) NULL, -- fattura, bolletta, ricevuta
  `ai_summary` TEXT NULL,
  `extracted_amount` DECIMAL(10,2) NULL,
  `extracted_due_date` DATE NULL,
  `extracted_text` MEDIUMTEXT NULL, -- testo completo per ricerca
  
  -- Embedding Gemini (768 dimensioni)
  `embedding` JSON NULL,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL,
  
  INDEX idx_user_id (`user_id`),
  INDEX idx_event_id (`event_id`),
  FULLTEXT idx_extracted_text (`extracted_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: oauth_tokens
-- ============================================
DROP TABLE IF EXISTS `oauth_tokens`;
CREATE TABLE `oauth_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `provider` ENUM('google') DEFAULT 'google',
  
  -- Token OAuth
  `access_token` TEXT NOT NULL,
  `refresh_token` TEXT NULL,
  `token_type` VARCHAR(50) DEFAULT 'Bearer',
  `expires_at` TIMESTAMP NOT NULL,
  
  -- Scope autorizzati
  `scope` TEXT NULL,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_user_provider (`user_id`, `provider`),
  INDEX idx_expires (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: ai_query_log
-- ============================================
-- Log query AI per conteggio limiti e debug
DROP TABLE IF EXISTS `ai_query_log`;
CREATE TABLE `ai_query_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `query_type` ENUM('chat', 'document_analysis', 'search') NOT NULL,
  `input_text` TEXT NULL,
  `response_text` TEXT NULL,
  `tokens_used` INT UNSIGNED NULL,
  `execution_time_ms` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_date (`user_id`, `created_at`),
  INDEX idx_type (`query_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELLA: sessions (opzionale, se usi sessioni PHP)
-- ============================================
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` VARCHAR(128) PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  
  INDEX idx_user_id (`user_id`),
  INDEX idx_last_activity (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATI INIZIALI: Categorie Default
-- ============================================
-- Nota: queste vanno create per ogni nuovo utente via codice
-- Qui sono solo esempio struttura

-- INSERT INTO categories (user_id, name, color, icon, is_default) VALUES
-- (1, 'Lavoro', '#3B82F6', '💼', TRUE),
-- (1, 'Famiglia', '#10B981', '👨‍👩‍👧‍👦', TRUE),
-- (1, 'Personale', '#8B5CF6', '🧘', TRUE),
-- (1, 'Altro', '#6B7280', '📌', TRUE);

-- ============================================
-- PROCEDURE: Reset contatori mensili AI
-- ============================================
DELIMITER $$
DROP PROCEDURE IF EXISTS reset_ai_quotas$$
CREATE PROCEDURE reset_ai_quotas()
BEGIN
  UPDATE users 
  SET 
    ai_queries_count = 0,
    ai_queries_reset_at = CURRENT_TIMESTAMP
  WHERE 
    ai_queries_reset_at < DATE_SUB(NOW(), INTERVAL 1 MONTH);
END$$
DELIMITER ;

-- ============================================
-- EVENT SCHEDULER: Reset mensile automatico
-- ============================================
-- Attivare solo se MySQL Event Scheduler è abilitato
-- SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS monthly_reset_ai_quotas;
CREATE EVENT monthly_reset_ai_quotas
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL reset_ai_quotas();

-- ============================================
-- TRIGGER: Aggiorna contatore eventi categoria
-- ============================================
DELIMITER $$
DROP TRIGGER IF EXISTS after_event_insert$$
CREATE TRIGGER after_event_insert
AFTER INSERT ON events
FOR EACH ROW
BEGIN
  UPDATE categories 
  SET event_count = event_count + 1 
  WHERE id = NEW.category_id;
END$$

DROP TRIGGER IF EXISTS after_event_delete$$
CREATE TRIGGER after_event_delete
AFTER DELETE ON events
FOR EACH ROW
BEGIN
  UPDATE categories 
  SET event_count = GREATEST(0, event_count - 1)
  WHERE id = OLD.category_id;
END$$

DROP TRIGGER IF EXISTS after_event_update$$
CREATE TRIGGER after_event_update
AFTER UPDATE ON events
FOR EACH ROW
BEGIN
  IF OLD.category_id != NEW.category_id THEN
    UPDATE categories SET event_count = GREATEST(0, event_count - 1) WHERE id = OLD.category_id;
    UPDATE categories SET event_count = event_count + 1 WHERE id = NEW.category_id;
  END IF;
END$$
DELIMITER ;

-- ============================================
-- TRIGGER: Aggiorna storage usato utente
-- ============================================
DELIMITER $$
DROP TRIGGER IF EXISTS after_document_insert$$
CREATE TRIGGER after_document_insert
AFTER INSERT ON documents
FOR EACH ROW
BEGIN
  UPDATE users 
  SET storage_used_bytes = storage_used_bytes + NEW.filesize_bytes
  WHERE id = NEW.user_id;
END$$

DROP TRIGGER IF EXISTS after_document_delete$$
CREATE TRIGGER after_document_delete
AFTER DELETE ON documents
FOR EACH ROW
BEGIN
  UPDATE users 
  SET storage_used_bytes = GREATEST(0, storage_used_bytes - OLD.filesize_bytes)
  WHERE id = OLD.user_id;
END$$
DELIMITER ;

-- ============================================
-- VIEW: Eventi con info categoria
-- ============================================
DROP VIEW IF EXISTS view_events_full;
CREATE VIEW view_events_full AS
SELECT 
  e.*,
  c.name AS category_name,
  c.color AS category_color,
  c.icon AS category_icon,
  u.email AS user_email,
  u.plan AS user_plan
FROM events e
JOIN categories c ON e.category_id = c.id
JOIN users u ON e.user_id = u.id;

-- ============================================
-- INDICI AGGIUNTIVI per Performance
-- ============================================
-- Ricerca eventi per range date
ALTER TABLE events ADD INDEX idx_datetime_range (`start_datetime`, `end_datetime`);

-- Ricerca documenti per utente e data
ALTER TABLE documents ADD INDEX idx_user_created (`user_id`, `created_at`);

-- Query utenti pro
ALTER TABLE users ADD INDEX idx_plan_active (`plan`, `is_active`);

-- ============================================
-- FOREIGN KEY CHECKS
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- QUERY UTILI PER SVILUPPO
-- ============================================

-- Verifica quote utente
-- SELECT 
--   email, 
--   plan,
--   ai_queries_count,
--   ROUND(storage_used_bytes / 1048576, 2) AS storage_mb,
--   CASE 
--     WHEN plan = 'free' THEN CONCAT(ai_queries_count, '/20 AI queries')
--     ELSE CONCAT(ai_queries_count, '/500 AI queries')
--   END AS quota_status
-- FROM users;

-- Eventi prossimi 7 giorni
-- SELECT * FROM view_events_full 
-- WHERE user_id = 1 
--   AND start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
-- ORDER BY start_datetime;

-- Documenti con maggior storage
-- SELECT 
--   u.email,
--   d.filename,
--   ROUND(d.filesize_bytes / 1048576, 2) AS size_mb
-- FROM documents d
-- JOIN users u ON d.user_id = u.id
-- ORDER BY d.filesize_bytes DESC
-- LIMIT 10;

-- ============================================
-- FINE SCHEMA DATABASE
-- ============================================
