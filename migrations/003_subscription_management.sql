-- ============================================================
-- Migração 003: Sistema Completo de Gestão de Assinaturas
-- Execute após o schema inicial (init.sql)
-- ============================================================

-- 1. Adicionar subscription_status à tabela users
ALTER TABLE users
    ADD COLUMN subscription_status ENUM('none','active','canceled','expired','refunded') NOT NULL DEFAULT 'none' AFTER plan;

-- Migrar dados existentes
UPDATE users SET subscription_status = 'active'
    WHERE plan = 'pro' AND (subscription_expires_at IS NULL OR subscription_expires_at > NOW());
UPDATE users SET subscription_status = 'expired'
    WHERE plan = 'starter' AND last_payment_id IS NOT NULL AND subscription_expires_at IS NOT NULL AND subscription_expires_at < NOW();

-- 2. Criar tabela de assinaturas (audit trail completo)
CREATE TABLE IF NOT EXISTS subscriptions (
    id                    INT PRIMARY KEY AUTO_INCREMENT,
    user_id               INT NOT NULL,
    mp_payment_id         VARCHAR(100) NULL,
    mp_idempotency_key    VARCHAR(150) NULL,
    plan_type             ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    status                ENUM('active','canceled','expired','refunded','pending','failed') NOT NULL DEFAULT 'pending',
    amount_paid           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    starts_at             DATETIME NOT NULL,
    expires_at            DATETIME NOT NULL,
    canceled_at           DATETIME NULL,
    cancel_type           ENUM('immediate','end_of_period') NULL,
    refund_eligible_until DATETIME NULL,
    refunded_at           DATETIME NULL,
    refund_mp_id          VARCHAR(100) NULL,
    refund_amount         DECIMAL(10,2) NULL,
    -- Controle de e-mails de renovação (evita duplicatas)
    reminder_7_sent       TINYINT(1) NOT NULL DEFAULT 0,
    reminder_3_sent       TINYINT(1) NOT NULL DEFAULT 0,
    reminder_1_sent       TINYINT(1) NOT NULL DEFAULT 0,
    notes                 TEXT NULL,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user_id   (user_id),
    INDEX idx_status    (status),
    INDEX idx_expires_at(expires_at),

    -- NULL é permitido em UNIQUE; chaves distintas são únicas
    UNIQUE KEY uq_idempotency (mp_idempotency_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Migrar dados históricos de pagamentos existentes dos usuários
INSERT IGNORE INTO subscriptions (user_id, mp_payment_id, plan_type, status, amount_paid, starts_at, expires_at, refund_eligible_until, notes)
SELECT
    id,
    last_payment_id,
    COALESCE(subscription_plan_type, 'monthly'),
    CASE
        WHEN plan = 'pro' AND (subscription_expires_at IS NULL OR subscription_expires_at > NOW()) THEN 'active'
        WHEN subscription_expires_at < NOW() THEN 'expired'
        ELSE 'active'
    END,
    CASE COALESCE(subscription_plan_type, 'monthly') WHEN 'yearly' THEN 179.40 ELSE 29.90 END,
    COALESCE(DATE_SUB(subscription_expires_at, INTERVAL 1 MONTH), NOW()),
    COALESCE(subscription_expires_at, DATE_ADD(NOW(), INTERVAL 1 MONTH)),
    DATE_ADD(COALESCE(DATE_SUB(subscription_expires_at, INTERVAL 1 MONTH), NOW()), INTERVAL 7 DAY),
    'Migrado do sistema anterior'
FROM users
WHERE last_payment_id IS NOT NULL
  AND plan = 'pro';

