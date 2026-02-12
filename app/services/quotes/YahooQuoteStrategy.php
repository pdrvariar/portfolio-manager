<?php
require_once __DIR__ . '/AssetQuoteStrategy.php';

class YahooQuoteStrategy implements AssetQuoteStrategy {
    private $assetModel;

    public function __construct() {
        $this->assetModel = new Asset();
    }

    public function updateQuotes($asset, $confirmFull = false) {
        if (!$asset) {
            return ['success' => false, 'message' => 'Ativo inválido'];
        }

        // SELIC (TAXA_MENSAL) ficará para outro método
        if (strcasecmp(trim($asset['code']), 'SELIC') === 0 || strcasecmp(trim($asset['asset_type']), 'TAXA_MENSAL') === 0) {
            return ['success' => false, 'message' => 'Atualização via Yahoo não disponível para SELIC/Taxa mensal.'];
        }

        $ticker = $asset['yahoo_ticker'] ?: $asset['code'];
        $yahoo = $this->fetchYahooMonthly($ticker);
        if (!$yahoo['success']) {
            return $yahoo; // Contém message de erro
        }

        $yahooData = $yahoo['data']; // [['date' => 'YYYY-MM-01', 'price' => float], ...]
        $yahooByMonth = [];
        foreach ($yahooData as $row) {
            $ym = substr($row['date'], 0, 7);
            $yahooByMonth[$ym] = $row['price'];
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

            $p1y = $yahooByMonth[$ym1] ?? null;
            $p2y = $yahooByMonth[$ym2] ?? null;

            $eq = function($a,$b){
                if ($a === null || $b === null) return false;
                return abs(floatval($a) - floatval($b)) < 1e-8;
            };

            if (!($eq($p1y, $last1['price']) && $eq($p2y, $last2['price']))) {
                $requiresFull = true;
            }
        }

        if ($requiresFull && !$confirmFull) {
            return [
                'success' => false,
                'requires_full_refresh' => true,
                'provider_start' => $yahoo['min_date'],
                'provider_end' => $yahoo['max_date'],
                'yahoo_start' => $yahoo['min_date'], // manter por compatibilidade
                'yahoo_end' => $yahoo['max_date'],
                'message' => 'Divergência detectada entre as últimas cotações. Pode ter havido split/agrupamento/dividendos. Confirme para atualizar tudo.'
            ];
        }

        // Atualização total
        if ($requiresFull && $confirmFull) {
            $count = $this->assetModel->importHistoricalData($assetId, $yahooData);
            return [
                'success' => true,
                'updated_count' => $count,
                'requires_full_refresh' => false,
                'message' => "Atualização completa realizada. Registros importados: {$count}."
            ];
        }

        // Incremental: inserir apenas meses após a última data do sistema e que não existam
        $updated = 0;
        $existingMonths = [];
        foreach ($systemData as $r) {
            $existingMonths[substr($r['reference_date'], 0, 7)] = true;
        }
        $lastSystemDate = !empty($systemData) ? $systemData[count($systemData)-1]['reference_date'] : null;
        $lastSystemYm = $lastSystemDate ? substr($lastSystemDate,0,7) : null;

        foreach ($yahooData as $row) {
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

    private function fetchYahooMonthly($ticker) {
        $period1 = 0; // desde 1970
        $period2 = time();
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . urlencode($ticker);
        $query = http_build_query([
            'period1' => $period1,
            'period2' => $period2,
            'interval' => '1mo',
            'events' => 'history',
            'includeAdjustedClose' => 'true'
        ]);

        $ch = curl_init($url . '?' . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            return ['success' => false, 'message' => 'Erro na requisição ao Yahoo: ' . curl_error($ch)];
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            return ['success' => false, 'message' => 'Falha ao obter dados do Yahoo. HTTP ' . $code];
        }

        $json = json_decode($resp, true);
        $chart = $json['chart']['result'][0] ?? null;
        if (!$chart) {
            return ['success' => false, 'message' => 'Dados do Yahoo indisponíveis'];
        }

        $timestamps = $chart['timestamp'] ?? [];
        $ind = $chart['indicators'] ?? [];
        $adj = $ind['adjclose'][0]['adjclose'] ?? null;
        $close = $ind['quote'][0]['close'] ?? null;
        $prices = $adj ?: $close;
        if (!$timestamps || !$prices) {
            return ['success' => false, 'message' => 'Dados incompletos do Yahoo'];
        }

        $data = [];
        $dataMap = [];
        $todayYm = gmdate('Y-m');
        $minDate = null; $maxDate = null;

        for ($i = 0; $i < count($timestamps); $i++) {
            $ts = intval($timestamps[$i]);
            $yahooDay = gmdate('d', $ts);
            $yahooYm = gmdate('Y-m', $ts);

            // 1. Ignorar o mês em aberto. 
            // Se o ponto do Yahoo é do mês atual e não é o dia 1, é o preço "live" do mês em curso.
            // Se for dia 1 do mês atual, segundo o usuário, ele representa o mês anterior (que já fechou).
            if ($yahooYm === $todayYm && $yahooDay !== '01') {
                continue;
            }

            // 2. Ajuste de Data: O Yahoo usa 01/Mês Seguinte para representar o fechamento do Mês Anterior.
            // O sistema quer que a rentabilidade de Janeiro seja gravada como 01/01.
            $dateObj = new DateTime("@$ts");
            $dateObj->setTimezone(new DateTimeZone('UTC'));
            $dateObj->modify('-1 month');
            $systemDate = $dateObj->format('Y-m-01');

            // 3. Apenas meses totalmente fechados no sistema.
            // Se o ajuste resultou no mês atual ou futuro, ignoramos.
            if ($systemDate >= $todayYm . '-01') {
                continue;
            }

            $p = $prices[$i];
            if ($p === null) continue;

            // Usar Map para evitar duplicatas (o último registro para o mesmo mês vence)
            $dataMap[$systemDate] = floatval($p);
        }

        // Converter Map de volta para a lista ordenada
        ksort($dataMap);
        foreach ($dataMap as $dt => $p) {
            $data[] = ['date' => $dt, 'price' => $p];
            if ($minDate === null || $dt < $minDate) $minDate = $dt;
            if ($maxDate === null || $dt > $maxDate) $maxDate = $dt;
        }

        return [
            'success' => true,
            'data' => $data,
            'min_date' => $minDate,
            'max_date' => $maxDate
        ];
    }
}
