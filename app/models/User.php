<?php

/**
 * Classe User - Modelo de Gestão de Identidade e Segurança
 * Responsável pela persistência e lógica de negócios de utilizadores.
 */
class User {
    private $db;

    public function __construct() {
        //
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cria um novo utilizador no sistema com status pendente.
     * @param array $data Dados do formulário (full_name, email, phone, birth_date, username, password)
     * @return string|false Retorna o token de verificação em caso de sucesso ou false.
     */
    public function create($data) {
        try {
            // Hashing de senha usando o algoritmo padrão atual (Bcrypt)
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Geração de um token seguro para verificação de e-mail
            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO users (
                        username, full_name, email, phone, birth_date, 
                        password, verification_token, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $data['username'],
                $data['full_name'],
                $data['email'],
                $data['phone'],
                $data['birth_date'],
                $hashedPassword,
                $token
            ]);

            return $success ? $token : false;
        } catch (PDOException $e) {
            // Log de erro profissional (evite expor detalhes do SQL ao usuário final)
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida o e-mail do utilizador via token.
     * @param string $token Token enviado por e-mail.
     */
    public function verifyEmail($token) {
        $sql = "UPDATE users 
                SET status = 'active', email_verified_at = NOW(), verification_token = NULL 
                WHERE verification_token = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$token]);
    }

    /**
     * Lógica de Autenticação Federada (Google Login)
     * Refatorada para garantir status 'active' e preencher data de verificação.
     */
    public function findOrCreateGoogleUser(array $data) {
        // 1. Verificar se o utilizador já existe pelo e-mail
        $user = $this->findByEmail($data['email']);

        if ($user) {
            // UEX Sênior: Se o usuário existia como 'pending', ativamos ele agora
            // pois o Google confirmou a identidade do dono do e-mail.
            if ($user['status'] === 'pending') {
                $this->activate($user['id']);
                $user['status'] = 'active'; // Atualiza o objeto para a sessão
            }
            return $user;
        }

        // 2. Se não existe, criar um novo já ativo e verificado
        $username = explode('@', $data['email'])[0] . rand(10, 99);
        
        // SÊNIOR: Note o preenchimento de email_verified_at e status 'active'
        $sql = "INSERT INTO users (full_name, email, username, status, email_verified_at, is_admin) 
                VALUES (?, ?, ?, 'active', NOW(), 0)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['email'],
                $username
            ]);

            return $this->findByEmail($data['email']);
        } catch (PDOException $e) {
            error_log("Erro ao criar utilizador Google: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Localiza um utilizador pelo nome de usuário exclusivo.
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /**
     * Localiza um utilizador pelo e-mail exclusivo.
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findByCredentials($login) {
        // 1. Preparamos a query com placeholders '?' para evitar SQL Injection
        $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        
        // 2. Usamos o prepare em vez do query
        $stmt = $this->db->prepare($sql);
        
        // 3. Executamos passando os valores
        $stmt->execute([$login, $login]);
        
        // 4. Retornamos o resultado
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }   

    /**
     * Localiza um utilizador pelo ID primário.
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Atualiza os dados de perfil do utilizador.
     */
    public function update($id, $data) {
        $sql = "UPDATE users SET full_name = ?, phone = ?, birth_date = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['full_name'],
            $data['phone'],
            $data['birth_date'],
            $id
        ]);
    }

    /**
     * Atualização administrativa completa de um usuário.
     */
    public function adminUpdate($id, $data) {
        $sql = "UPDATE users SET full_name = ?, email = ?, status = ?, is_admin = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['status'],
            $data['is_admin'],
            $id
        ]);
    }

    /**
     * Abstração de verificação de senha para garantir compatibilidade futura.
     */
    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Obtém todos os utilizadores (uso administrativo).
     */
    public function getAllUsers() {
        return $this->db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
    }

    /**
     * Grava um token de recuperação com validade de 1 hora
     */
    public function setResetToken($email, $token) {
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $sql = "UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$token, $expires, $email]);
    }

    /**
     * Valida se um token existe e ainda não expirou
     */
    public function validateResetToken($token) {
        $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Atualiza a senha e limpa os campos de recuperação
     */
    public function updatePassword($userId, $password) {
        // SÊNIOR: Nunca salve senhas sem o hash BCRYPT
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hash, $userId]);
    }

    /**
     * Busca o usuário pelo token de verificação enviado por e-mail
     */
    public function findByToken($token) {
        // IMPORTANTE: Use prepare() para evitar erros de PDO e garantir a busca
        $sql = "SELECT * FROM users WHERE verification_token = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ativa a conta do usuário e limpa o token
     */
    public function activate($userId) {
        // Definimos o status como 'active' e preenchemos a data de verificação
        $sql = "UPDATE users SET status = 'active', email_verified_at = NOW(), verification_token = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }    
}