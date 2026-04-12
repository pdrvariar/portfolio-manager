-- Adicionar coluna tax_group na tabela system_assets
ALTER TABLE system_assets ADD COLUMN tax_group ENUM('RENDA_FIXA', 'ETF_BR', 'CRIPTOMOEDA', 'FUNDO_IMOBILIARIO', 'ETF_US', 'NAO_APLICAVEL') DEFAULT 'RENDA_FIXA';
