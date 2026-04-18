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
                    $planType = $paymentData['plan_type'] ?? 'monthly';
                    $expiration = ($planType === 'yearly') ? date('Y-m-d H:i:s', strtotime('+1 year')) : date('Y-m-d H:i:s', strtotime('+1 month'));
                    
                    if ($userModel->updatePlan($user['id'], 'pro', $expiration, $planType, $payment->id)) {
                        Auth::updateSessionPlan('pro');
                        Session::setFlash('success', 'Assinatura PRO ativada com sucesso! Aproveite os novos recursos.');
                    }
                }

                header('Content-Type: application/json');
                $response = [
                    'status' => $payment->status,
                    'status_detail' => $payment->status_detail,
                    'id' => $payment->id
                ];

                echo json_encode($response);
                exit;
            }

            // SÊNIOR: Se chegamos aqui, houve erro. Vamos pegar a mensagem detalhada se existir
            $detailedError = Session::getFlash('error_debug') ?: 'Falha ao processar pagamento com Mercado Pago.';
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $detailedError]);
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
        $userModel = new User();
        $userId = Auth::getUserId();

        // O Mercado Pago retorna payment_id, collection_id e preference_id na query string
        $paymentId   = $_GET['payment_id']   ?? $_GET['collection_id'] ?? null;
        $planType    = 'monthly'; // Checkout Pro não distingue; ajuste conforme necessário
        $expiration  = date('Y-m-d H:i:s', strtotime('+1 month'));

        // SÊNIOR: Se tivermos o payment_id, consultamos a API para obter o tipo de plano e prazo corretos
        if ($paymentId) {
            try {
                $mpService  = new MercadoPagoService();
                $mpClient   = new \MercadoPago\Client\Payment\PaymentClient();
                $payment    = $mpClient->get((int)$paymentId);
                if ($payment && isset($payment->status) && $payment->status === 'approved') {
                    // external_reference guarda o userId — confirmar que é o mesmo usuário
                    if ((string)($payment->external_reference ?? '') !== (string)$userId) {
                        error_log("AVISO MP: payment_id $paymentId não pertence ao usuário $userId.");
                        $paymentId = null;
                    }
                } else {
                    error_log("AVISO MP: payment_id $paymentId status: " . ($payment->status ?? 'desconhecido'));
                }
            } catch (\Exception $e) {
                error_log("ERRO MP (success lookup): " . $e->getMessage());
            }
        }

        if ($userModel->updatePlan($userId, 'pro', $expiration, $planType, $paymentId)) {
            // Atualizar a sessão para refletir o novo plano
            Auth::updateSessionPlan('pro');
        } else {
            error_log("ERRO CRÍTICO: Não foi possível atualizar o plano do usuário ID " . $userId . " para PRO após pagamento.");
            Session::setFlash('warning', 'Seu pagamento foi aprovado, mas houve um problema ao atualizar seu plano. Nossa equipe já foi notificada.');
        }

        header('Location: /index.php?url=' . obfuscateUrl('dashboard'));
        exit;
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
