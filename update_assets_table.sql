USE portfolio_db;
ALTER TABLE system_assets ADD COLUMN source VARCHAR(50) DEFAULT 'Yahoo';
ALTER TABLE system_assets ADD COLUMN yahoo_ticker VARCHAR(50) NULL;
