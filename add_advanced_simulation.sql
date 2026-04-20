-- Migration: Add advanced simulation support to simulation_results
-- Run this against your database

ALTER TABLE simulation_results
    ADD COLUMN advanced_simulation_group VARCHAR(36) NULL DEFAULT NULL,
    ADD COLUMN allocation_label VARCHAR(512) NULL DEFAULT NULL;

CREATE INDEX idx_adv_sim_group ON simulation_results (advanced_simulation_group);

