<?php

class ProfileController {
    private $userModel;
    private $params;

    public function __construct($params = []) {
        $this->params = $params;
        $this->userModel = new User();
        Session::start();
    }

    /**
     * Exibe o perfil com dados reais do banco de dados
     */
    public function index() {
        Auth::checkAuthentication();
        
        $userId = Auth::getCurrentUserId();
        $userData = $this->userModel->findById($userId);

        if (!$userData) {
            Session::setFlash('error', 'Utilizador não encontrado.');
            header('Location: /');
            exit;
        }

        $user = [
            'name'       => $userData['full_name'] ?? $userData['username'],
            'username'   => $userData['username'],
            'email'      => $userData['email'],
            'phone'      => $userData['phone'] ?? '',
            'birth_date' => $userData['birth_date'] ?? '',
            'role'       => $userData['is_admin'] ? 'Administrador' : 'Investidor',
            'status'     => $userData['status'] ?? 'pending',
            'verified'   => !empty($userData['email_verified_at']),
            'created_at' => $userData['created_at'] // ADICIONE ESTA LINHA
        ];
        
        require_once __DIR__ . '/../views/profile/index.php';
    }

    /**
     * Processa a atualização dos dados básicos
     */
    public function update() {
        Auth::checkAuthentication();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=profile');
            }            

            $userId = Auth::getCurrentUserId();
            $data = [
                'full_name'  => sanitize($_POST['full_name']),
                'phone'      => sanitize($_POST['phone']),
                'birth_date' => $_POST['birth_date']
            ];

            $age = date_diff(date_create($data['birth_date']), date_create('today'))->y;
            if ($age < 18) {
                Session::setFlash('error', 'A data de nascimento indica que você é menor de idade.');
                header('Location: /index.php?url=' . obfuscateUrl('profile'));
                exit;
            }

            if ($this->userModel->update($userId, $data)) {
                Session::set('user_name', $data['full_name']);
                Session::setFlash('success', 'Perfil atualizado com sucesso!');
            } else {
                Session::setFlash('error', 'Erro ao atualizar os dados.');
            }
            
            header('Location: /index.php?url=' . obfuscateUrl('profile'));
            exit;
        }
    }

    /**
     * NOVO: Processa a troca de senha com validação de segurança
     */
    public function changePassword() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Validação CSRF
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Erro de segurança: Token CSRF inválido.');
                header('Location: /index.php?url=' . obfuscateUrl('profile'));
                exit;
            }

            $userId = Auth::getCurrentUserId();
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // 2. Busca o usuário atual no banco para validar a senha antiga
            $user = $this->userModel->findById($userId);

            // 3. Validações Sênior de Segurança
            if (!password_verify($currentPassword, $user['password'])) {
                Session::setFlash('error', 'A senha atual informada está incorreta.');
            } 
            elseif ($newPassword !== $confirmPassword) {
                Session::setFlash('error', 'A nova senha e a confirmação não coincidem.');
            } 
            elseif (strlen($newPassword) < 6) {
                Session::setFlash('error', 'A nova senha deve ter pelo menos 6 caracteres.');
            } 
            else {
                // 4. Se tudo OK, faz o update usando o método que já existe no seu User.php
                if ($this->userModel->updatePassword($userId, $newPassword)) {
                    Session::setFlash('success', 'Sua senha foi alterada com sucesso!');
                } else {
                    Session::setFlash('error', 'Ocorreu um erro técnico ao tentar atualizar a senha.');
                }
            }

            header('Location: /index.php?url=' . obfuscateUrl('profile'));
            exit;
        }
    }
}