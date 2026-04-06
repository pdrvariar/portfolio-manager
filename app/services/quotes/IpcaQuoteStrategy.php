<?php
require_once __DIR__ . '/AssetQuoteStrategy.php';

class IpcaQuoteStrategy implements AssetQuoteStrategy {
    private $assetModel;

    public function __construct() {
        $this->assetModel = new Asset();
    }

    /**
     * Atualiza dados do IPCA (TAXA_MENSAL)
     * Baseia-se na série 433 (IPCA - Variação mensal) do BCB
     */
    public function updateQuotes($asset, $confirmFull = false) {
        if (!$asset) {
            return ['success' => false, 'message' => 'Ativo inválido'];
        }

        $ipca = $this->fetchIpcaMonthly();
        if (!$ipca['success']) {
            return $ipca;
        }

        $ipcaData = $ipca['data'];
        $ipcaByMonth = [];
        foreach ($ipcaData as $row) {
            $ym = substr($row['date'], 0, 7);
            $ipcaByMonth[$ym] = $row['price'];
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

            $p1f = $ipcaByMonth[$ym1] ?? null;
            $p2f = $ipcaByMonth[$ym2] ?? null;

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
                'provider_start' => $ipca['min_date'],
                'provider_end' => $ipca['max_date'],
                'yahoo_start' => $ipca['min_date'], 
                'yahoo_end' => $ipca['max_date'],
                'message' => 'Divergência detectada entre as últimas taxas IPCA. Confirme para atualizar tudo.'
            ];
        }

        // Atualização total
        if ($requiresFull && $confirmFull) {
            $count = $this->assetModel->importHistoricalData($assetId, $ipcaData);
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

        foreach ($ipcaData as $row) {
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

    protected function fetchIpcaMonthly() {
        // IPCA mensal (%) - Série 433
        $baseUrl = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados";
        
        $hoje = new DateTime();
        $todayYm = $hoje->format('Y-m');
        $inicioGeral = new DateTime('1994-01-01');
        
        $dataIniStr = $inicioGeral->format('d/m/Y');
        $dataFimStr = $hoje->format('d/m/Y');
        
        $apiUrl = $baseUrl . "?formato=json&dataInicial=$dataIniStr&dataFinal=$dataFimStr";
        
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
            return ['success' => false, 'message' => "Erro ao acessar API BCB (IPCA): HTTP $httpCode"];
        }

        $dados = json_decode($response, true);
        if (!is_array($dados) || empty($dados)) {
            return ['success' => false, 'message' => "Dados IPCA não encontrados na resposta do BCB."];
        }

        $result = [];
        $minDate = null;
        $maxDate = null;

        foreach ($dados as $item) {
            if (!isset($item['data']) || !isset($item['valor'])) continue;
            
            $parts = explode('/', $item['data']);
            if (count($parts) !== 3) continue;
            
            $ym = $parts[2] . '-' . $parts[1];
            
            // Requisito: não carregar se o mês ainda não estiver fechado (geralmente IPCA do mês atual não existe, mas por precaução)
            if ($ym === $todayYm) {
                continue;
            }

            $date = $ym . '-01';
            $price = (float)$item['valor'];

            $result[] = [
                'date' => $date,
                'price' => $price
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
