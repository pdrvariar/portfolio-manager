-- ============================================================
-- Migration 004: Pricing Management & Discount Coupons
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Planos de preço dinâmicos (histórico de preços)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscription_plans (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_type        ENUM('monthly','yearly') NOT NULL,
    price            DECIMAL(10,2) NOT NULL,
    original_price   DECIMAL(10,2) DEFAULT NULL COMMENT 'Preço original para exibir como "de R$"',
    label            VARCHAR(100)  DEFAULT NULL COMMENT 'Apelido/label ex: Black Friday 🔥',
    description      VARCHAR(255)  DEFAULT NULL,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    effective_from   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    effective_until  DATETIME      DEFAULT NULL COMMENT 'NULL = sem prazo',
    updated_by       INT           DEFAULT NULL COMMENT 'Admin user_id',
    notes            TEXT          DEFAULT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plan_active (plan_type, is_active),
    INDEX idx_effective (plan_type, effective_from, effective_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: preços atuais como registro inicial
INSERT IGNORE INTO subscription_plans (plan_type, price, original_price, label, is_active, effective_from)
VALUES
    ('monthly', 29.90, NULL, 'Padrão', 1, NOW()),
    ('yearly',  179.40, 358.80, 'Padrão (50% off)', 1, NOW());

-- ─────────────────────────────────────────────────────────────
-- 2. Configuração de parcelamento por plano
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subscription_installment_configs (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_type               ENUM('monthly','yearly') NOT NULL UNIQUE,
    max_installments        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    interest_free_up_to     TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Parcelas sem juros',
    monthly_interest_rate   DECIMAL(6,4)     NOT NULL DEFAULT 0.0000 COMMENT 'Taxa mensal ex: 0.0199 = 1.99%',
    is_active               TINYINT(1)       NOT NULL DEFAULT 1,
    created_at              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: configuração padrão
INSERT IGNORE INTO subscription_installment_configs (plan_type, max_installments, interest_free_up_to, monthly_interest_rate)
VALUES
    ('monthly', 1,  1, 0.0000),
    ('yearly',  12, 3, 0.0199);

-- ─────────────────────────────────────────────────────────────
-- 3. Cupons de desconto
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS discount_coupons (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(50)   NOT NULL COMMENT 'Alias/código ex: BLACKFRIDAY, BEMVINDO20',
    display_name     VARCHAR(100)  NOT NULL COMMENT 'Nome exibido ao usuário',
    discount_type    ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value   DECIMAL(10,2) NOT NULL COMMENT '% ou R$ conforme discount_type',
    applies_to       ENUM('monthly','yearly','both') NOT NULL DEFAULT 'both',
    min_price        DECIMAL(10,2) DEFAULT NULL COMMENT 'Preço mínimo para aplicar',
    max_discount     DECIMAL(10,2) DEFAULT NULL COMMENT 'Teto máximo de desconto em R$',
    max_uses         INT           DEFAULT NULL COMMENT 'NULL = ilimitado',
    used_count       INT           NOT NULL DEFAULT 0,
    valid_from       DATETIME      DEFAULT NULL,
    valid_until      DATETIME      DEFAULT NULL COMMENT 'NULL = sempre válido',
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    created_by       INT           DEFAULT NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_code (code),
    INDEX idx_active (is_active),
    INDEX idx_valid (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 4. Log de uso de cupons
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS coupon_uses (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id        INT UNSIGNED  NOT NULL,
    user_id          INT           NOT NULL,
    subscription_id  INT           DEFAULT NULL,
    original_price   DECIMAL(10,2) NOT NULL,
    discount_applied DECIMAL(10,2) NOT NULL,
    final_price      DECIMAL(10,2) NOT NULL,
    used_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_coupon_user (coupon_id, user_id),
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id),
    CONSTRAINT fk_cu_coupon FOREIGN KEY (coupon_id) REFERENCES discount_coupons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. Alterar tabela subscriptions para vincular cupom
-- ─────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS add_coupon_columns;
DELIMITER //
CREATE PROCEDURE add_coupon_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions' AND COLUMN_NAME = 'coupon_id'
    ) THEN
        ALTER TABLE subscriptions ADD COLUMN coupon_id INT UNSIGNED DEFAULT NULL AFTER notes;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions' AND COLUMN_NAME = 'discount_amount'
    ) THEN
        ALTER TABLE subscriptions ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_id;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions' AND COLUMN_NAME = 'coupon_code'
    ) THEN
        ALTER TABLE subscriptions ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER discount_amount;
    END IF;
END //
DELIMITER ;
CALL add_coupon_columns();
DROP PROCEDURE IF EXISTS add_coupon_columns;

