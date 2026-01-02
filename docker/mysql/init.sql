-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    verification_token VARCHAR(255) NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires_at TIMESTAMP NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de ativos
CREATE TABLE IF NOT EXISTS assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('STOCK', 'BOND', 'CRYPTO', 'COMMODITY', 'CURRENCY', 'INDEX') NOT NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de preços
CREATE TABLE IF NOT EXISTS asset_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    date DATE NOT NULL,
    price DECIMAL(20, 8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    INDEX idx_asset_date (asset_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de portfólios
CREATE TABLE IF NOT EXISTS portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    initial_capital DECIMAL(15, 2) DEFAULT 100000.00,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    rebalance_frequency ENUM('MONTHLY', 'QUARTERLY', 'SEMIANNUAL', 'ANNUAL', 'NEVER') DEFAULT 'MONTHLY',
    output_currency VARCHAR(3) DEFAULT 'BRL',
    is_clone BOOLEAN DEFAULT FALSE,
    cloned_from INT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cloned_from) REFERENCES portfolios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de alocação de ativos no portfólio
CREATE TABLE IF NOT EXISTS portfolio_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    asset_id INT NOT NULL,
    allocation_percentage DECIMAL(10, 8) NOT NULL,
    performance_factor DECIMAL(5, 2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_portfolio_asset (portfolio_id, asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de simulações
CREATE TABLE IF NOT EXISTS simulations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('PENDING', 'RUNNING', 'COMPLETED', 'ERROR') DEFAULT 'PENDING',
    execution_id VARCHAR(36) UNIQUE NOT NULL,
    result_data JSON,
    charts_html TEXT,
    metrics JSON,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_execution_id (execution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de câmbio
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    base_currency VARCHAR(3) NOT NULL,
    target_currency VARCHAR(3) NOT NULL,
    date DATE NOT NULL,
    rate DECIMAL(20, 8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rate_date (base_currency, target_currency, date),
    INDEX idx_date_currency (date, base_currency, target_currency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados de exemplo
INSERT INTO assets (code, name, type, currency, is_default) VALUES
('BTC-USD', 'Bitcoin', 'CRYPTO', 'USD', true),
('BVSP-IBOVESPA', 'Ibovespa', 'INDEX', 'BRL', true),
('GSPC-SP500', 'S&P 500', 'INDEX', 'USD', true),
('IRX-RF-USA-CURTO-PRAZO', 'US Short Term Bond', 'BOND', 'USD', true),
('SELIC_MENSAL', 'SELIC', 'BOND', 'BRL', true),
('XAU-OURO', 'Gold', 'COMMODITY', 'USD', true),
('USD-BRL', 'US Dollar', 'CURRENCY', 'BRL', true);

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO users (name, email, password, is_admin) VALUES
('Administrador', 'admin@portfolio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', true);