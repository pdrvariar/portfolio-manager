-- ============================================================
--  Tabela de Snapshots de Configuração do Portfólio
--  Guarda, no momento da simulação, todos os parâmetros do
--  portfólio e a composição de ativos — permitindo reproduzir
--  qualquer simulação passada exatamente como foi rodada.
-- ============================================================

CREATE TABLE IF NOT EXISTS simulation_snapshots (
    id               INT          PRIMARY KEY AUTO_INCREMENT,
    simulation_id    INT          NOT NULL UNIQUE,
    portfolio_config JSON         NOT NULL,
    assets_config    JSON         NOT NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (simulation_id)
        REFERENCES simulation_results(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para busca rápida
CREATE INDEX idx_snapshot_sim ON simulation_snapshots (simulation_id);

-- Limpa simulações existentes para começar do zero (conforme solicitado)
DELETE FROM simulation_results;

