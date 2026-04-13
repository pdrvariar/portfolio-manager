-- Script de correção dos grupos de imposto (Tax Groups)
-- Rodar no MySQL: portfolio_db

-- 1. Garante que o ENUM suporte todos os grupos necessários
ALTER TABLE system_assets MODIFY COLUMN tax_group ENUM('RENDA_FIXA', 'ETF_BR', 'CRIPTOMOEDA', 'FUNDO_IMOBILIARIO', 'ETF_US', 'ETF_USA', 'ACAO_BR', 'NAO_APLICAVEL') DEFAULT 'RENDA_FIXA';

-- 2. Corrige o DIVO11 para ETF_BR
UPDATE system_assets SET tax_group = 'ETF_BR' WHERE name = 'DIVO11' OR yahoo_ticker = 'DIVO11.SA';

-- 3. Corrige o SP500 para ETF_US
UPDATE system_assets SET tax_group = 'ETF_US' WHERE name = 'SP500' OR yahoo_ticker = 'IVVB11.SA';

-- 4. Normalização de grupos existentes no banco para os grupos base (opcional mas recomendado)
UPDATE system_assets SET tax_group = 'ETF_BR' WHERE tax_group = 'ACAO_BR';
UPDATE system_assets SET tax_group = 'ETF_US' WHERE tax_group = 'ETF_USA';
