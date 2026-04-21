<?php

/**
 * Subscription Model — Gestão completa do ciclo de vida da assinatura.
 * Cobre: criação, cancelamento, reembolso, expiração, upgrade e histórico.
 */
class Subscription {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─────────────────────────────────────────────────────────────
    // CRIAÇÃO
    // ─────────────────────────────────────────────────────────────

    /**
     * Grava um novo registro de assinatura.
     * @return int|false  ID inserido ou false em caso de erro.
     */
    public function create(array $data) {
        try {
            $sql = "INSERT INTO subscriptions
                        (user_id, mp_payment_id, mp_idempotency_key, plan_type,
                         status, amount_paid, starts_at, expires_at, refund_eligible_until, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $ok   = $stmt->execute([
                $data['user_id'],
                $data['mp_payment_id']         ?? null,
                $data['mp_idempotency_key']    ?? null,
                $data['plan_type'],
                $data['status']                ?? 'active',
                $data['amount_paid'],
                $data['starts_at'],
                $data['expires_at'],
                $data['refund_eligible_until'] ?? null,
                $data['notes']                 ?? null,
            ]);

            return $ok ? (int) $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Subscription::create error: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // LEITURA
    // ─────────────────────────────────────────────────────────────

    public function findById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Todas as assinaturas de um usuário, mais recentes primeiro. */
    public function findByUserId(int $userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Assinatura ativa mais recente. */
    public function findActiveByUserId(int $userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions
             WHERE user_id = ? AND status = 'active'
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /** Último registro de assinatura (qualquer status). */
    public function findLatestByUserId(int $userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions
             WHERE user_id = ?
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function findByMpPaymentId(string $mpPaymentId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions WHERE mp_payment_id = ?"
        );
        $stmt->execute([$mpPaymentId]);
        return $stmt->fetch() ?: null;
    }

    public function findByIdempotencyKey(string $key) {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscriptions WHERE mp_idempotency_key = ?"
        );
        $stmt->execute([$key]);
        return $stmt->fetch() ?: null;
    }

    // ─────────────────────────────────────────────────────────────
    // ATUALIZAÇÕES DE CICLO DE VIDA
    // ─────────────────────────────────────────────────────────────

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions SET status = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$status, $id]);
    }

    /**
     * Cancela a assinatura.
     *
     * @param int    $id         ID da assinatura
     * @param string $cancelType 'immediate' ou 'end_of_period'
     */
    public function cancel(int $id, string $cancelType = 'immediate'): bool {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions
             SET status = 'canceled', canceled_at = NOW(), cancel_type = ?, updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$cancelType, $id]);
    }

    /**
     * Marca como reembolsada após o processamento via MP.
     */
    public function markRefunded(int $id, string $refundMpId, float $amount): bool {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions
             SET status = 'refunded', refunded_at = NOW(),
                 refund_mp_id = ?, refund_amount = ?, updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$refundMpId, $amount, $id]);
    }

    public function markExpired(int $id): bool {
        return $this->updateStatus($id, 'expired');
    }

    /** Atualiza a chave de idempotência depois de reservada. */
    public function setIdempotencyKey(int $id, string $key): bool {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions SET mp_idempotency_key = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$key, $id]);
    }

    /** Atualiza o payment_id e finaliza o status ativo. */
    public function confirmPayment(int $id, string $mpPaymentId): bool {
        $stmt = $this->db->prepare(
            "UPDATE subscriptions
             SET mp_payment_id = ?, status = 'active', updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$mpPaymentId, $id]);
    }

    // ─────────────────────────────────────────────────────────────
    // LÓGICA DE NEGÓCIO
    // ─────────────────────────────────────────────────────────────

    /**
     * Verifica se a assinatura ainda está dentro janela de 7 dias para reembolso.
     */
    public function isRefundEligible(array $subscription): bool {
        if ($subscription['status'] !== 'active') return false;
        if (empty($subscription['refund_eligible_until'])) return false;
        return strtotime($subscription['refund_eligible_until']) > time();
    }

    /**
     * Calcula o crédito proporcional para upgrade mensal → anual.
     * Baseado nos dias restantes do plano atual.
     */
    public function calculateProratedCredit(array $subscription): float {
        $starts    = strtotime($subscription['starts_at']);
        $expires   = strtotime($subscription['expires_at']);
        $now       = time();

        $totalDays     = max(1, ($expires - $starts) / 86400);
        $remainingDays = max(0, ($expires - $now)  / 86400);

        $dailyRate = (float)$subscription['amount_paid'] / $totalDays;
        $credit    = round($remainingDays * $dailyRate, 2);

        // Crédito não pode ser maior que o que foi pago nem negativo
        return max(0.0, min($credit, (float)$subscription['amount_paid']));
    }

    /**
     * Retorna dias restantes de uma assinatura ativa.
     */
    public function getDaysRemaining(array $subscription): int {
        $remaining = (strtotime($subscription['expires_at']) - time()) / 86400;
        return max(0, (int) ceil($remaining));
    }

    /**
     * Percentual do período consumido (para barra de progresso).
     */
    public function getUsagePercent(array $subscription): int {
        $total   = max(1, strtotime($subscription['expires_at']) - strtotime($subscription['starts_at']));
        $used    = time() - strtotime($subscription['starts_at']);
        $percent = (int) round(($used / $total) * 100);
        return min(100, max(0, $percent));
    }

    // ─────────────────────────────────────────────────────────────
    // CRON / JOBS
    // ─────────────────────────────────────────────────────────────

    /**
     * Assinaturas ativas que já expiraram (para o cron de expiração).
     */
    public function getExpiredActive(): array {
        $stmt = $this->db->query(
            "SELECT s.*, u.email, u.full_name
             FROM subscriptions s
             JOIN users u ON s.user_id = u.id
             WHERE s.status = 'active' AND s.expires_at <= NOW()"
        );
        return $stmt->fetchAll();
    }

    /**
     * Assinaturas ativas que vencem em exatamente até $days dias
     * e cujo lembrete ainda não foi enviado.
     */
    public function getExpiringSoonPending(int $days): array {
        $col  = "reminder_{$days}_sent";
        $stmt = $this->db->prepare(
            "SELECT s.*, u.email, u.full_name
             FROM subscriptions s
             JOIN users u ON s.user_id = u.id
             WHERE s.status = 'active'
               AND s.expires_at > NOW()
               AND s.expires_at <= DATE_ADD(NOW(), INTERVAL ? DAY)
               AND s.$col = 0"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function markReminderSent(int $id, int $days): bool {
        $col  = "reminder_{$days}_sent";
        $stmt = $this->db->prepare(
            "UPDATE subscriptions SET $col = 1, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    // ─────────────────────────────────────────────────────────────
    // ADMIN / RELATÓRIOS
    // ─────────────────────────────────────────────────────────────

    /** Todas as assinaturas com dados do usuário (painel admin). */
    public function getAllWithUsers(): array {
        $stmt = $this->db->query(
            "SELECT s.*, u.email, u.full_name, u.username
             FROM subscriptions s
             JOIN users u ON s.user_id = u.id
             ORDER BY s.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    /** Estatísticas financeiras e operacionais de assinaturas. */
    public function getRevenueStats(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(CASE WHEN status = 'active'   THEN 1 END)                             AS active_count,
                COUNT(CASE WHEN status = 'canceled' THEN 1 END)                             AS canceled_count,
                COUNT(CASE WHEN status = 'refunded' THEN 1 END)                             AS refunded_count,
                COUNT(CASE WHEN status = 'expired'  THEN 1 END)                             AS expired_count,
                COALESCE(SUM(CASE WHEN status IN ('active','expired','canceled') THEN amount_paid ELSE 0 END), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN status = 'active' AND plan_type = 'monthly' THEN amount_paid ELSE 0 END), 0) AS mrr_monthly,
                COALESCE(SUM(CASE WHEN status = 'active' AND plan_type = 'yearly'  THEN amount_paid/12 ELSE 0 END), 0) AS mrr_yearly,
                COALESCE(SUM(CASE WHEN status = 'refunded' THEN COALESCE(refund_amount, 0) ELSE 0 END), 0)       AS total_refunded,
                COUNT(CASE WHEN status = 'canceled' AND DATE(canceled_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) AS cancels_30d,
                COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status != 'failed' THEN 1 END)  AS new_30d
             FROM subscriptions"
        );
        $row = $stmt->fetch();

        // MRR = mensal direto + equivalente mensal do anual
        $row['mrr'] = round(($row['mrr_monthly'] ?? 0) + ($row['mrr_yearly'] ?? 0), 2);

        return $row ?: [];
    }

    /** Admin: cancelar manualmente uma assinatura e revogar acesso. */
    public function adminForceCancel(int $id): bool {
        return $this->cancel($id, 'immediate');
    }
}

