-- 1. Definição do Ambiente
CREATE DATABASE IF NOT EXISTS portfolio_db;
USE portfolio_db;

-- 2. Tabela de Usuários (Com campos de Verificação e Recuperação)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    birth_date DATE,
    password VARCHAR(255) NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    verification_token VARCHAR(100) NULL,
    email_verified_at TIMESTAMP NULL,
    reset_token VARCHAR(100) NULL,           -- Novo campo para recuperação
    reset_expires_at DATETIME NULL,         -- Novo campo para expiração
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabela de Ativos do Sistema
CREATE TABLE system_assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    currency VARCHAR(3) NOT NULL, 
    asset_type VARCHAR(20) NOT NULL, 
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Tabela de Dados Históricos
CREATE TABLE asset_historical_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    reference_date DATE NOT NULL, 
    price DECIMAL(20, 10) NOT NULL,
    FOREIGN KEY (asset_id) REFERENCES system_assets(id) ON DELETE CASCADE,
    INDEX idx_asset_date (asset_id, reference_date),
    UNIQUE KEY unique_asset_date (asset_id, reference_date)
) ENGINE=InnoDB;

-- 5. Tabela de Portfólios
CREATE TABLE portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    initial_capital DECIMAL(15, 2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    rebalance_frequency ENUM('never', 'monthly', 'quarterly', 'biannual', 'annual') DEFAULT 'monthly',
    output_currency VARCHAR(3) DEFAULT 'BRL',
    is_system_default BOOLEAN DEFAULT FALSE,
    cloned_from INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cloned_from) REFERENCES portfolios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. Tabela de Alocação
CREATE TABLE portfolio_assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    asset_id INT NOT NULL,
    allocation_percentage DECIMAL(10, 6) NOT NULL,
    performance_factor DECIMAL(10, 4) DEFAULT 1.0,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES system_assets(id),
    UNIQUE KEY unique_portfolio_asset (portfolio_id, asset_id)
) ENGINE=InnoDB;

-- 7. Tabela de Resultados de Simulação
CREATE TABLE simulation_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    simulation_date DATE NOT NULL,
    total_value DECIMAL(15, 2) NOT NULL,
    annual_return DECIMAL(10, 4),
    volatility DECIMAL(10, 4),
    max_drawdown DECIMAL(10, 4),
    sharpe_ratio DECIMAL(10, 4),
    chart_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Tabela de Detalhes por Ativo
CREATE TABLE simulation_asset_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    simulation_id INT NOT NULL,
    asset_id INT NOT NULL,
    year INT NOT NULL,
    annual_return DECIMAL(10, 4),
    FOREIGN KEY (simulation_id) REFERENCES simulation_results(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES system_assets(id)
) ENGINE=InnoDB;

-- 9. Seeders (Dados Iniciais)
INSERT INTO users (username, full_name, email, password, is_admin, status) 
VALUES ('admin', 'Administrador do Sistema', 'admin@portfolio.com', '$2y$10$WAogU2u/zEPt4IAfozFKGOvSIxMMd3vBQPz2NCI6Ehf6Q8AGPPFxa', TRUE, 'active');

INSERT INTO system_assets (code, name, currency, asset_type, source) VALUES
('BTC-USD', 'Bitcoin', 'USD', 'COTACAO', 'Yahoo'),
('BVSP-IBOVESPA', 'Ibovespa', 'BRL', 'COTACAO', 'Yahoo'),
('IFIX', 'Índice de Fundos Imobiliários', 'BRL', 'COTACAO', 'Yahoo'),
('SELIC', 'Taxa Selic', 'BRL', 'TAXA_MENSAL', 'SELIC'),
('IRX-RF-USA', 'Tesouro EUA Curto Prazo', 'USD', 'TAXA_MENSAL', 'SELIC'),
('USD-BRL', 'Dólar Americano', 'BRL', 'CAMBIO', 'Yahoo'),
('SP500', 'S&P 500', 'USD', 'COTACAO', 'Yahoo'),
('XAU-OURO', 'Ouro (Gold)', 'USD', 'COTACAO', 'Yahoo');

ALTER TABLE portfolios
    ADD COLUMN simulation_type ENUM('standard', 'monthly_deposit', 'strategic_deposit') DEFAULT 'standard',
ADD COLUMN deposit_amount DECIMAL(15, 2) NULL,
ADD COLUMN deposit_currency VARCHAR(3) NULL,
ADD COLUMN deposit_frequency VARCHAR(20) NULL,
ADD COLUMN strategic_threshold DECIMAL(10, 4) NULL,
ADD COLUMN strategic_deposit_percentage DECIMAL(10, 4) NULL;

-- Atualize o INSERT existente para incluir valores padrão para os novos campos
-- (Opcional, se quiser que portfolios existentes tenham valores padrão)
UPDATE portfolios
SET simulation_type = 'standard',
    deposit_amount = NULL,
    deposit_currency = NULL,
    deposit_frequency = NULL,
    strategic_threshold = NULL,
    strategic_deposit_percentage = NULL
WHERE simulation_type IS NULL;

-- Final do script
