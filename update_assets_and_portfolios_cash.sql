-- Adicionar coluna is_cash na tabela system_assets
ALTER TABLE system_assets ADD COLUMN is_cash BOOLEAN DEFAULT FALSE;

-- Atualizar ativos com tipo TAXA_MENSAL para is_cash = TRUE
UPDATE system_assets SET is_cash = TRUE WHERE asset_type = 'TAXA_MENSAL';

-- Adicionar coluna use_cash_assets_for_rebalance na tabela portfolios
ALTER TABLE portfolios ADD COLUMN use_cash_assets_for_rebalance BOOLEAN DEFAULT FALSE;
