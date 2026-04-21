<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║  Script de Cron - Gestão de Assinaturas                 ║
 * ║  Executar a cada hora:                                   ║
 * ║  0 * * * * php /app/scripts/subscription_cron.php       ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Responsabilidades:
 *  1. Expirar assinaturas ativas que passaram da data de expiração
 *  2. Enviar lembretes de renovação (7, 3, 1 dias antes)
 *  3. Expirar assinaturas canceladas que chegaram no fim do período
 */

// Bootstrap da aplicação (ajustar para o caminho correto conforme ambiente)
$appRoot = dirname(__DIR__, 2);

require_once $appRoot . '/vendor/autoload.php';
require_once $appRoot . '/app/core/Env.php';
require_once $appRoot . '/app/core/Database.php';
require_once $appRoot . '/app/core/Session.php';
require_once $appRoot . '/app/core/Auth.php';
require_once $appRoot . '/app/config/database.php';
require_once $appRoot . '/app/utils/helpers.php';
require_once $appRoot . '/app/models/User.php';
require_once $appRoot . '/app/models/Subscription.php';
require_once $appRoot . '/app/services/EmailService.php';

// Carregar .env se existir
if (class_exists('Env')) {
    Env::load($appRoot . '/.env');
}

// ─────────────────────────────────────────────────────────────
// HELPERS LOCAIS
// ─────────────────────────────────────────────────────────────

function cronLog(string $msg): void {
    $ts   = date('Y-m-d H:i:s');
    $line = "[{$ts}] [CRON] {$msg}" . PHP_EOL;
    echo $line;
    error_log($msg);

    $logPath = dirname(__DIR__) . '/logs/subscription_cron.log';
    @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}

// ─────────────────────────────────────────────────────────────
// 1. EXPIRAR ASSINATURAS COM PRAZO ESGOTADO
// ─────────────────────────────────────────────────────────────

$subModel  = new Subscription();
$userModel = new User();

$expired = $subModel->getExpiredActive();
cronLog("Assinaturas expiradas encontradas: " . count($expired));

foreach ($expired as $sub) {
    try {
        $subModel->markExpired($sub['id']);
        $userModel->updatePlan($sub['user_id'], 'starter', null, null, $sub['mp_payment_id'], 'expired');

        // Notificar o usuário
        try {
            EmailService::sendSubscriptionExpired($sub['email'], $sub['full_name']);
        } catch (Exception $e) {
            cronLog("Erro ao enviar e-mail de expiração para {$sub['email']}: " . $e->getMessage());
        }

        cronLog("Assinatura #{$sub['id']} do usuário {$sub['email']} marcada como EXPIRADA.");
    } catch (Exception $e) {
        cronLog("ERRO ao expirar assinatura #{$sub['id']}: " . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// 2. EXPIRAR ASSINATURAS CANCELADAS NO FIM DO PERÍODO
// ─────────────────────────────────────────────────────────────

try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->query(
        "SELECT s.*, u.email, u.full_name
         FROM subscriptions s
         JOIN users u ON s.user_id = u.id
         WHERE s.status = 'canceled'
           AND s.cancel_type = 'end_of_period'
           AND s.expires_at <= NOW()"
    );
    $canceledEndPeriod = $stmt->fetchAll();

    cronLog("Assinaturas canceladas no fim do período a encerrar: " . count($canceledEndPeriod));

    foreach ($canceledEndPeriod as $sub) {
        $subModel->markExpired($sub['id']);
        $userModel->updatePlan($sub['user_id'], 'starter', null, null, $sub['mp_payment_id'], 'expired');
        cronLog("Assinatura #{$sub['id']} ({$sub['email']}) encerrada após cancelamento end_of_period.");
    }
} catch (Exception $e) {
    cronLog("ERRO ao processar cancelamentos end_of_period: " . $e->getMessage());
}

// ─────────────────────────────────────────────────────────────
// 3. LEMBRETES DE RENOVAÇÃO (7, 3, 1 dias)
// ─────────────────────────────────────────────────────────────

foreach ([7, 3, 1] as $days) {
    try {
        $pending = $subModel->getExpiringSoonPending($days);
        cronLog("Lembretes de {$days} dia(s) a enviar: " . count($pending));

        foreach ($pending as $sub) {
            try {
                EmailService::sendRenewalReminder(
                    $sub['email'],
                    $sub['full_name'],
                    $days,
                    $sub['expires_at'],
                    $sub['plan_type']
                );
                $subModel->markReminderSent($sub['id'], $days);
                cronLog("Lembrete {$days}d enviado para {$sub['email']}");
            } catch (Exception $e) {
                cronLog("ERRO ao enviar lembrete {$days}d para {$sub['email']}: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        cronLog("ERRO ao buscar lembretes {$days}d: " . $e->getMessage());
    }
}

cronLog("Cron concluído.");

