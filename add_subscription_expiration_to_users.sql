-- SQL Script para adicionar colunas de expiração e controle de assinatura
-- Gerado para o projeto Portfolio Manager
-- Data: 2026-04-16

ALTER TABLE users 
ADD COLUMN subscription_expires_at DATETIME NULL DEFAULT NULL,
ADD COLUMN subscription_plan_type ENUM('monthly', 'yearly') NULL DEFAULT NULL,
ADD COLUMN last_payment_id VARCHAR(100) NULL DEFAULT NULL;

-- Garante que usuários atuais sem plano starter tenham a estrutura pronta
UPDATE users SET plan = 'starter' WHERE plan IS NULL;
