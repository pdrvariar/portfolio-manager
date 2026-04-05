ALTER TABLE simulation_results
ADD COLUMN max_monthly_gain DECIMAL(20, 10) DEFAULT 0,
ADD COLUMN max_monthly_loss DECIMAL(20, 10) DEFAULT 0;
