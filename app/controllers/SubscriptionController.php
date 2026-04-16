<?php

class SubscriptionController {
    private $params;

    public function __construct($params) {
        $this->params = $params;
    }

    public function upgrade() {
        Auth::checkAuthentication();
        
        if (Auth::isPro()) {
            Session::setFlash('info', 'Você já possui o Plano PRO ativo!');
            header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
            exit;
        }

        require_once __DIR__ . '/../views/subscription/upgrade.php';
    }

    public function checkout() {
        Auth::checkAuthentication();
        
        $user = Auth::getUser();
        $mpService = new MercadoPagoService();
        
        $preference = $mpService->createSubscriptionPreference($user['id'], $user['email']);
        
        if ($preference && isset($preference->init_point)) {
            header('Location: ' . $preference->init_point);
            exit;
        }

        // Adicionar mensagem de flash para o usuário
        $errorMsg = 'Ocorreu um erro ao iniciar o checkout com Mercado Pago.';
        
        // Se houver erro de debug, adicionamos ele
        if ($debugMsg = Session::getFlash('error_debug')) {
            $errorMsg .= ' Detalhes técnicos: ' . $debugMsg;
        } elseif (!$preference) {
            $errorMsg .= ' Não foi possível estabelecer conexão com a API do Mercado Pago. Verifique sua conexão de internet ou chaves de API.';
        } elseif (!isset($preference->init_point)) {
            $errorMsg .= ' A resposta do servidor de pagamento foi inválida (init_point ausente).';
        }

        Session::setFlash('error', $errorMsg);
        header('Location: /index.php?url=' . obfuscateUrl('upgrade'));
        exit;
    }

    public function success() {
        Auth::checkAuthentication();
        // Em um fluxo real, aqui você verificaria o status do pagamento via API ou Webhook
        // Por enquanto, vamos simular que deu certo e atualizar o plano do usuário
        $userModel = new User();
        $userId = Auth::getUserId();
        
        // Simulação de atualização de plano
        $userModel->update($userId, ['plan' => 'pro']);
        
        // Atualizar a sessão para refletir o novo plano
        $_SESSION['user_plan'] = 'pro';

        require_once __DIR__ . '/../views/subscription/success.php';
    }

    public function failure() {
        Auth::checkAuthentication();
        require_once __DIR__ . '/../views/subscription/failure.php';
    }

    public function pending() {
        Auth::checkAuthentication();
        require_once __DIR__ . '/../views/subscription/pending.php';
    }
}
