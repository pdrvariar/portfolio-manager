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
        
        // No futuro aqui integrará com a API do Mercado Pago para gerar o link dinâmico
        // Por enquanto, redireciona para um link de checkout genérico ou simulado
        $mercadoPagoUrl = "https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=SIMULATED_ID";
        
        header('Location: ' . $mercadoPagoUrl);
        exit;
    }
}
