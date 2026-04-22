<?php

/**
 * SubscriptionPlan Model
 * Gestão dinâmica de preços e configuração de parcelamentos.
 */
class SubscriptionPlan {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─────────────────────────────────────────────────────────────
    // PREÇOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Retorna o preço ativo atual para um plano.
     * Respeita janela de tempo (effective_from / effective_until).
     */
    public function getPriceFor(string $planType): float {
        $row = $this->getActivePlanRow($planType);
        return $row ? (float)$row['price'] : ($planType === 'yearly' ? 179.40 : 29.90);
    }

    /**
     * Retorna o registro completo do plano ativo (inclui label, original_price, etc.)
     */
    public function getActivePlanRow(string $planType): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM subscription_plans
            WHERE plan_type = ?
              AND is_active = 1
              AND effective_from <= NOW()
              AND (effective_until IS NULL OR effective_until > NOW())
            ORDER BY effective_from DESC
            LIMIT 1
        ");
        $stmt->execute([$planType]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retorna preços ativos para todos os planos, indexados por plan_type.
     */
    public function getActivePrices(): array {
        $result = [];
        foreach (['monthly', 'yearly'] as $type) {
            $row = $this->getActivePlanRow($type);
            if ($row) {
                $installment = $this->getInstallmentConfig($type);
                $row['installment'] = $installment;
                $result[$type] = $row;
            }
        }
        return $result;
    }

    /**
     * Cria novo registro de preço (inserta histórico, expira o anterior).
     */
    public function updatePrice(string $planType, array $data, int $adminId): int|false {
        try {
            $this->db->beginTransaction();

            // Expirar plano ativo anterior (se effective_until não estava definido)
            $this->db->prepare("
                UPDATE subscription_plans
                SET effective_until = NOW(), updated_at = NOW()
                WHERE plan_type = ? AND is_active = 1
                  AND (effective_until IS NULL OR effective_until > NOW())
            ")->execute([$planType]);

            // Inserir novo registro
            $stmt = $this->db->prepare("
                INSERT INTO subscription_plans
                    (plan_type, price, original_price, label, description, is_active,
                     effective_from, effective_until, updated_by, notes)
                VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $planType,
                (float)$data['price'],
                !empty($data['original_price']) ? (float)$data['original_price'] : null,
                $data['label']          ?? null,
                $data['description']    ?? null,
                $data['effective_from'] ?? date('Y-m-d H:i:s'),
                !empty($data['effective_until']) ? $data['effective_until'] : null,
                $adminId,
                $data['notes']          ?? null,
            ]);

            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("SubscriptionPlan::updatePrice error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Histórico completo de preços de um plano.
     */
    public function getPriceHistory(string $planType): array {
        $stmt = $this->db->prepare("
            SELECT sp.*, u.username AS admin_name
            FROM subscription_plans sp
            LEFT JOIN users u ON sp.updated_by = u.id
            WHERE sp.plan_type = ?
            ORDER BY sp.effective_from DESC
        ");
        $stmt->execute([$planType]);
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────
    // PARCELAMENTO
    // ─────────────────────────────────────────────────────────────

    public function getInstallmentConfig(string $planType): array {
        $stmt = $this->db->prepare("
            SELECT * FROM subscription_installment_configs WHERE plan_type = ?
        ");
        $stmt->execute([$planType]);
        return $stmt->fetch() ?: [
            'plan_type'             => $planType,
            'max_installments'      => 1,
            'interest_free_up_to'   => 1,
            'monthly_interest_rate' => 0.0000,
            'is_active'             => 1,
        ];
    }

    public function updateInstallmentConfig(string $planType, array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO subscription_installment_configs
                (plan_type, max_installments, interest_free_up_to, monthly_interest_rate, is_active)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                max_installments      = VALUES(max_installments),
                interest_free_up_to   = VALUES(interest_free_up_to),
                monthly_interest_rate = VALUES(monthly_interest_rate),
                is_active             = VALUES(is_active),
                updated_at            = NOW()
        ");
        return $stmt->execute([
            $planType,
            max(1, (int)($data['max_installments'] ?? 1)),
            max(1, (int)($data['interest_free_up_to'] ?? 1)),
            max(0, (float)($data['monthly_interest_rate'] ?? 0)),
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);
    }

    /**
     * Calcula as parcelas de um valor conforme configuração.
     * Retorna array com detalhes de cada parcela.
     */
    public static function calculateInstallments(float $price, array $config): array {
        $max  = (int)($config['max_installments'] ?? 1);
        $free = (int)($config['interest_free_up_to'] ?? 1);
        $rate = (float)($config['monthly_interest_rate'] ?? 0);
        $rows = [];

        for ($n = 1; $n <= $max; $n++) {
            if ($n <= $free || $rate == 0) {
                $installmentValue = $price / $n;
                $totalValue       = $price;
                $hasInterest      = false;
            } else {
                // Price + Compound interest: P*(r*(1+r)^n)/((1+r)^n - 1)
                $factor           = $rate * pow(1 + $rate, $n) / (pow(1 + $rate, $n) - 1);
                $installmentValue = $price * $factor;
                $totalValue       = round($installmentValue * $n, 2);
                $hasInterest      = true;
            }
            $rows[] = [
                'installments'      => $n,
                'installment_value' => round($installmentValue, 2),
                'total_value'       => $totalValue,
                'has_interest'      => $hasInterest,
            ];
        }
        return $rows;
    }
}

