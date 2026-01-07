<?php
// app/services/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    /**
     * Configura as definições base do PHPMailer extraídas do .env
     */
    private static function configurePhpMailer(PHPMailer $mail) {
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['BREVO_USER'];
        $mail->Password   = $_ENV['BREVO_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remetente dinâmico configurado no .env
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    }

    /**
     * Envia o e-mail de recuperação de senha
     */
    public static function sendPasswordReset($email, $name, $token) {
        $mail = new PHPMailer(true);

        try {
            self::configurePhpMailer($mail); // RESOLVE O ERRO: Método agora definido

            $mail->addAddress($email, $name);
            
            // Construção do link utilizando a APP_URL do .env
            $url = $_ENV['APP_URL'] . "/index.php?url=reset-password&token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha - Portfolio Backtest';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #0d6efd;'>Recuperação de Senha</h2>
                    <p>Olá, <strong>{$name}</strong>,</p>
                    <p>Recebemos uma solicitação para redefinir a sua senha. Se não foi você, ignore este e-mail.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <a href='{$url}' style='display:inline-block; padding:12px 24px; background-color:#0d6efd; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold;'>Redefinir Senha</a>
                    <p style='margin-top:20px; font-size:12px; color:#6c757d;'>Este link expirará em breve por motivos de segurança.</p>
                </div>";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro EmailService (Reset): " . $mail->ErrorInfo);
            return false;
        }
    }
}