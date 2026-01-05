-- Banco de dados
CREATE DATABASE IF NOT EXISTS portfolio_db;
USE portfolio_db;

-- Tabela de usuários
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de ativos do sistema (padrão)
CREATE TABLE system_assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    asset_type VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de dados históricos dos ativos
CREATE TABLE asset_historical_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    reference_date DATE NOT NULL, 
    price DECIMAL(20, 10) NOT NULL,
    FOREIGN KEY (asset_id) REFERENCES system_assets(id) ON DELETE CASCADE,
    INDEX idx_asset_date (asset_id, reference_date)
);

-- Tabela de portfólios
CREATE TABLE portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    initial_capital DECIMAL(15, 2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    rebalance_frequency VARCHAR(10) DEFAULT 'monthly',
    output_currency VARCHAR(3) DEFAULT 'BRL',
    is_system_default BOOLEAN DEFAULT FALSE,
    cloned_from INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cloned_from) REFERENCES portfolios(id) ON DELETE SET NULL
);

-- Tabela de alocação dos ativos no portfólio
CREATE TABLE portfolio_assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    asset_id INT NOT NULL,
    allocation_percentage DECIMAL(10, 6) NOT NULL,
    performance_factor DECIMAL(10, 4) DEFAULT 1.0,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES system_assets(id),
    UNIQUE KEY unique_portfolio_asset (portfolio_id, asset_id)
);

-- Tabela de resultados de simulação
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
);

-- Tabela de resultados detalhados por ativo
CREATE TABLE simulation_asset_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    simulation_id INT NOT NULL,
    asset_id INT NOT NULL,
    year INT NOT NULL,
    annual_return DECIMAL(10, 4),
    contribution DECIMAL(10, 4),
    FOREIGN KEY (simulation_id) REFERENCES simulation_results(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES system_assets(id)
);

-- Inserir usuário administrador padrão (senha: admin123)
INSERT INTO users (username, email, password, is_admin) 
VALUES ('admin', 'admin@portfolio.com', '$2y$10$WAogU2u/zEPt4IAfozFKGOvSIxMMd3vBQPz2NCI6Ehf6Q8AGPPFxa', TRUE);

-- Inserir alguns ativos padrão
INSERT INTO system_assets (code, name, currency, asset_type) VALUES
('BTC-USD', 'Bitcoin', 'USD', 'COTACAO'),
('BVSP-IBOVESPA', 'Ibovespa', 'BRL', 'COTACAO'),
('IFIX', 'Índice de Fundos Imobiliários', 'BRL', 'COTACAO'),
('SELIC', 'Taxa Selic', 'BRL', 'TAXA_MENSAL'),
('IRX-RF-USA', 'Tesouro EUA Curto Prazo', 'USD', 'TAXA_MENSAL'),
('USD-BRL', 'Dólar Americano', 'BRL', 'CAMBIO');