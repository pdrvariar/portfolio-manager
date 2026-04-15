-- Script de Migração: Converte Margens de Rebalanceamento de Relativas para Absolutas
-- Este script atualiza os valores existentes na tabela portfolio_assets para o novo formato.
-- Novo formato: rebalance_margin_down e rebalance_margin_up representam o percentual total da carteira (ex: 45% e 55%).
-- Formato antigo: representavam desvios em relação ao alvo (ex: -5% e +5% para um alvo de 50%).

-- Antes de aplicar, é altamente recomendável fazer um backup da tabela:
-- CREATE TABLE portfolio_assets_backup AS SELECT * FROM portfolio_assets;

UPDATE portfolio_assets pa
JOIN portfolios p ON pa.portfolio_id = p.id
SET 
    pa.rebalance_margin_down = CASE 
        WHEN pa.rebalance_margin_down IS NOT NULL AND p.rebalance_type = 'custom_margin' 
        THEN pa.allocation_percentage + pa.rebalance_margin_down 
        ELSE pa.rebalance_margin_down 
    END,
    pa.rebalance_margin_up = CASE 
        WHEN pa.rebalance_margin_up IS NOT NULL AND p.rebalance_type = 'custom_margin' 
        THEN pa.allocation_percentage + pa.rebalance_margin_up 
        ELSE pa.rebalance_margin_up 
    END
WHERE p.rebalance_type = 'custom_margin';

-- Nota: Se p.rebalance_type não for 'custom_margin', as margens individuais geralmente são nulas ou ignoradas, 
-- mas o filtro garante que só mexemos onde a lógica de margem customizada é aplicada.
