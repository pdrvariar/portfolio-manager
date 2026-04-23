-- Migration: cria tabela de configurações globais
-- Execute: mysql -u root -p portfolio_db < add_app_settings.sql

USE portfolio_db;

CREATE TABLE IF NOT EXISTS `app_settings` (
    `key`        VARCHAR(100)  NOT NULL,
    `value`      TEXT          NOT NULL,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações padrão (PIX desabilitado por padrão)
INSERT IGNORE INTO `app_settings` (`key`, `value`) VALUES
    ('pix_payment_enabled', '0');

