<?php

namespace app\services;

use DateTime;

class ProjectionService {
    /**
     * Calcula a projeção de patrimônio futuro baseada em um valor inicial, aportes e taxa de retorno.
     *
     * @param float $initialCapital Capital inicial para a projeção (geralmente o valor final do backtest)
     * @param float $annualReturn Taxa de retorno anual esperada (em porcentagem, ex: 10.5)
     * @param float $monthlyDeposit Valor do aporte mensal (em moeda)
     * @param int $years Quantidade de anos para a projeção (padrão 10)
     * @return array Dados da projeção mensal
     */
    public function calculateProjection($initialCapital, $annualReturn, $monthlyDeposit, $years = 10) {
        $projection = [];
        $currentValue = $initialCapital;
        $totalInvested = $initialCapital;
        
        // Taxa mensal equivalente (juros compostos)
        $monthlyRate = pow(1 + ($annualReturn / 100), 1 / 12) - 1;
        
        $months = $years * 12;
        $startDate = new DateTime();
        
        // Adiciona o ponto inicial
        $projection[$startDate->format('Y-m-d')] = [
            'total_value' => round($currentValue, 2),
            'total_invested' => round($totalInvested, 2),
            'interest_earned' => 0,
            'is_projection' => true
        ];
        
        for ($i = 1; $i <= $months; $i++) {
            $startDate->modify('+1 month');
            
            // 1. Aplica o rendimento mensal sobre o saldo do mês anterior (conforme solicitado pelo usuário)
            $interest = $currentValue * $monthlyRate;
            $currentValue += $interest;
            
            // 2. Em seguida, adiciona o aporte mensal (conforme solicitado pelo usuário)
            $currentValue += $monthlyDeposit;
            $totalInvested += $monthlyDeposit;
            
            $projection[$startDate->format('Y-m-d')] = [
                'total_value' => round($currentValue, 2),
                'total_invested' => round($totalInvested, 2),
                'interest_earned' => round($currentValue - $totalInvested, 2),
                'is_projection' => true
            ];
        }
        
        return $projection;
    }

    /**
     * Prepara os dados de projeção para o gráfico.
     *
     * @param array $projectionData Dados retornados por calculateProjection
     * @return array Estrutura para o Chart.js
     */
    public function formatProjectionChart($projectionData) {
        $labels = [];
        $values = [];
        $invested = [];
        
        foreach ($projectionData as $date => $data) {
            $labels[] = date('m/Y', strtotime($date));
            $values[] = $data['total_value'];
            $invested[] = $data['total_invested'];
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Patrimônio Projetado',
                    'data' => $values,
                    'borderColor' => '#0d6efd',
                    'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ],
                [
                    'label' => 'Total Investido (Aportes)',
                    'data' => $invested,
                    'borderColor' => '#6c757d',
                    'backgroundColor' => 'rgba(108, 117, 125, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderDash' => [5, 5]
                ]
            ]
        ];
    }
}
