ALTER TABLE simulation_results
ADD COLUMN max_monthly_gain DECIMAL(10, 4) DEFAULT 0,
ADD COLUMN max_monthly_loss DECIMAL(10, 4) DEFAULT 0;
