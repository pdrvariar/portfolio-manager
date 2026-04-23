<?php

/**
 * DiscountCoupon Model
 * Gerenciamento completo de cupons de desconto com validade, limites e auditoria.
 */
class DiscountCoupon {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─────────────────────────────────────────────────────────────
    // LEITURA
    // ─────────────────────────────────────────────────────────────

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM discount_coupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM discount_coupons WHERE code = ?"
        );
        $stmt->execute([strtoupper(trim($code))]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT dc.*, u.username AS created_by_name,
                   (SELECT COUNT(*) FROM coupon_uses cu WHERE cu.coupon_id = dc.id) AS real_uses
            FROM discount_coupons dc
            LEFT JOIN users u ON dc.created_by = u.id
            ORDER BY dc.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getStats(): array {
        $row = $this->db->query("
            SELECT
                COUNT(*)                                                               AS total_coupons,
                SUM(CASE WHEN is_active = 1 AND (valid_until IS NULL OR valid_until > NOW()) THEN 1 ELSE 0 END) AS active_coupons,
                SUM(CASE WHEN valid_until IS NOT NULL AND valid_until <= NOW() THEN 1 ELSE 0 END)               AS expired_coupons,
                COALESCE(SUM(used_count), 0)                                           AS total_uses
            FROM discount_coupons
        ")->fetch();

        $revenue = $this->db->query("
            SELECT COALESCE(SUM(discount_applied), 0) AS total_discounted
            FROM coupon_uses
        ")->fetch();

        return array_merge($row ?: [], $revenue ?: []);
    }

    // ─────────────────────────────────────────────────────────────
    // VALIDAÇÃO
    // ─────────────────────────────────────────────────────────────

    /**
     * Valida e calcula desconto de um cupom.
     * @return array ['valid'=>bool, 'final_price'=>float, 'discount'=>float, 'message'=>string, 'coupon'=>array|null]
     */
    public function validate(string $code, string $planType, float $basePrice, int $userId = 0): array {
        try {
            $coupon = $this->findByCode($code);

            if (!$coupon) {
                return ['valid' => false, 'message' => 'Cupom não encontrado.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            if (!$coupon['is_active']) {
                return ['valid' => false, 'message' => 'Este cupom está inativo.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            $now = time();

            if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > $now) {
                return ['valid' => false, 'message' => 'Este cupom ainda não está válido.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < $now) {
                return ['valid' => false, 'message' => 'Este cupom expirou em ' . date('d/m/Y', strtotime($coupon['valid_until'])) . '.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            // Verificar plano aplicável
            if ($coupon['applies_to'] !== 'both' && $coupon['applies_to'] !== $planType) {
                $planLabel = $coupon['applies_to'] === 'monthly' ? 'Mensal' : 'Anual';
                return ['valid' => false, 'message' => "Este cupom é válido apenas para o plano {$planLabel}.", 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            // Verificar preço mínimo
            if ($coupon['min_price'] && $basePrice < (float)$coupon['min_price']) {
                return ['valid' => false, 'message' => 'Valor mínimo para este cupom não atingido.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            // Verificar limite de usos
            if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) {
                return ['valid' => false, 'message' => 'Este cupom já atingiu o limite máximo de usos.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
            }

            // Verificar se usuário já usou
            if ($userId > 0) {
                $used = $this->db->prepare(
                    "SELECT id FROM coupon_uses WHERE coupon_id = ? AND user_id = ?"
                );
                $used->execute([$coupon['id'], $userId]);
                if ($used->fetch()) {
                    return ['valid' => false, 'message' => 'Você já utilizou este cupom.', 'final_price' => $basePrice, 'discount' => 0, 'coupon' => null];
                }
            }

            // Calcular desconto
            if ($coupon['discount_type'] === 'percent') {
                $discount = round($basePrice * ((float)$coupon['discount_value'] / 100), 2);
            } else {
                $discount = (float)$coupon['discount_value'];
            }

            // Aplicar teto máximo de desconto
            if ($coupon['max_discount'] && $discount > (float)$coupon['max_discount']) {
                $discount = (float)$coupon['max_discount'];
            }

            $discount   = min($discount, $basePrice); // Não pode ser maior que o preço
            $finalPrice = round(max(0, $basePrice - $discount), 2);

            return [
                'valid'       => true,
                'message'     => "✅ Cupom <strong>{$coupon['display_name']}</strong> aplicado! Você economizou R$ " . number_format($discount, 2, ',', '.'),
                'final_price' => $finalPrice,
                'discount'    => $discount,
                'coupon'      => $coupon,
            ];
        } catch (Exception $e) {
            error_log("DiscountCoupon::validate error: " . $e->getMessage() . " | code=" . $code . " | planType=" . $planType . " | basePrice=" . $basePrice);
            return [
                'valid'       => false,
                'message'     => 'Erro ao verificar cupom. Por favor, tente novamente.',
                'final_price' => $basePrice,
                'discount'    => 0,
                'coupon'      => null,
            ];
        }
    }

    /**
     * Registra o uso de um cupom (incrementa contador + log).
     */
    public function apply(int $couponId, int $userId, float $originalPrice, float $discount, float $finalPrice, ?int $subscriptionId = null): bool {
        try {
            $this->db->beginTransaction();

            // Incrementar contador
            $this->db->prepare(
                "UPDATE discount_coupons SET used_count = used_count + 1, updated_at = NOW() WHERE id = ?"
            )->execute([$couponId]);

            // Log de uso
            $this->db->prepare("
                INSERT INTO coupon_uses (coupon_id, user_id, subscription_id, original_price, discount_applied, final_price)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    subscription_id  = VALUES(subscription_id),
                    discount_applied = VALUES(discount_applied),
                    final_price      = VALUES(final_price),
                    used_at          = NOW()
            ")->execute([$couponId, $userId, $subscriptionId, $originalPrice, $discount, $finalPrice]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("DiscountCoupon::apply error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────

    public function create(array $data, int $adminId): int|false {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO discount_coupons
                    (code, display_name, discount_type, discount_value, applies_to,
                     min_price, max_discount, max_uses, valid_from, valid_until,
                     is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                strtoupper(trim($data['code'])),
                $data['display_name'],
                in_array($data['discount_type'], ['percent','fixed']) ? $data['discount_type'] : 'percent',
                (float)$data['discount_value'],
                in_array($data['applies_to'], ['monthly','yearly','both']) ? $data['applies_to'] : 'both',
                !empty($data['min_price'])    ? (float)$data['min_price']    : null,
                !empty($data['max_discount']) ? (float)$data['max_discount'] : null,
                !empty($data['max_uses'])     ? (int)$data['max_uses']       : null,
                !empty($data['valid_from'])   ? $data['valid_from']  : null,
                !empty($data['valid_until'])  ? $data['valid_until'] : null,
                isset($data['is_active'])     ? (int)$data['is_active'] : 1,
                $adminId,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("DiscountCoupon::create error: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE discount_coupons SET
                code            = ?,
                display_name    = ?,
                discount_type   = ?,
                discount_value  = ?,
                applies_to      = ?,
                min_price       = ?,
                max_discount    = ?,
                max_uses        = ?,
                valid_from      = ?,
                valid_until     = ?,
                is_active       = ?,
                updated_at      = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            strtoupper(trim($data['code'])),
            $data['display_name'],
            in_array($data['discount_type'], ['percent','fixed']) ? $data['discount_type'] : 'percent',
            (float)$data['discount_value'],
            in_array($data['applies_to'], ['monthly','yearly','both']) ? $data['applies_to'] : 'both',
            !empty($data['min_price'])    ? (float)$data['min_price']    : null,
            !empty($data['max_discount']) ? (float)$data['max_discount'] : null,
            !empty($data['max_uses'])     ? (int)$data['max_uses']       : null,
            !empty($data['valid_from'])   ? $data['valid_from']  : null,
            !empty($data['valid_until'])  ? $data['valid_until'] : null,
            isset($data['is_active'])     ? (int)$data['is_active'] : 1,
            $id,
        ]);
    }

    public function toggle(int $id, bool $active): bool {
        $stmt = $this->db->prepare(
            "UPDATE discount_coupons SET is_active = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([(int)$active, $id]);
    }

    /**
     * Soft-delete: se já usado, apenas desativa; caso contrário remove.
     */
    public function delete(int $id): bool {
        $coupon = $this->findById($id);
        if (!$coupon) return false;

        if ($coupon['used_count'] > 0) {
            // Apenas desativar
            return $this->toggle($id, false);
        }

        $stmt = $this->db->prepare("DELETE FROM discount_coupons WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verifica se código já existe (para validação de unicidade no frontend).
     */
    public function codeExists(string $code, ?int $excludeId = null): bool {
        $sql  = "SELECT id FROM discount_coupons WHERE code = ?";
        $args = [strtoupper(trim($code))];
        if ($excludeId) {
            $sql  .= " AND id != ?";
            $args[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    /**
     * Uso detalhado de um cupom (para relatório).
     */
    public function getUsageDetails(int $couponId): array {
        $stmt = $this->db->prepare("
            SELECT cu.*, u.email, u.full_name, u.username
            FROM coupon_uses cu
            JOIN users u ON cu.user_id = u.id
            WHERE cu.coupon_id = ?
            ORDER BY cu.used_at DESC
        ");
        $stmt->execute([$couponId]);
        return $stmt->fetchAll();
    }
}

