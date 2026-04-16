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
            error_log("MP INFO: Token configurado (Sufixo: " . substr($accessToken, -5) . ")");
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
            "token" => $paymentData['token'],
            "description" => "Assinatura Plano PRO - Portfolio Manager",
            "installments" => (int)$paymentData['installments'],
            "payment_method_id" => $paymentData['payment_method_id'],
            "issuer_id" => $paymentData['issuer_id'],
            "payer" => [
                "email" => $paymentData['payer']['email'],
                "identification" => [
                    "type" => $paymentData['payer']['identification']['type'],
                    "number" => $paymentData['payer']['identification']['number']
                ]
            ],
            "external_reference" => (string)$userId,
            "statement_descriptor" => "PORTFOLIO PRO"
        ];

        try {
            error_log("MP INFO: Processando pagamento via API para User ID: " . $userId);
            $payment = $client->create($request);
            return $payment;
        } catch (Exception $e) {
            $this->logException($e, "ERRO CRÍTICO MP (Payment)");
            return null;
        }
    }

    private function logException($e, $context) {
        $msg = $context . ": " . $e->getMessage();
        
        if ($e instanceof \MercadoPago\Exceptions\MPApiException && $e->getApiResponse()) {
            $response = $e->getApiResponse();
            $msg .= " | Status: " . $response->getStatusCode();
            $msg .= " | Body: " . json_encode($response->getContent());
        } elseif (method_exists($e, 'getResponse') && $e->getResponse()) {
            $response = $e->getResponse();
            $msg .= " | Status: " . $response->getStatusCode();
            $msg .= " | Body: " . json_encode($response->getContent());
        } else {
            $msg .= " | Trace: " . substr($e->getTraceAsString(), 0, 500);
        }
        
        error_log($msg);
        $GLOBALS['last_mp_error'] = $msg;
        Session::setFlash('error_debug', $msg);
    }
}
