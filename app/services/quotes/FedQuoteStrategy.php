<?php
require_once __DIR__ . '/AssetQuoteStrategy.php';

class FedQuoteStrategy implements AssetQuoteStrategy {
    private $assetModel;
    private $apiKey = 'demo'; // Pode ser parametrizado via .env no futuro

    public function __construct() {
        $this->assetModel = new Asset();
        $this->apiKey = $_ENV['FRED_API_KEY'] ?? 'demo';
    }

    public function updateQuotes($asset, $confirmFull = false) {
        if (!$asset) {
            return ['success' => false, 'message' => 'Ativo inválido'];
        }

        $fed = $this->fetchFedMonthly();
        if (!$fed['success']) {
            return $fed;
        }

        $fedData = $fed['data'];
        $fedByMonth = [];
        foreach ($fedData as $row) {
            $ym = substr($row['date'], 0, 7);
            $fedByMonth[$ym] = $row['price'];
        }

        $assetId = $asset['id'];
        $systemData = $this->assetModel->getHistoricalData($assetId);

        // Determinar se precisa full refresh (mesma lógica do Yahoo)
        $requiresFull = false;
        if (count($systemData) >= 2) {
            $last1 = $systemData[count($systemData)-1];
            $last2 = $systemData[count($systemData)-2];

            $ym1 = substr($last1['reference_date'], 0, 7);
            $ym2 = substr($last2['reference_date'], 0, 7);

            $p1f = $fedByMonth[$ym1] ?? null;
            $p2f = $fedByMonth[$ym2] ?? null;

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
                'provider_start' => $fed['min_date'],
                'provider_end' => $fed['max_date'],
                'yahoo_start' => $fed['min_date'], // Por compatibilidade com o front atual
                'yahoo_end' => $fed['max_date'],
                'message' => 'Divergência detectada entre as últimas taxas FED. Confirme para atualizar tudo.'
            ];
        }

        // Atualização total
        if ($requiresFull && $confirmFull) {
            $count = $this->assetModel->importHistoricalData($assetId, $fedData);
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

        foreach ($fedData as $row) {
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

    protected function fetchFedMonthly() {
        // Conforme pedido: trazer apenas de 1994 em diante
        $startDate = '1994-01-01';
        $endDate = date('Y-m-d');
        
        $url = "https://api.stlouisfed.org/fred/series/observations";
        $params = [
            'series_id' => 'DFF',
            'api_key' => $this->apiKey,
            'file_type' => 'json',
            'observation_start' => $startDate,
            'observation_end' => $endDate,
            'frequency' => 'd' // diária, para calcular a mensal composta
        ];

        $apiUrl = $url . '?' . http_build_query($params);
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: PortfolioManager/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            $msg = $err['error_message'] ?? "HTTP $httpCode";
            return ['success' => false, 'message' => "Erro ao acessar API FRED: $msg"];
        }

        $data = json_decode($response, true);
        if (!isset($data['observations']) || empty($data['observations'])) {
            return ['success' => false, 'message' => "Dados FRED não encontrados"];
        }

        $observations = $data['observations'];
        $byMonth = [];
        $todayYm = date('Y-m');
        foreach ($observations as $obs) {
            if ($obs['value'] === '.') continue;
            $date = $obs['date'];
            $ym = substr($date, 0, 7);

            // Ignorar o mês atual (em aberto)
            if ($ym === $todayYm) {
                continue;
            }

            $byMonth[$ym][] = (float)$obs['value'];
        }

        $result = [];
        $minDate = null;
        $maxDate = null;

        // Ordenar meses
        ksort($byMonth);

        foreach ($byMonth as $ym => $values) {
            // Cálculo da taxa mensal composta conforme fed_rate.py
            $fatorAcumulado = 1.0;
            foreach ($values as $taxaAnual) {
                // (1 + taxa_anual/100)^(1/365) - 1
                $taxaDia = pow(1 + $taxaAnual/100, 1/365) - 1;
                $fatorAcumulado *= (1 + $taxaDia);
            }
            $taxaMensal = ($fatorAcumulado - 1) * 100;

            $date = $ym . '-01';
            $result[] = [
                'date' => $date,
                'price' => $taxaMensal
            ];

            if ($minDate === null || $date < $minDate) $minDate = $date;
            if ($maxDate === null || $date > $maxDate) $maxDate = $date;
        }

        return [
            'success' => true,
            'data' => $result,
            'min_date' => $minDate,
            'max_date' => $maxDate
        ];
    }
}
