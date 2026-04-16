<?php

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

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

    public function createSubscriptionPreference($userId, $userEmail) {
        $client = new PreferenceClient();
        
        $baseUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://localhost');
        
        $preferenceData = [
            "items" => [
                [
                    "id" => "plan_pro",
                    "title" => "Assinatura Plano PRO - Portfolio Manager",
                    "description" => "Acesso ilimitado a ferramentas avançadas de análise de portfólio.",
                    "quantity" => 1,
                    "currency_id" => "BRL",
                    "unit_price" => 29.90
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

    public function processPayment($paymentData, $userId) {
        $client = new PaymentClient();

        $request = [
            "transaction_amount" => (float)$paymentData['transaction_amount'],
            "description" => "Assinatura Plano PRO - Portfolio Manager",
            "payment_method_id" => $paymentData['payment_method_id'],
            "payer" => [
                "email" => $paymentData['payer']['email'],
                "identification" => [
                    "type" => $paymentData['payer']['identification']['type'] ?? 'CPF',
                    "number" => preg_replace('/\D/', '', $paymentData['payer']['identification']['number'] ?? '')
                ]
            ],
            "external_reference" => (string)$userId,
            "statement_descriptor" => "PORTFOLIO PRO"
        ];

        // SÊNIOR: Log do payload (sem dados sensíveis) para debug
        $debugRequest = $request;
        if (isset($paymentData['token'])) {
            $debugRequest['token'] = '***' . substr($paymentData['token'], -4);
        }
        error_log("MP DEBUG: Request Payload: " . json_encode($debugRequest));

        // Se for cartão, adiciona token e parcelas
        if (isset($paymentData['token'])) {
            $request["token"] = $paymentData['token'];
            $request["installments"] = (int)$paymentData['installments'];
            if (isset($paymentData['issuer_id'])) {
                $request["issuer_id"] = (int)$paymentData['issuer_id'];
            }
        }

        try {
            error_log("MP INFO: Processando pagamento via API (" . $paymentData['payment_method_id'] . ") para User ID: " . $userId);
            
            // SÊNIOR: Adicionando chave de idempotência para evitar cobranças duplicadas em caso de retry
            $request_options = new \MercadoPago\Client\Common\RequestOptions();
            $request_options->setCustomHeaders(["X-Idempotency-Key: " . uniqid('pay_', true)]);

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

    private function logException($e, $context) {
        $msg = $context . ": " . $e->getMessage();
        
        $body = null;
        if ($e instanceof \MercadoPago\Exceptions\MPApiException && $e->getApiResponse()) {
            $response = $e->getApiResponse();
            $body = $response->getContent();
            $msg .= " | Status: " . $response->getStatusCode();
            $msg .= " | Body: " . json_encode($body);
        } elseif (method_exists($e, 'getResponse') && $e->getResponse()) {
            $response = $e->getResponse();
            $body = $response->getContent();
            $msg .= " | Status: " . $response->getStatusCode();
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
        if ($body && isset($body['message'])) {
            $friendlyMessage .= " Detalhe: " . $body['message'];
            
            // SÊNIOR: Dica específica para Unauthorized use of live credentials
            if (strpos($body['message'], 'Unauthorized use of live credentials') !== false) {
                $friendlyMessage .= ". DICA: Você está usando chaves de PRODUÇÃO (APP_USR-) com cartões de teste ou em ambiente local. Para testes, use credenciais de TESTE (TEST-) e cartões de teste específicos.";
            }
        }
        if ($body && isset($body['cause']) && is_array($body['cause'])) {
            foreach ($body['cause'] as $cause) {
                if (isset($cause['description'])) {
                    $friendlyMessage .= " (" . $cause['description'] . ")";
                }
            }
        }

        Session::setFlash('error_debug', $friendlyMessage);
    }
}
