-- Adicionar coluna profit_tax_rates_json para armazenar alíquotas de impostos por grupo
ALTER TABLE portfolios ADD COLUMN profit_tax_rates_json TEXT DEFAULT NULL;

-- Opcional: Migrar o valor antigo de profit_tax_rate para o novo JSON se necessário
-- UPDATE portfolios SET profit_tax_rates_json = JSON_OBJECT('RENDA_FIXA', profit_tax_rate, 'ETF_BR', profit_tax_rate, 'CRIPTOMOEDA', profit_tax_rate, 'FUNDO_IMOBILIARIO', profit_tax_rate, 'ETF_US', profit_tax_rate) WHERE profit_tax_rate IS NOT NULL;
