-- =============================================================
-- Migração de Precisão Numérica
-- Aumenta a precisão dos campos DECIMAL para evitar arredondamentos
-- Executar no banco de dados existente (portfolio_db)
-- =============================================================

USE portfolio_db;

-- ---------------------------------------------------------------
-- 1. simulation_results: valores monetários (2 → 8 casas decimais)
-- ---------------------------------------------------------------
ALTER TABLE simulation_results
    MODIFY COLUMN total_value       DECIMAL(20, 8) NOT NULL,
    MODIFY COLUMN total_deposits    DECIMAL(20, 8) DEFAULT 0,
    MODIFY COLUMN total_invested    DECIMAL(20, 8) DEFAULT 0,
    MODIFY COLUMN interest_earned   DECIMAL(20, 8) DEFAULT 0;

-- ---------------------------------------------------------------
-- 2. simulation_results: percentuais e ratios (4 → 10 casas decimais)
-- ---------------------------------------------------------------
ALTER TABLE simulation_results
    MODIFY COLUMN annual_return          DECIMAL(20, 10),
    MODIFY COLUMN volatility             DECIMAL(20, 10),
    MODIFY COLUMN max_drawdown           DECIMAL(20, 10),
    MODIFY COLUMN sharpe_ratio           DECIMAL(20, 10),
    MODIFY COLUMN roi                    DECIMAL(20, 10) DEFAULT 0,
    MODIFY COLUMN strategy_return        DECIMAL(20, 10) DEFAULT 0,
    MODIFY COLUMN strategy_annual_return DECIMAL(20, 10) DEFAULT 0,
    MODIFY COLUMN max_monthly_gain       DECIMAL(20, 10) DEFAULT 0,
    MODIFY COLUMN max_monthly_loss       DECIMAL(20, 10) DEFAULT 0;

-- ---------------------------------------------------------------
-- 3. portfolio_assets: fator de performance (4 → 10 casas decimais)
-- ---------------------------------------------------------------
ALTER TABLE portfolio_assets
    MODIFY COLUMN performance_factor DECIMAL(20, 10) DEFAULT 1.0;

-- ---------------------------------------------------------------
-- 4. simulation_asset_details: retorno anual (4 → 10 casas decimais)
-- ---------------------------------------------------------------
ALTER TABLE simulation_asset_details
    MODIFY COLUMN annual_return DECIMAL(20, 10);

-- ---------------------------------------------------------------
-- 5. portfolios: valor de aporte (2 → 8) e thresholds (4 → 10 casas decimais)
-- ---------------------------------------------------------------
ALTER TABLE portfolios
    MODIFY COLUMN deposit_amount               DECIMAL(20, 8)  NULL,
    MODIFY COLUMN strategic_threshold          DECIMAL(20, 10) NULL,
    MODIFY COLUMN strategic_deposit_percentage DECIMAL(20, 10) NULL;

-- ---------------------------------------------------------------
-- Verificação: exibe os tipos dos campos alterados
-- ---------------------------------------------------------------
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'portfolio_db'
  AND (
    (TABLE_NAME = 'simulation_results'    AND COLUMN_NAME IN ('total_value','total_deposits','total_invested','interest_earned','annual_return','volatility','max_drawdown','sharpe_ratio','roi','strategy_return','strategy_annual_return','max_monthly_gain','max_monthly_loss'))
    OR (TABLE_NAME = 'portfolio_assets'   AND COLUMN_NAME = 'performance_factor')
    OR (TABLE_NAME = 'simulation_asset_details' AND COLUMN_NAME = 'annual_return')
    OR (TABLE_NAME = 'portfolios'         AND COLUMN_NAME IN ('deposit_amount','strategic_threshold','strategic_deposit_percentage'))
    OR (TABLE_NAME = 'asset_historical_data' AND COLUMN_NAME = 'price')
  )
ORDER BY TABLE_NAME, COLUMN_NAME;

