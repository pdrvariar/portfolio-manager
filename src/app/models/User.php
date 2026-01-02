<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, verification_token) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            bin2hex(random_bytes(32))
        ]);
    }
    
    public function verifyEmail($token) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET email_verified_at = NOW(), verification_token = NULL 
            WHERE verification_token = ?
        ");
        return $stmt->execute([$token]);
    }
    
    public function setResetToken($email, $token) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
            WHERE email = ?
        ");
        return $stmt->execute([$token, $email]);
    }
    
    public function resetPassword($token, $password) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires_at = NULL
            WHERE reset_token = ? AND reset_token_expires_at > NOW()
        ");
        return $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $token
        ]);
    }
}