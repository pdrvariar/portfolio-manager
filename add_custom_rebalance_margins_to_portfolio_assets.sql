ALTER TABLE portfolio_assets ADD COLUMN rebalance_margin_down DECIMAL(10, 2) DEFAULT NULL;
ALTER TABLE portfolio_assets ADD COLUMN rebalance_margin_up DECIMAL(10, 2) DEFAULT NULL;