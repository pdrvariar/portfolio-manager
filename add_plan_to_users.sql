ALTER TABLE users ADD COLUMN plan VARCHAR(20) DEFAULT 'starter';
UPDATE users SET plan = 'starter' WHERE plan IS NULL;
