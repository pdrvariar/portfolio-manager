<?php
require_once __DIR__ . '/AssetQuoteStrategy.php';

class DolarQuoteStrategy implements AssetQuoteStrategy {
    private $assetModel;

    public function __construct() {
        // Assume que a classe Asset está disponível (carregada via autoloader ou inclusão prévia)
        $this->assetModel = new Asset();
    }

    /**
     * Atualiza cotações do Dólar (CAMBIO)
     * Baseia-se na série 3698 (Dólar comercial venda - mensal - fim de período) do BCB
     */
    public function updateQuotes($asset, $confirmFull = false) {
        if (!$asset) {
            return ['success' => false, 'message' => 'Ativo inválido'];
        }

        $dolar = $this->fetchDolarMonthly();
        if (!$dolar['success']) {
            return $dolar;
        }

        $dolarData = $dolar['data'];
        $dolarByMonth = [];
        foreach ($dolarData as $row) {
            $ym = substr($row['date'], 0, 7);
            $dolarByMonth[$ym] = $row['price'];
        }

        $assetId = $asset['id'];
        $systemData = $this->assetModel->getHistoricalData($assetId);

        // Determinar se precisa full refresh
        $requiresFull = false;
        if (count($systemData) >= 2) {
            $last1 = $systemData[count($systemData)-1];
            $last2 = $systemData[count($systemData)-2];

            $ym1 = substr($last1['reference_date'], 0, 7);
            $ym2 = substr($last2['reference_date'], 0, 7);

            $p1f = $dolarByMonth[$ym1] ?? null;
            $p2f = $dolarByMonth[$ym2] ?? null;

            $eq = function($a, $b) {
                if ($a === null || $b === null) return false;
                return abs(floatval($a) - floatval($b)) < 1e-8;
            };

            if (!($eq($p1f, $last1['price']) && $eq($p2f, $last2['price']))) {
                $requiresFull = true;
            }
        }

        if ($requiresFull && !$confirmFull) {
            return [
                'success' => false,
                'requires_full_refresh' => true,
                'provider_start' => $dolar['min_date'],
                'provider_end' => $dolar['max_date'],
                'message' => 'Divergência detectada entre as últimas cotações do Dólar. Confirme para atualizar tudo.'
            ];
        }

        // Atualização total
        if ($requiresFull && $confirmFull) {
            $count = $this->assetModel->importHistoricalData($assetId, $dolarData);
            return [
                'success' => true,
                'updated_count' => $count,
                'requires_full_refresh' => false,
                'message' => "Atualização completa realizada. Registros importados: {$count}."
            ];
        }

        // Incremental
        $updated = 0;
        $existingMonths = [];
        foreach ($systemData as $r) {
            $existingMonths[substr($r['reference_date'], 0, 7)] = true;
        }
        $lastSystemDate = !empty($systemData) ? $systemData[count($systemData)-1]['reference_date'] : null;
        $lastSystemYm = $lastSystemDate ? substr($lastSystemDate, 0, 7) : null;

        foreach ($dolarData as $row) {
            $ym = substr($row['date'], 0, 7);
            if (($lastSystemYm === null || $ym > $lastSystemYm) && !isset($existingMonths[$ym])) {
                $r = $this->assetModel->addHistoricalData($assetId, $row['date'], $row['price']);
                if ($r['success']) $updated++;
            }
        }

        return [
            'success' => true,
            'updated_count' => $updated,
            'requires_full_refresh' => false,
            'message' => $updated > 0 ? ("Incremental: ".$updated." novos meses adicionados.") : 'Nada novo a adicionar.'
        ];
    }

    protected function fetchDolarMonthly() {
        // Dólar comercial (venda) - Mensal - fim de período - Série 3698
        $codigoSerie = 3698;
        // Buscamos a partir de Dezembro/1994 para que, ao deslocar 1 mês para frente, 
        // tenhamos a cotação inicial em 01/01/1995 (referente ao fechamento do mês anterior).
        $dataInicio = "01/12/1994";
        $apiUrl = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.$codigoSerie/dados?formato=json&dataInicial=$dataInicio";

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json, text/plain, */*',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) PortfolioManager/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => "Erro ao acessar API BCB: HTTP $httpCode"];
        }

        $dados = json_decode($response, true);
        if (!is_array($dados)) {
            return ['success' => false, 'message' => "Resposta inválida da API do BCB."];
        }

        $result = [];
        $minDate = null;
        $maxDate = null;
        
        $hoje = new DateTime();
        $todayYm = $hoje->format('Y-m');

        foreach ($dados as $item) {
            if (!isset($item['data']) || !isset($item['valor'])) continue;

            $parts = explode('/', $item['data']);
            if (count($parts) !== 3) continue;

            $d = (int)$parts[0];
            $m = (int)$parts[1];
            $y = (int)$parts[2];

            $dataYm = sprintf("%04d-%02d", $y, $m);

            // Requisito: não carregar o mês em aberto (mês atual dos dados)
            if ($dataYm === $todayYm) {
                continue;
            }

            // Conforme pedido: o mês de Janeiro de 2026 corresponde a cotação em 01/02/2026.
            // A série 3698 traz dados de fim de período. 
            // Ex: 31/01/1995 -> salvamos como 01/01/1995, mas o valor é o de fim de mês (início do próximo).
            try {
                $targetDate = "$y-" . sprintf("%02d", $m) . "-01";
                
                // Evitar carregar dados futuros (redundante mas seguro)
                if ($targetDate > $hoje->format('Y-m-d')) {
                    continue;
                }

                $result[] = [
                    'date' => $targetDate,
                    'price' => (float)$item['valor']
                ];

                if ($minDate === null || $targetDate < $minDate) $minDate = $targetDate;
                if ($maxDate === null || $targetDate > $maxDate) $maxDate = $targetDate;
            } catch (Exception $e) {
                continue;
            }
        }

        return [
            'success' => true,
            'data' => $result,
            'min_date' => $minDate,
            'max_date' => $maxDate
        ];
    }
}
