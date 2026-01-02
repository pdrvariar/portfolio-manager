<?php
require_once __DIR__ . '/../helpers/Validation.php';

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->loadConfig();
    }
    
    private function loadConfig() {
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtpUser = $_ENV['SMTP_USER'] ?? '';
        $this->smtpPass = $_ENV['SMTP_PASS'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@portfolio.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'Portfolio Manager';
    }
    
    public function sendVerificationEmail($toEmail, $toName, $verificationLink) {
        $subject = 'Verifique seu email - Portfolio Manager';
        
        $body = $this->renderTemplate('verification', [
            'name' => $toName,
            'verification_link' => $verificationLink,
            'app_name' => 'Portfolio Manager'
        ]);
        
        return $this->sendEmail($toEmail, $toName, $subject, $body);
    }
    
    public function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
        $subject = 'Redefinição de Senha - Portfolio Manager';
        
        $body = $this->renderTemplate('password_reset', [
            'name' => $toName,
            'reset_link' => $resetLink,
            'app_name' => 'Portfolio Manager'
        ]);
        
        return $this->sendEmail($toEmail, $toName, $subject, $body);
    }
    
    public function sendSimulationCompleteEmail($toEmail, $toName, $portfolioName, $resultsLink) {
        $subject = 'Simulação Concluída - ' . $portfolioName;
        
        $body = $this->renderTemplate('simulation_complete', [
            'name' => $toName,
            'portfolio_name' => $portfolioName,
            'results_link' => $resultsLink,
            'app_name' => 'Portfolio Manager'
        ]);
        
        return $this->sendEmail($toEmail, $toName, $subject, $body);
    }
    
    private function renderTemplate($template, $data) {
        $templateFile = __DIR__ . "/../../templates/email/{$template}.html";
        
        if (!file_exists($templateFile)) {
            // Template padrão
            switch ($template) {
                case 'verification':
                    return "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Verificação de Email</title>
                        </head>
                        <body>
                            <h1>Olá, {$data['name']}!</h1>
                            <p>Obrigado por se cadastrar no {$data['app_name']}.</p>
                            <p>Por favor, clique no link abaixo para verificar seu email:</p>
                            <p><a href='{$data['verification_link']}'>Verificar Email</a></p>
                            <p>Se você não se cadastrou, ignore este email.</p>
                        </body>
                        </html>
                    ";
                    
                case 'password_reset':
                    return "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Redefinição de Senha</title>
                        </head>
                        <body>
                            <h1>Olá, {$data['name']}!</h1>
                            <p>Recebemos uma solicitação para redefinir sua senha no {$data['app_name']}.</p>
                            <p>Clique no link abaixo para redefinir sua senha:</p>
                            <p><a href='{$data['reset_link']}'>Redefinir Senha</a></p>
                            <p>Se você não solicitou esta redefinição, ignore este email.</p>
                            <p>Este link expira em 1 hora.</p>
                        </body>
                        </html>
                    ";
                    
                case 'simulation_complete':
                    return "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Simulação Concluída</title>
                        </head>
                        <body>
                            <h1>Olá, {$data['name']}!</h1>
                            <p>Sua simulação para o portfólio <strong>{$data['portfolio_name']}</strong> foi concluída.</p>
                            <p>Clique no link abaixo para ver os resultados:</p>
                            <p><a href='{$data['results_link']}'>Ver Resultados</a></p>
                            <p>Atenciosamente,<br>{$data['app_name']}</p>
                        </body>
                        </html>
                    ";
                    
                default:
                    return "Template não encontrado";
            }
        }
        
        $content = file_get_contents($templateFile);
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }
        
        return $content;
    }
    
    private function sendEmail($toEmail, $toName, $subject, $body) {
        // Validação básica
        if (!Validation::validateEmail($toEmail)) {
            error_log("Email inválido: $toEmail");
            return false;
        }
        
        // Usar PHPMailer se disponível
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($toEmail, $toName, $subject, $body);
        }
        
        // Fallback para mail() básico
        return $this->sendWithMail($toEmail, $subject, $body);
    }
    
    private function sendWithPHPMailer($toEmail, $toName, $subject, $body) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuração SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            
            // Remetente e destinatário
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            return $mail->send();
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendWithMail($toEmail, $subject, $body) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $headers = implode("\r\n", $headers);
        
        return mail($toEmail, $subject, $body, $headers);
    }
}