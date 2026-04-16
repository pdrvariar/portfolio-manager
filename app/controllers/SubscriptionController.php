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
        
        // SÊNIOR: Para checkout transparente, recebemos os dados via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json = file_get_contents('php://input');
            $paymentData = json_decode($json, true);

            if (!$paymentData) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Dados de pagamento inválidos.']);
                exit;
            }

            $payment = $mpService->processPayment($paymentData, $user['id']);

            if ($payment && isset($payment->status)) {
                // Se o pagamento for aprovado, atualizamos o plano
                if ($payment->status === 'approved') {
                    $userModel = new User();
                    if ($userModel->updatePlan($user['id'], 'pro')) {
                        Auth::updateSessionPlan('pro');
                    }
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'status' => $payment->status,
                    'status_detail' => $payment->status_detail,
                    'id' => $payment->id
                ]);
                exit;
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Falha ao processar pagamento com Mercado Pago.']);
            exit;
        }

        // Fallback para gerar preferência (Checkout Pro / Modal) se necessário
        $preference = $mpService->createSubscriptionPreference($user['id'], $user['email']);
        
        if ($preference && isset($preference->id)) {
            // SÊNIOR: Retornar JSON se for uma requisição AJAX para o modal
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['preferenceId' => $preference->id]);
                exit;
            }
            // Caso contrário, redireciona como fallback (comportamento atual)
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
        
        // Simulação de atualização de plano - SÊNIOR: Garantindo que o plano mude no BD
        if ($userModel->updatePlan($userId, 'pro')) {
            // Atualizar a sessão para refletir o novo plano
            $_SESSION['user_plan'] = 'pro';
        } else {
            error_log("ERRO CRÍTICO: Não foi possível atualizar o plano do usuário ID " . $userId . " para PRO após pagamento.");
            Session::setFlash('warning', 'Seu pagamento foi aprovado, mas houve um problema ao atualizar seu plano. Nossa equipe já foi notificada.');
        }

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
