<?php
class ProfileController {
    private $params;

    public function __construct($params = []) {
        $this->params = $params;
        Session::start(); // Garante o acesso aos dados do utilizador
    }

    public function index() {
        Auth::checkAuthentication();
        
        // Tenta obter o nome de diferentes chaves comuns de sessão caso 'user_name' falhe
        $name = Session::get('user_name') ?? Session::get('name') ?? 'Admin';
        $email = Session::get('user_email') ?? Session::get('email') ?? 'admin@exemplo.com';

        $user = [
            'name'  => $name,
            'email' => $email,
            'role'  => Session::get('is_admin') ? 'Administrador' : 'Investidor'
        ];
        
        require_once __DIR__ . '/../views/profile/index.php';
    }

    public function update() {
        Auth::checkAuthentication();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lógica: Aqui chamaria o User Model para gravar no BD
            // Ex: $this->userModel->update($_SESSION['user_id'], $_POST);
            
            // Atualiza a sessão para refletir a mudança no cabeçalho imediatamente
            Session::set('user_name', $_POST['name']);
            
            Session::setFlash('success', 'Dados do perfil atualizados com sucesso!');
            header('Location: /index.php?url=profile');
            exit;
        }
    }
}