<?php

use App\Core\EntityManagerFactory;
use App\Entities\User;

class ProfileController {
    private $params;

    public function __construct($params = []) {
        $this->params = $params;
        Session::start();
    }

    /**
     * Exibe o perfil com dados reais do banco de dados
     */
    public function index() {
        Auth::checkAuthentication();
        
        $entityManager = EntityManagerFactory::createEntityManager();
        $userId = Auth::getCurrentUserId();
        $userEntity = $entityManager->find(User::class, $userId);

        if (!$userEntity) {
            Session::setFlash('error', 'Utilizador não encontrado.');
            header('Location: /');
            exit;
        }

        $user = [
            'name'       => $userEntity->getFullName() ?: $userEntity->getUsername(),
            'username'   => $userEntity->getUsername(),
            'email'      => $userEntity->getEmail(),
            'phone'      => $userEntity->getPhone() ?? '',
            'birth_date' => $userEntity->getBirthDate() ? $userEntity->getBirthDate()->format('Y-m-d') : '',
            'role'       => $userEntity->isAdmin() ? 'Administrador' : 'Investidor',
            'status'     => $userEntity->getStatus() ?? 'pending',
            'verified'   => $userEntity->getEmailVerifiedAt() !== null,
            'created_at' => $userEntity->getCreatedAt() ? $userEntity->getCreatedAt()->format('Y-m-d H:i:s') : null
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

            $entityManager = EntityManagerFactory::createEntityManager();
            $userId = Auth::getCurrentUserId();
            $userEntity = $entityManager->find(User::class, $userId);

            if (!$userEntity) {
                Session::setFlash('error', 'Utilizador não encontrado.');
                header('Location: /index.php?url=' . obfuscateUrl('profile'));
                exit;
            }

            $fullName = sanitize($_POST['full_name']);
            $phone = sanitize($_POST['phone']);
            $birthDate = $_POST['birth_date'];

            $age = date_diff(date_create($birthDate), date_create('today'))->y;
            if ($age < 18) {
                Session::setFlash('error', 'A data de nascimento indica que você é menor de idade.');
                header('Location: /index.php?url=' . obfuscateUrl('profile'));
                exit;
            }

            $userEntity->setFullName($fullName)
                       ->setPhone($phone)
                       ->setBirthDate(new \DateTime($birthDate));

            $entityManager->flush();
            Session::set('user_name', $fullName);
            Session::setFlash('success', 'Perfil atualizado com sucesso!');
            
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

            $entityManager = EntityManagerFactory::createEntityManager();
            $userId = Auth::getCurrentUserId();
            $userEntity = $entityManager->find(User::class, $userId);

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // 3. Validações Sênior de Segurança
            if (!$userEntity->verifyPassword($currentPassword)) {
                Session::setFlash('error', 'A senha atual informada está incorreta.');
            } 
            elseif ($newPassword !== $confirmPassword) {
                Session::setFlash('error', 'A nova senha e a confirmação não coincidem.');
            } 
            elseif (strlen($newPassword) < 6) {
                Session::setFlash('error', 'A nova senha deve ter pelo menos 6 caracteres.');
            } 
            else {
                $userEntity->setPassword($newPassword);
                $entityManager->flush();
                Session::setFlash('success', 'Sua senha foi alterada com sucesso!');
            }

            header('Location: /index.php?url=' . obfuscateUrl('profile'));
            exit;
        }
    }
}