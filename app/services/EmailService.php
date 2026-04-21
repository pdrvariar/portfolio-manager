<?php
// app/services/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {

    private static function configurePhpMailer(PHPMailer $mail) {
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['BREVO_USER'];
        $mail->Password   = $_ENV['BREVO_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    }

    // ── Recuperação de senha ──────────────────────────────────────

    public static function sendPasswordReset($email, $name, $token) {
        $mail = new PHPMailer(true);
        try {
            self::configurePhpMailer($mail);
            $mail->addAddress($email, $name);
            $url = $_ENV['APP_URL'] . "/index.php?url=reset-password&token=" . $token;
            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha - Portfolio Backtest';
            $mail->Body    = self::wrapTemplate(
                'Recuperação de Senha',
                "<p>Olá, <strong>{$name}</strong>,</p>
                 <p>Recebemos uma solicitação para redefinir a sua senha. Se não foi você, ignore este e-mail.</p>
                 <a href='{$url}' style='" . self::btnStyle() . "'>Redefinir Senha</a>
                 <p style='font-size:12px;color:#6c757d;margin-top:16px;'>Este link expira em 1 hora.</p>"
            );
            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (Reset): " . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Bem-vindo ao PRO ─────────────────────────────────────────

    public static function sendSubscriptionWelcome($email, $name, $planType, $expiresAt) {
        $mail = new PHPMailer(true);
        try {
            self::configurePhpMailer($mail);
            $mail->addAddress($email, $name);
            $planLabel  = $planType === 'yearly' ? 'Anual' : 'Mensal';
            $expiryFmt  = date('d/m/Y', strtotime($expiresAt));
            $manageUrl  = ($_ENV['APP_URL'] ?? '') . "/index.php?url=subscription/manage";
            $mail->isHTML(true);
            $mail->Subject = '🎉 Bem-vindo ao Plano PRO — Smart Returns!';
            $mail->Body    = self::wrapTemplate(
                'Sua assinatura PRO está ativa!',
                "<p>Olá, <strong>{$name}</strong>! 🚀</p>
                 <p>Sua assinatura do <strong>Plano PRO {$planLabel}</strong> foi ativada com sucesso.</p>
                 <ul style='line-height:2;'>
                   <li>✅ Histórico ilimitado de dados</li>
                   <li>✅ Ativos ilimitados por portfólio</li>
                   <li>✅ 1.000 simulações por mês</li>
                   <li>✅ Estratégias avançadas de rebalanceamento</li>
                   <li>✅ Cálculo automático de impostos</li>
                 </ul>
                 <p><strong>Válido até:</strong> {$expiryFmt}</p>
                 <a href='{$manageUrl}' style='" . self::btnStyle() . "'>Gerenciar Minha Assinatura</a>
                 <p style='font-size:12px;color:#6c757d;margin-top:16px;'>⚠️ Você tem 7 dias de garantia. Se não ficar satisfeito, solicite reembolso completo na página de assinatura.</p>"
            );
            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (SubWelcome): " . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Lembrete de renovação ────────────────────────────────────

    public static function sendRenewalReminder($email, $name, $daysLeft, $expiresAt, $planType) {
        $mail = new PHPMailer(true);
        try {
            self::configurePhpMailer($mail);
            $mail->addAddress($email, $name);
            $expiryFmt = date('d/m/Y', strtotime($expiresAt));
            $emojis    = $daysLeft <= 1 ? '🚨' : ($daysLeft <= 3 ? '⚠️' : '📅');
            $renewUrl  = ($_ENV['APP_URL'] ?? '') . "/index.php?url=upgrade";
            $mail->isHTML(true);
            $mail->Subject = "{$emojis} Sua assinatura PRO expira em {$daysLeft} dia(s)";
            $mail->Body    = self::wrapTemplate(
                "Assinatura expira em {$daysLeft} dia(s)!",
                "<p>Olá, <strong>{$name}</strong>,</p>
                 <p>Sua assinatura expira em <strong>{$daysLeft} dia(s)</strong> ({$expiryFmt}).</p>
                 <p>Renove agora para não perder o acesso:</p>
                 <a href='{$renewUrl}' style='" . self::btnStyle() . "'>Renovar Assinatura PRO</a>
                 <p style='font-size:12px;color:#6c757d;margin-top:16px;'>Após o vencimento sua conta volta ao plano Starter automaticamente.</p>"
            );
            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (RenewalReminder): " . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Confirmação de cancelamento ──────────────────────────────

    public static function sendCancellationConfirmation($email, $name, $cancelType, $expiresAt) {
        $mail = new PHPMailer(true);
        try {
            self::configurePhpMailer($mail);
            $mail->addAddress($email, $name);
            $expiryFmt = date('d/m/Y', strtotime($expiresAt));
            $detail    = $cancelType === 'immediate'
                ? "Seu acesso ao Plano PRO foi <strong>cancelado imediatamente</strong>."
                : "Seu acesso PRO permanece ativo até <strong>{$expiryFmt}</strong>, quando será cancelado.";
            $renewUrl  = ($_ENV['APP_URL'] ?? '') . "/index.php?url=upgrade";
            $mail->isHTML(true);
            $mail->Subject = 'Cancelamento de Assinatura — Smart Returns';
            $mail->Body    = self::wrapTemplate(
                'Assinatura Cancelada',
                "<p>Olá, <strong>{$name}</strong>,</p>
                 <p>{$detail}</p>
                 <p>Se mudar de ideia, pode reativar o PRO a qualquer momento:</p>
                 <a href='{$renewUrl}' style='" . self::btnStyle('#6c757d') . "'>Reativar Plano PRO</a>"
            );
            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (Cancellation): " . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Reembolso confirmado ─────────────────────────────────────

    public static function sendRefundConfirmation($email, $name, $amount) {
        $mail = new PHPMailer(true);
        try {
            self::configurePhpMailer($mail);
            $mail->addAddress($email, $name);
            $amountFmt = 'R$ ' . number_format($amount, 2, ',', '.');
            $mail->isHTML(true);
            $mail->Subject = '✅ Reembolso Confirmado — Smart Returns';
            $mail->Body    = self::wrapTemplate(
                'Reembolso Aprovado!',
                "<p>Olá, <strong>{$name}</strong>,</p>
                 <p>Seu reembolso de <strong>{$amountFmt}</strong> foi processado com sucesso.</p>
                 <p>O valor será creditado no cartão original em até <strong>5 dias úteis</strong>, conforme seu banco.</p>
                 <p style='font-size:12px;color:#6c757d;margin-top:16px;'>Obrigado por ter testado o Smart Returns. Ficamos à disposição!</p>"
            );
            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (Refund): " . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Assinatura expirada ──────────────────────────────────────

    public static function sendSubscriptionExpired($email, $name) {
        $mail = new PHPMailer(true);
        try {
            self::configurePhpMailer($mail);
            $mail->addAddress($email, $name);
            $renewUrl = ($_ENV['APP_URL'] ?? '') . "/index.php?url=upgrade";
            $mail->isHTML(true);
            $mail->Subject = 'Sua assinatura PRO expirou — Smart Returns';
            $mail->Body    = self::wrapTemplate(
                'Assinatura Expirada',
                "<p>Olá, <strong>{$name}</strong>,</p>
                 <p>Sua assinatura do <strong>Plano PRO</strong> expirou e sua conta voltou ao plano Starter.</p>
                 <p>Seus portfólios e histórico estão preservados. Para retomar o acesso completo:</p>
                 <a href='{$renewUrl}' style='" . self::btnStyle() . "'>Reativar Plano PRO</a>"
            );
            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (Expired): " . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Helpers privados ─────────────────────────────────────────

    private static function btnStyle($color = '#0d6efd'): string {
        return "display:inline-block;padding:12px 28px;background-color:{$color};color:#ffffff;"
             . "text-decoration:none;border-radius:8px;font-weight:bold;margin:16px 0;";
    }

    private static function wrapTemplate(string $heading, string $body): string {
        $appName = htmlspecialchars($_ENV['MAIL_FROM_NAME'] ?? 'Smart Returns');
        return "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f8f9fa;padding:24px;border-radius:12px;'>
          <div style='background:#ffffff;border-radius:10px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.06);'>
            <h2 style='color:#0d6efd;margin-top:0;'>{$heading}</h2>
            {$body}
          </div>
          <p style='text-align:center;font-size:11px;color:#adb5bd;margin-top:20px;'>
            {$appName} &middot; E-mail automático, não responda diretamente.
          </p>
        </div>";
    }
}