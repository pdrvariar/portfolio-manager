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
     * Baseia-se na série 10813 (PTAX Dólar comercial venda - diário) do BCB,
     * pegando o ÚLTIMO DIA ÚTIL de cada mês — mesma regra do Yahoo Finance.
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
        // PTAX Dólar comercial (venda) - Diário - Série 10813
        // Usamos a série DIÁRIA e pegamos o ÚLTIMO DIA ÚTIL de cada mês,
        // replicando exatamente o mesmo comportamento do Yahoo Finance (interval=1mo → último dia de negociação).
        //
        // ATENÇÃO: A Série 3698 (mensal) representa a MÉDIA MENSAL da PTAX, NÃO o fim de período,
        // apesar da descrição oficial dizer "fim de período". Verificado empiricamente:
        // Jan/2026 → Média mensal = 5.3380, Último dia útil (30/01/2026) = 5.2295 (diferença ~2,1%).
        // Por isso usamos a série diária 10813 agrupada pelo último dia de cada mês.
        //
        // LIMITE DE API: A BCB retorna HTTP 406 para janelas muito grandes (~>1500 registros).
        // Solução: buscar em janelas de 4 anos para ficar seguro (~1000 registros/janela).
        $codigoSerie = 10813;
        $hoje = new DateTime();
        $todayYm = $hoje->format('Y-m');

        // Gera janelas de 4 anos de 1995 até hoje
        $startYear = 1995;
        $endYear   = (int)$hoje->format('Y');
        $chunks    = [];
        for ($y = $startYear; $y <= $endYear; $y += 4) {
            $chunkEnd = min($y + 3, $endYear);
            $chunks[] = [
                'ini' => "02/01/{$y}",
                'fim' => "31/12/{$chunkEnd}"
            ];
        }

        $allDados = [];
        foreach ($chunks as $chunk) {
            $apiUrl = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.{$codigoSerie}/dados"
                    . "?formato=json&dataInicial={$chunk['ini']}&dataFinal={$chunk['fim']}";

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json, text/plain, */*',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) PortfolioManager/1.0'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return ['success' => false, 'message' => "Erro ao acessar API BCB ({$chunk['ini']} a {$chunk['fim']}): HTTP {$httpCode}"];
            }

            $dados = json_decode($response, true);
            if (!is_array($dados)) {
                return ['success' => false, 'message' => "Resposta inválida da API do BCB ({$chunk['ini']} a {$chunk['fim']})."];
            }

            $allDados = array_merge($allDados, $dados);
        }

        // Agrupa por mês e mantém APENAS o último registro de cada mês (= último dia útil).
        // A API BCB retorna os dados em ordem cronológica, portanto a última sobrescrição
        // de cada chave 'YYYY-MM' será sempre o último dia útil daquele mês.
        $monthlyData = [];

        foreach ($allDados as $item) {
            if (!isset($item['data']) || !isset($item['valor'])) continue;

            $parts = explode('/', $item['data']);
            if (count($parts) !== 3) continue;

            $m = (int)$parts[1];
            $y = (int)$parts[2];

            $dataYm = sprintf("%04d-%02d", $y, $m);

            // Ignorar mês atual (em aberto) e dados futuros
            if ($dataYm >= $todayYm) {
                continue;
            }

            // Sobrescreve para que ao final fique o último dia útil do mês
            $monthlyData[$dataYm] = [
                'date'  => "$y-" . sprintf("%02d", $m) . "-01",
                'price' => (float)$item['valor']
            ];
        }

        ksort($monthlyData);

        $result  = [];
        $minDate = null;
        $maxDate = null;

        foreach ($monthlyData as $row) {
            $result[] = ['date' => $row['date'], 'price' => $row['price']];
            if ($minDate === null || $row['date'] < $minDate) $minDate = $row['date'];
            if ($maxDate === null || $row['date'] > $maxDate) $maxDate = $row['date'];
        }

        return [
            'success'  => true,
            'data'     => $result,
            'min_date' => $minDate,
            'max_date' => $maxDate
        ];
    }
}
