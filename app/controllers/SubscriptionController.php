<?php

class SubscriptionController {
    private $params;

    // Preços definidos centralmente (single source of truth)
    const PRICES = ['monthly' => 29.90, 'yearly' => 179.40];

    public function __construct($params) {
        $this->params = $params;
    }

    // ─────────────────────────────────────────────────────────────
    // PÁGINA DE UPGRADE / CONTRATAÇÃO
    // ─────────────────────────────────────────────────────────────

    public function upgrade() {
        Auth::checkAuthentication();

        $user          = Auth::getUser();
        $userModel     = new User();
        $userData      = $userModel->findById($user['id']);
        $subModel      = new Subscription();
        $activeSub     = $subModel->findActiveByUserId($user['id']);

        // PRO anual → redirecionar para gestão
        if (Auth::isPro() && !Auth::isAdmin() && $activeSub && $activeSub['plan_type'] === 'yearly') {
            Session::setFlash('info', 'Você já possui o Plano PRO Anual ativo. Gerencie sua assinatura abaixo.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        // PRO mensal → modo upgrade para anual
        $upgradeMode        = Auth::isPro() && !Auth::isAdmin() && $activeSub && $activeSub['plan_type'] === 'monthly';
        $proratedCredit     = $upgradeMode ? $subModel->calculateProratedCredit($activeSub) : 0;
        $proratedYearlyPrice = $upgradeMode ? round(self::PRICES['yearly'] - $proratedCredit, 2) : self::PRICES['yearly'];

        require_once __DIR__ . '/../views/subscription/upgrade.php';
    }

    // ─────────────────────────────────────────────────────────────
    // CHECKOUT — PROCESSAMENTO DO PAGAMENTO
    // ─────────────────────────────────────────────────────────────

    public function checkout() {
        Auth::checkAuthentication();

        $user = Auth::getUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Fallback para Checkout Pro (modal)
            $mpService  = new MercadoPagoService();
            $preference = $mpService->createSubscriptionPreference($user['id'], $user['email']);
            if ($preference && isset($preference->id)) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['preferenceId' => $preference->id]);
                    exit;
                }
                header('Location: ' . $preference->init_point);
                exit;
            }
            Session::setFlash('error', 'Erro ao iniciar o checkout.');
            header('Location: /index.php?url=' . obfuscateUrl('upgrade'));
            exit;
        }

        $json        = file_get_contents('php://input');
        $paymentData = json_decode($json, true);

        if (!$paymentData) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Dados de pagamento inválidos.']);
            exit;
        }

        $planType  = in_array($paymentData['plan_type'] ?? '', ['monthly', 'yearly'])
                     ? $paymentData['plan_type'] : 'monthly';
        $isUpgrade = (bool)($paymentData['is_upgrade'] ?? false);

        // ── Calcular valor esperado ──────────────────────────────
        $subModel        = new Subscription();
        $expectedAmount  = self::PRICES[$planType];
        $activeSub       = null;

        if ($isUpgrade && $planType === 'yearly') {
            $activeSub      = $subModel->findActiveByUserId($user['id']);
            if ($activeSub && $activeSub['plan_type'] === 'monthly') {
                $credit         = $subModel->calculateProratedCredit($activeSub);
                $expectedAmount = round(self::PRICES['yearly'] - $credit, 2);
            } else {
                $isUpgrade = false; // sem assinatura mensal ativa, tratar como nova
            }
        }

        // Sobrescrever valor para evitar fraude
        $paymentData['transaction_amount'] = $expectedAmount;
        $paymentData['plan_type']          = $planType;

        // ── Chave de idempotência (deterministicamente única por dia) ──
        $idempotencyKey = 'pay_' . $user['id'] . '_' . $planType . '_' . date('Ymd');

        // ── Guarda duplicata: mesmo pagamento aprovado no mesmo dia ──
        $existingSub = $subModel->findByIdempotencyKey($idempotencyKey);
        if ($existingSub && $existingSub['status'] === 'active') {
            Auth::updateSessionPlan('pro');
            header('Content-Type: application/json');
            echo json_encode(['status' => 'approved', 'id' => $existingSub['mp_payment_id'], 'status_detail' => 'already_approved']);
            exit;
        }

        // ── Processar pagamento ──────────────────────────────────
        $mpService = new MercadoPagoService();
        $payment   = $mpService->processPayment($paymentData, $user['id'], $idempotencyKey);

        if ($payment && isset($payment->status)) {
            if ($payment->status === 'approved') {
                $userModel  = new User();
                $now        = date('Y-m-d H:i:s');
                $expiration = ($planType === 'yearly')
                    ? date('Y-m-d H:i:s', strtotime('+1 year'))
                    : date('Y-m-d H:i:s', strtotime('+1 month'));

                // Expirar assinatura anterior em caso de upgrade
                if ($isUpgrade && $activeSub) {
                    $subModel->cancel($activeSub['id'], 'immediate');
                }

                // Criar registro de assinatura
                $subModel->create([
                    'user_id'              => $user['id'],
                    'mp_payment_id'        => (string)$payment->id,
                    'mp_idempotency_key'   => $idempotencyKey,
                    'plan_type'            => $planType,
                    'status'               => 'active',
                    'amount_paid'          => $expectedAmount,
                    'starts_at'            => $now,
                    'expires_at'           => $expiration,
                    'refund_eligible_until'=> date('Y-m-d H:i:s', strtotime('+7 days')),
                    'notes'                => $isUpgrade ? 'Upgrade de mensal para anual' : null,
                ]);

                // Atualizar plano do usuário
                if ($userModel->updatePlan($user['id'], 'pro', $expiration, $planType, (string)$payment->id, 'active')) {
                    Auth::updateSessionPlan('pro');
                }

                // E-mail de boas-vindas (não-bloqueante)
                try {
                    $userData = $userModel->findById($user['id']);
                    EmailService::sendSubscriptionWelcome(
                        $userData['email'],
                        $userData['full_name'],
                        $planType,
                        $expiration
                    );
                } catch (Exception $e) {
                    error_log("E-mail welcome falhou: " . $e->getMessage());
                }

                logActivity("Assinatura PRO {$planType} ativada. Payment: {$payment->id}", $user['id']);
            }

            header('Content-Type: application/json');
            echo json_encode([
                'status'        => $payment->status,
                'status_detail' => $payment->status_detail,
                'id'            => $payment->id,
                'debug_info'    => ($payment->status !== 'approved') ? [
                    'message' => $payment->status_detail,
                    'id'      => $payment->id
                ] : null
            ]);
            exit;
        }

        $detailedError = Session::getFlash('error_debug') ?: 'Falha ao processar pagamento com Mercado Pago.';
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $detailedError]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // GERENCIAR ASSINATURA (self-service)
    // ─────────────────────────────────────────────────────────────

    public function manage() {
        Auth::checkAuthentication();

        $user      = Auth::getUser();
        $userModel = new User();
        $userData  = $userModel->findById($user['id']);
        $subModel  = new Subscription();

        $activeSub   = $subModel->findActiveByUserId($user['id']);
        $history     = $subModel->findByUserId($user['id']);
        $refundEligible = $activeSub ? $subModel->isRefundEligible($activeSub) : false;
        $daysRemaining  = $activeSub ? $subModel->getDaysRemaining($activeSub) : 0;
        $usagePercent   = $activeSub ? $subModel->getUsagePercent($activeSub) : 0;
        $proratedCredit = ($activeSub && $activeSub['plan_type'] === 'monthly')
                          ? $subModel->calculateProratedCredit($activeSub) : 0;

        $title = 'Gerenciar Assinatura';
        require_once __DIR__ . '/../views/subscription/manage.php';
    }

    // ─────────────────────────────────────────────────────────────
    // CANCELAR ASSINATURA
    // ─────────────────────────────────────────────────────────────

    public function cancel() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de segurança inválido. Tente novamente.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        $user      = Auth::getUser();
        $subModel  = new Subscription();
        $activeSub = $subModel->findActiveByUserId($user['id']);

        if (!$activeSub) {
            Session::setFlash('error', 'Nenhuma assinatura ativa encontrada.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        $cancelType = in_array($_POST['cancel_type'] ?? '', ['immediate', 'end_of_period'])
                      ? $_POST['cancel_type'] : 'end_of_period';

        $subModel->cancel($activeSub['id'], $cancelType);

        if ($cancelType === 'immediate') {
            $userModel = new User();
            $userModel->updatePlan($user['id'], 'starter', null, null, $activeSub['mp_payment_id'], 'canceled');
            Auth::updateSessionPlan('starter');
            Session::setFlash('success', 'Assinatura cancelada. Seu acesso PRO foi encerrado imediatamente.');
        } else {
            // Marca o status do usuário como cancelado mas mantém o plano até expirar
            $db = Database::getInstance()->getConnection();
            $db->prepare("UPDATE users SET subscription_status = 'canceled' WHERE id = ?")->execute([$user['id']]);
            $expiryFmt = date('d/m/Y', strtotime($activeSub['expires_at']));
            Session::setFlash('success', "Assinatura cancelada. Você continua com acesso PRO até {$expiryFmt}.");
        }

        // E-mail de confirmação
        try {
            $userModel  = $userModel ?? new User();
            $userData   = $userModel->findById($user['id']);
            EmailService::sendCancellationConfirmation(
                $userData['email'],
                $userData['full_name'],
                $cancelType,
                $activeSub['expires_at']
            );
        } catch (Exception $e) {
            error_log("E-mail cancel falhou: " . $e->getMessage());
        }

        logActivity("Assinatura cancelada (tipo: {$cancelType})", $user['id']);
        header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // REEMBOLSO (Garantia 7 dias)
    // ─────────────────────────────────────────────────────────────

    public function refund() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de segurança inválido. Tente novamente.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        $user      = Auth::getUser();
        $subModel  = new Subscription();
        $activeSub = $subModel->findActiveByUserId($user['id']);

        if (!$activeSub) {
            Session::setFlash('error', 'Nenhuma assinatura ativa encontrada.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        if (!$subModel->isRefundEligible($activeSub)) {
            Session::setFlash('error', 'A janela de 7 dias para reembolso já foi encerrada.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        if (empty($activeSub['mp_payment_id'])) {
            Session::setFlash('error', 'Pagamento não encontrado para reembolso.');
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        $mpService = new MercadoPagoService();
        $refund    = $mpService->processRefund($activeSub['mp_payment_id'], (float)$activeSub['amount_paid']);

        if (!$refund || !isset($refund->id)) {
            $err = Session::getFlash('error_debug') ?: 'Falha ao processar reembolso. Tente novamente ou entre em contato.';
            Session::setFlash('error', $err);
            header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
            exit;
        }

        // Atualizar registros
        $subModel->markRefunded($activeSub['id'], (string)$refund->id, (float)$activeSub['amount_paid']);
        $userModel = new User();
        $userModel->updatePlan($user['id'], 'starter', null, null, $activeSub['mp_payment_id'], 'refunded');
        Auth::updateSessionPlan('starter');

        // E-mail de confirmação
        try {
            $userData = $userModel->findById($user['id']);
            EmailService::sendRefundConfirmation(
                $userData['email'],
                $userData['full_name'],
                (float)$activeSub['amount_paid']
            );
        } catch (Exception $e) {
            error_log("E-mail refund falhou: " . $e->getMessage());
        }

        logActivity("Reembolso processado. MP Refund ID: {$refund->id}", $user['id']);
        Session::setFlash('success', 'Reembolso de R$ ' . number_format($activeSub['amount_paid'], 2, ',', '.') . ' processado! O crédito aparecerá em até 5 dias úteis.');
        header('Location: /index.php?url=' . obfuscateUrl('subscription/manage'));
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // UPGRADE MENSAL → ANUAL (com crédito proporcional)
    // ─────────────────────────────────────────────────────────────

    public function upgradePlan() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?url=' . obfuscateUrl('upgrade'));
            exit;
        }

        // Redirecionar para o checkout com flag is_upgrade=true
        // O JS já envia os dados do brick para /checkout com is_upgrade: true
        // Este endpoint serve como fallback redirect
        header('Location: /index.php?url=' . obfuscateUrl('upgrade'));
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // WEBHOOK DO MERCADO PAGO (IPN)
    // ─────────────────────────────────────────────────────────────

    /**
     * Endpoint público chamado pelo Mercado Pago para notificações de pagamento.
     * NÃO requer autenticação de sessão.
     */
    public function webhook() {
        // Desligar buffer e cabeçalhos
        header('Content-Type: application/json');

        $rawBody  = file_get_contents('php://input');
        $data     = json_decode($rawBody, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'payload inválido']);
            exit;
        }

        // Validar assinatura HMAC
        $xSignature = $_SERVER['HTTP_X_SIGNATURE']  ?? '';
        $xRequestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        $dataId     = $data['data']['id'] ?? '';

        $mpService = new MercadoPagoService();
        if (!$mpService->verifyWebhookSignature($dataId, $xSignature, $xRequestId)) {
            error_log("MP WEBHOOK: assinatura inválida. Payload: " . $rawBody);
            http_response_code(401);
            echo json_encode(['error' => 'assinatura inválida']);
            exit;
        }

        $action = $data['action'] ?? '';
        error_log("MP WEBHOOK: action={$action}, id={$dataId}");

        // Processar apenas eventos de pagamento
        if (!in_array($action, ['payment.created', 'payment.updated'])) {
            echo json_encode(['ok' => true, 'note' => 'evento ignorado']);
            exit;
        }

        $paymentId = (int)$dataId;
        if (!$paymentId) {
            echo json_encode(['ok' => true]);
            exit;
        }

        // Consultar pagamento na API
        $payment = $mpService->getPaymentById($paymentId);
        if (!$payment || !isset($payment->status)) {
            http_response_code(500);
            echo json_encode(['error' => 'falha ao consultar pagamento']);
            exit;
        }

        // Atualizar assinatura idempotentemente
        $subModel = new Subscription();
        $sub      = $subModel->findByMpPaymentId((string)$paymentId);

        if (!$sub) {
            // Pagamento não vinculado a nenhuma assinatura — ignorar
            echo json_encode(['ok' => true, 'note' => 'assinatura não encontrada para este pagamento']);
            exit;
        }

        // Mapear status do MP para status da assinatura
        $mpStatus = $payment->status;

        if ($mpStatus === 'approved' && $sub['status'] !== 'active') {
            $subModel->updateStatus($sub['id'], 'active');
        } elseif (in_array($mpStatus, ['rejected', 'cancelled']) && in_array($sub['status'], ['pending', 'active'])) {
            $subModel->updateStatus($sub['id'], 'failed');
            $userModel = new User();
            $userModel->updatePlan($sub['user_id'], 'starter', null, null, null, 'expired');
        } elseif ($mpStatus === 'refunded' && $sub['status'] !== 'refunded') {
            // Reembolso iniciado pelo lado do MP (ex.: contestação)
            $subModel->markRefunded($sub['id'], (string)$paymentId, (float)$sub['amount_paid']);
            $userModel = new User();
            $userModel->updatePlan($sub['user_id'], 'starter', null, null, null, 'refunded');
        }

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // PÁGINAS DE RETORNO (Checkout Pro)
    // ─────────────────────────────────────────────────────────────

    public function success() {
        Auth::checkAuthentication();
        $userModel = new User();
        $userId    = Auth::getUserId();

        $paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? null;

        if ($paymentId) {
            try {
                $mpService = new MercadoPagoService();
                $payment   = $mpService->getPaymentById((int)$paymentId);

                if ($payment && isset($payment->status) && $payment->status === 'approved') {
                    if ((string)($payment->external_reference ?? '') === (string)$userId) {
                        $subModel  = new Subscription();
                        $existing  = $subModel->findByMpPaymentId((string)$paymentId);

                        if (!$existing) {
                            $planType   = 'monthly';
                            $expiration = date('Y-m-d H:i:s', strtotime('+1 month'));
                            $now        = date('Y-m-d H:i:s');

                            $subModel->create([
                                'user_id'              => $userId,
                                'mp_payment_id'        => (string)$paymentId,
                                'plan_type'            => $planType,
                                'status'               => 'active',
                                'amount_paid'          => self::PRICES[$planType],
                                'starts_at'            => $now,
                                'expires_at'           => $expiration,
                                'refund_eligible_until'=> date('Y-m-d H:i:s', strtotime('+7 days')),
                                'notes'                => 'Via Checkout Pro',
                            ]);

                            $userModel->updatePlan($userId, 'pro', $expiration, $planType, (string)$paymentId, 'active');
                            Auth::updateSessionPlan('pro');
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("ERRO success(): " . $e->getMessage());
            }
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
