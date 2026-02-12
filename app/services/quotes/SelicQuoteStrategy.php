<?php
require_once __DIR__ . '/AssetQuoteStrategy.php';

class SelicQuoteStrategy implements AssetQuoteStrategy {
    private $assetModel;

    public function __construct() {
        $this->assetModel = new Asset();
    }

    /**
     * Atualiza cotações da SELIC (TAXA_MENSAL)
     * Baseia-se na série 11 (SELIC diária % a.a.) do BCB e capitaliza para o mês
     */
    public function updateQuotes($asset, $confirmFull = false) {
        if (!$asset) {
            return ['success' => false, 'message' => 'Ativo inválido'];
        }

        $selic = $this->fetchSelicMonthly();
        if (!$selic['success']) {
            return $selic;
        }

        $selicData = $selic['data'];
        $selicByMonth = [];
        foreach ($selicData as $row) {
            $ym = substr($row['date'], 0, 7);
            $selicByMonth[$ym] = $row['price'];
        }

        $assetId = $asset['id'];
        $systemData = $this->assetModel->getHistoricalData($assetId);

        // Determinar se precisa full refresh (mesma lógica do Fed/Yahoo)
        $requiresFull = false;
        if (count($systemData) >= 2) {
            $last1 = $systemData[count($systemData)-1];
            $last2 = $systemData[count($systemData)-2];

            $ym1 = substr($last1['reference_date'], 0, 7);
            $ym2 = substr($last2['reference_date'], 0, 7);

            $p1f = $selicByMonth[$ym1] ?? null;
            $p2f = $selicByMonth[$ym2] ?? null;

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
                'provider_start' => $selic['min_date'],
                'provider_end' => $selic['max_date'],
                'yahoo_start' => $selic['min_date'], 
                'yahoo_end' => $selic['max_date'],
                'message' => 'Divergência detectada entre as últimas taxas SELIC. Confirme para atualizar tudo.'
            ];
        }

        // Atualização total
        if ($requiresFull && $confirmFull) {
            $count = $this->assetModel->importHistoricalData($assetId, $selicData);
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

        foreach ($selicData as $row) {
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

    protected function fetchSelicMonthly() {
        // SELIC diária (% a.a.) - Série 11
        $baseUrl = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.11/dados";
        
        $hoje = new DateTime();
        $fimGeral = clone $hoje;
        $inicioGeral = new DateTime('1994-01-01');
        
        $todosDados = [];
        $atualInicio = clone $inicioGeral;
        
        // A API do BCB limita consultas de séries diárias a janelas de no máximo 10 anos.
        while ($atualInicio < $fimGeral) {
            $atualFim = clone $atualInicio;
            $atualFim->modify('+10 years');
            if ($atualFim > $fimGeral) {
                $atualFim = clone $fimGeral;
            }
            
            $dataIniStr = $atualInicio->format('d/m/Y');
            $dataFimStr = $atualFim->format('d/m/Y');
            
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

            if ($httpCode === 200) {
                $dados = json_decode($response, true);
                if (is_array($dados)) {
                    $todosDados = array_merge($todosDados, $dados);
                }
            } else if ($httpCode !== 404) {
                return ['success' => false, 'message' => "Erro ao acessar API BCB ($dataIniStr - $dataFimStr): HTTP $httpCode"];
            }
            
            $atualInicio = clone $atualFim;
            $atualInicio->modify('+1 day');
        }

        if (empty($todosDados)) {
            return ['success' => false, 'message' => "Dados SELIC não encontrados na resposta do BCB."];
        }

        $byMonth = [];
        $todayYm = $hoje->format('Y-m');

        foreach ($todosDados as $item) {
            if (!isset($item['data']) || !isset($item['valor'])) continue;
            
            $parts = explode('/', $item['data']);
            if (count($parts) !== 3) continue;
            
            $ym = $parts[2] . '-' . $parts[1];
            
            // Requisito: não carregar se o mês ainda não estiver fechado
            if ($ym === $todayYm) {
                continue;
            }

            $byMonth[$ym][] = (float)$item['valor'];
        }

        $result = [];
        $minDate = null;
        $maxDate = null;

        ksort($byMonth);

        foreach ($byMonth as $ym => $valores) {
            // Cálculo taxa mensal composta conforme dados da série 11 do BCB:
            // A série 11 já retorna a taxa DIÁRIA efetiva em percentual (% a.d.)
            // Portanto, o fator diário é (1 + taxa_dia / 100)
            $fatorAcumulado = 1.0;
            foreach ($valores as $taxaDia) {
                $fatorAcumulado *= (1 + ($taxaDia / 100));
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
