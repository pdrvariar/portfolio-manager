<?php

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Payment\PaymentRefundClient;

class MercadoPagoService {
    public function __construct() {
        $accessToken = trim(getenv('MERCADOPAGO_ACCESS_TOKEN') ?: ($_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? ''), "\"' ");
        if (empty($accessToken)) {
            error_log("AVISO: MERCADOPAGO_ACCESS_TOKEN está vazio no .env.");
        } else {
            $isTest = strpos($accessToken, 'TEST-') === 0;
            error_log("MP INFO: Token configurado (Sufixo: " . substr($accessToken, -5) . " | Ambiente: " . ($isTest ? 'TESTE/SANDBOX' : 'PRODUÇÃO') . ")");
        }
        MercadoPagoConfig::setAccessToken($accessToken);
        // Removido setRuntimeEnviroment LOCAL para permitir conexões externas padrão
    }

    public function createSubscriptionPreference($userId, $userEmail, $planType = 'monthly') {
        $client = new PreferenceClient();
        
        $baseUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://localhost');
        
        $price = ($planType === 'yearly') ? 179.40 : 29.90; // R$ 14.95/mês no anual (50% desc)
        $title = "Assinatura Plano PRO " . ($planType === 'yearly' ? 'Anual' : 'Mensal') . " - Portfolio Manager";

        $preferenceData = [
            "items" => [
                [
                    "id" => "plan_pro_" . $planType,
                    "title" => $title,
                    "description" => "Acesso ilimitado a ferramentas avançadas de análise de portfólio.",
                    "quantity" => 1,
                    "currency_id" => "BRL",
                    "unit_price" => (float)$price
                ]
            ],
            "payer" => [
                "email" => $userEmail
            ],
            "back_urls" => [
                "success" => $baseUrl . "/index.php?url=subscription/success",
                "failure" => $baseUrl . "/index.php?url=subscription/failure",
                "pending" => $baseUrl . "/index.php?url=subscription/pending"
            ],
            "external_reference" => (string)$userId,
            "statement_descriptor" => "PORTFOLIO PRO"
        ];

        try {
            error_log("MP INFO: Tentando criar preferência para " . $userEmail);
            $preference = $client->create($preferenceData);
            if (!$preference || !isset($preference->init_point)) {
                $respData = $preference ? json_encode($preference) : 'NULL';
                error_log("ERRO MP: Preferência criada mas sem init_point. Resposta: " . $respData);
            } else {
                error_log("SUCESSO MP: Preferência criada ID: " . $preference->id);
            }
            return $preference;
        } catch (Exception $e) {
            $this->logException($e, "ERRO CRÍTICO MP (Preference)");
            return null;
        }
    }

    /**
     * Processa pagamento via API transparente.
     *
     * @param array       $paymentData Dados do Brick/formulário
     * @param int         $userId      ID do usuário pagante
     * @param string|null $idempotencyKey Chave de idempotência (evita cobranças duplas)
     */
    public function processPayment($paymentData, $userId, $idempotencyKey = null) {
        $client = new PaymentClient();

        $planType    = $paymentData['plan_type'] ?? 'monthly';
        $description = "Assinatura Plano PRO " . ($planType === 'yearly' ? 'Anual' : 'Mensal') . " - Portfolio Manager";

        $request = [
            "transaction_amount" => (float)$paymentData['transaction_amount'],
            "description"        => $description,
            "payment_method_id"  => $paymentData['payment_method_id'],
            "payer" => [
                "email"          => $paymentData['payer']['email'],
                "identification" => [
                    "type"   => $paymentData['payer']['identification']['type'] ?? 'CPF',
                    "number" => preg_replace('/\D/', '', $paymentData['payer']['identification']['number'] ?? '')
                ]
            ],
            "external_reference"  => (string)$userId,
            "statement_descriptor" => "PORTFOLIO PRO"
        ];

        $debugRequest = $request;
        if (isset($paymentData['token'])) {
            $debugRequest['token'] = '***' . substr($paymentData['token'], -4);
        }
        error_log("MP DEBUG: Request Payload: " . json_encode($debugRequest));

        if (isset($paymentData['token'])) {
            $request["token"]        = $paymentData['token'];
            $request["installments"] = (int)$paymentData['installments'];
            if (isset($paymentData['issuer_id']) && $paymentData['issuer_id'] !== "") {
                $request["issuer_id"] = (string)$paymentData['issuer_id'];
            }
        }

        try {
            error_log("MP INFO: Processando pagamento via API (" . $paymentData['payment_method_id'] . ") para User ID: " . $userId);

            $request_options = new \MercadoPago\Client\Common\RequestOptions();
            // Usa chave fornecida ou gera uma efêmera
            $key = $idempotencyKey ?: uniqid('pay_', true);
            $request_options->setCustomHeaders(["X-Idempotency-Key: " . $key]);

            $payment = $client->create($request, $request_options);

            if (!$payment || !isset($payment->status)) {
                error_log("ERRO MP: Resposta inválida da API de pagamento.");
                return null;
            }

            return $payment;
        } catch (Exception $e) {
            $this->logException($e, "ERRO CRÍTICO MP (Payment)");
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REEMBOLSO
    // ─────────────────────────────────────────────────────────────

    /**
     * Processa reembolso total ou parcial de um pagamento aprovado.
     *
     * @param string $mpPaymentId ID do pagamento no Mercado Pago
     * @param float  $amount      Valor a reembolsar (0 = reembolso total)
     * @return object|null        Objeto de reembolso ou null em caso de erro
     */
    public function processRefund(string $mpPaymentId, float $amount = 0) {
        try {
            $refundClient = new PaymentRefundClient();

            $request_options = new \MercadoPago\Client\Common\RequestOptions();
            $request_options->setCustomHeaders(["X-Idempotency-Key: refund_" . $mpPaymentId]);

            $body = $amount > 0 ? ['amount' => $amount] : [];

            $refund = $refundClient->refund((int)$mpPaymentId, $body, $request_options);

            if (!$refund || !isset($refund->id)) {
                error_log("ERRO MP (Refund): resposta inválida – " . json_encode($refund));
                return null;
            }

            error_log("MP REFUND OK: ID {$refund->id} para payment {$mpPaymentId}, valor R$ {$amount}");
            return $refund;
        } catch (Exception $e) {
            $this->logException($e, "ERRO CRÍTICO MP (Refund)");
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // CONSULTA DE PAGAMENTO
    // ─────────────────────────────────────────────────────────────

    /**
     * Consulta um pagamento diretamente na API do MP por ID.
     */
    public function getPaymentById(int $paymentId) {
        try {
            $client  = new PaymentClient();
            $payment = $client->get($paymentId);
            return $payment;
        } catch (Exception $e) {
            $this->logException($e, "ERRO MP (getPaymentById {$paymentId})");
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // WEBHOOK / ASSINATURA HMAC
    // ─────────────────────────────────────────────────────────────

    /**
     * Valida a assinatura HMAC-SHA256 enviada pelo Mercado Pago no header x-signature.
     *
     * Documentação: https://www.mercadopago.com.br/developers/pt/docs/your-integrations/notifications/webhooks
     *
     * @param string $dataId      $body['data']['id']
     * @param string $xSignature  Header 'x-signature'      (ts=...;v1=...)
     * @param string $xRequestId  Header 'x-request-id'
     * @return bool
     */
    public function verifyWebhookSignature(string $dataId, string $xSignature, string $xRequestId): bool {
        $secret = trim(getenv('MERCADOPAGO_WEBHOOK_SECRET') ?: ($_ENV['MERCADOPAGO_WEBHOOK_SECRET'] ?? ''));

        // Sem segredo configurado: logar aviso e permitir (modo lax p/ dev)
        if (empty($secret)) {
            error_log("MP WEBHOOK AVISO: MERCADOPAGO_WEBHOOK_SECRET não configurado. Validação ignorada.");
            return true;
        }

        // Extrai ts e v1 do header
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$k, $v] = explode('=', trim($part), 2) + [1 => ''];
            $parts[trim($k)] = trim($v);
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            error_log("MP WEBHOOK: header x-signature malformado: {$xSignature}");
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$parts['ts']};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    private function logException($e, $context) {
        $msg = $context . ": " . $e->getMessage();
        
        $body = null;
        $statusCode = 0;
        if ($e instanceof \MercadoPago\Exceptions\MPApiException && $e->getApiResponse()) {
            $response = $e->getApiResponse();
            $body = $response->getContent();
            $statusCode = $response->getStatusCode();
            $msg .= " | Status: " . $statusCode;
            $msg .= " | Body: " . json_encode($body);
        } elseif (method_exists($e, 'getResponse') && $e->getResponse()) {
            $response = $e->getResponse();
            $body = $response->getContent();
            $statusCode = $response->getStatusCode();
            $msg .= " | Status: " . $statusCode;
            $msg .= " | Body: " . json_encode($body);
        } else {
            $msg .= " | Trace: " . substr($e->getTraceAsString(), 0, 500);
        }
        
        error_log($msg);
        
        // SÊNIOR: Log extra em arquivo local para facilitar debug se error_log estiver inacessível
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logPath = $logDir . '/mercadopago.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL;
        @file_put_contents($logPath, $logMsg, FILE_APPEND);

        $GLOBALS['last_mp_error'] = $msg;
        
        // Tentar extrair uma mensagem amigável para o usuário
        $friendlyMessage = "Erro ao processar com Mercado Pago.";
        
        if ($body) {
            if (isset($body['message'])) {
                $friendlyMessage .= " Detalhe: " . $body['message'];
            }
            
            // SÊNIOR: Adiciona causas específicas se houver (muito comum em 400 Bad Request)
            if (isset($body['cause']) && is_array($body['cause'])) {
                $causes = [];
                foreach ($body['cause'] as $cause) {
                    if (is_array($cause) && isset($cause['description'])) {
                        $causes[] = $cause['description'];
                    } elseif (is_string($cause)) {
                        $causes[] = $cause;
                    }
                }
                if (!empty($causes)) {
                    $friendlyMessage .= " (" . implode(", ", $causes) . ")";
                }
            }

            // SÊNIOR: Dica específica para Unauthorized use of live credentials
            if (isset($body['message']) && strpos($body['message'], 'Unauthorized use of live credentials') !== false) {
                $friendlyMessage .= ". DICA: Você está usando chaves de PRODUÇÃO (APP_USR-) com cartões de teste. Use credenciais de TESTE (TEST-).";
            }
        }
        
        // Se for erro de validação (400), a mensagem é crucial para o desenvolvedor/usuário
        if ($statusCode === 400 && strpos($friendlyMessage, 'Detalhe') === false) {
            $friendlyMessage .= " (Erro de validação nos parâmetros enviados)";
        }

        // SÊNIOR: Dica para Internal Error (500)
        if ($statusCode === 500) {
            $friendlyMessage = "Erro interno no Mercado Pago. Por favor, tente novamente em alguns minutos. Se o erro persistir, tente usar outro cartão ou método de pagamento.";
        }

        Session::setFlash('error_debug', $friendlyMessage);
    }
}
