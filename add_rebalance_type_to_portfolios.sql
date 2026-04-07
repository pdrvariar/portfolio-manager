ALTER TABLE portfolios 
ADD COLUMN rebalance_type VARCHAR(20) DEFAULT 'full' 
AFTER simulation_type;
