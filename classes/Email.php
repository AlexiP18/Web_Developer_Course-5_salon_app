<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

class Email {

    public $email;
    public $nombre;
    public $token;
    private $ultimoError = '';
    
    public function __construct($email, $nombre, $token)
    {
        $this->email = $email;
        $this->nombre = $nombre;
        $this->token = $token;
    }

    public function getUltimoError() : string {
        return $this->ultimoError;
    }

    private function usarSendGrid() : bool {
        return (bool) getenv('SENDGRID_API_KEY');
    }

    private function getAppUrl() : string {
        $appUrl = trim((string) (getenv('APP_URL') ?: ''));
        if($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        $railwayDomain = trim((string) (getenv('RAILWAY_PUBLIC_DOMAIN') ?: ''));
        if($railwayDomain !== '') {
            return 'https://' . rtrim($railwayDomain, '/');
        }

        return 'http://localhost:3000';
    }

    private function crearContenidoConfirmacion() : string {
        $appUrl = $this->getAppUrl();
        $contenido = '<html>';
        $contenido .= "<p><strong>Hola " . $this->nombre .  "</strong> Has Creado tu cuenta en App Salón, solo debes confirmarla presionando el siguiente enlace</p>";
        $contenido .= "<p>Presiona aquí: <a href='" . $appUrl . "/confirmar-cuenta?token=" . $this->token . "'>Confirmar Cuenta</a>";
        $contenido .= "<p>Si tu no solicitaste este cambio, puedes ignorar el mensaje</p>";
        $contenido .= '</html>';
        return $contenido;
    }

    private function crearContenidoRecuperacion() : string {
        $appUrl = $this->getAppUrl();
        $contenido = '<html>';
        $contenido .= "<p><strong>Hola " . $this->nombre .  "</strong> Has solicitado reestablecer tu password, sigue el siguiente enlace para hacerlo.</p>";
        $contenido .= "<p>Presiona aquí: <a href='" . $appUrl . "/recuperar?token=" . $this->token . "'>Reestablecer Password</a>";
        $contenido .= "<p>Si tu no solicitaste este cambio, puedes ignorar el mensaje</p>";
        $contenido .= '</html>';
        return $contenido;
    }

    private function configurarMailer() : PHPMailer {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Port = (int) (getenv('SMTP_PORT') ?: 2525);
        $mail->Username = getenv('SMTP_USER') ?: '4ec54dfb980a42';
        $mail->Password = getenv('SMTP_PASS') ?: 'ae938c99960f22';

        // Configura cifrado según el puerto o variable explícita
        $smtpSecure = strtolower((string) (getenv('SMTP_SECURE') ?: ''));
        if($smtpSecure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif($smtpSecure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif($mail->Port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif($mail->Port === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->SMTPAutoTLS = true;
        $mail->Timeout = 15;
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $smtpDebug = (int) (getenv('SMTP_DEBUG') ?: 0);
        if($smtpDebug > 0) {
            $mail->SMTPDebug = $smtpDebug;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP[$level] $str");
            };
        }

        $fromEmail = getenv('MAIL_FROM') ?: $mail->Username;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'AppSalon.com';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($this->email, $this->nombre);

        return $mail;
    }

    private function enviarConSmtp(string $subject, string $html, string $contextoError) : bool {
        $mail = null;
        try {
            $mail = $this->configurarMailer();
            $mail->Subject = $subject;
            $mail->Body = $html;

            $enviado = $mail->send();
            if(!$enviado) {
                $this->ultimoError = $mail->ErrorInfo ?: 'Error SMTP desconocido';
                error_log($contextoError . ': ' . $this->ultimoError);
            }
            return $enviado;
        } catch (\Throwable $e) {
            $detalle = $e->getMessage();
            if($mail instanceof PHPMailer && $mail->ErrorInfo) {
                $detalle .= ' | ' . $mail->ErrorInfo;
            }
            $this->ultimoError = $detalle ?: 'Error SMTP desconocido';
            error_log($contextoError . ': ' . $this->ultimoError);
            return false;
        }
    }

    private function enviarConSendGrid(string $subject, string $html, string $contextoError) : bool {
        $apiKey = getenv('SENDGRID_API_KEY') ?: '';
        $fromEmail = getenv('MAIL_FROM') ?: '';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'AppSalon.com';

        if(!$apiKey) {
            $this->ultimoError = 'SENDGRID_API_KEY no está configurado';
            error_log($contextoError . ': ' . $this->ultimoError);
            return false;
        }

        if(!$fromEmail || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $this->ultimoError = 'MAIL_FROM no es un email válido';
            error_log($contextoError . ': ' . $this->ultimoError);
            return false;
        }

        $payload = [
            'personalizations' => [[
                'to' => [[
                    'email' => $this->email,
                    'name' => $this->nombre
                ]]
            ]],
            'from' => [
                'email' => $fromEmail,
                'name' => $fromName
            ],
            'subject' => $subject,
            'content' => [[
                'type' => 'text/html',
                'value' => $html
            ]]
        ];

        $jsonPayload = json_encode($payload);
        if($jsonPayload === false) {
            $this->ultimoError = 'No se pudo codificar el payload JSON';
            error_log($contextoError . ': ' . $this->ultimoError);
            return false;
        }

        $endpoint = 'https://api.sendgrid.com/v3/mail/send';
        $timeout = (int) (getenv('SENDGRID_TIMEOUT') ?: 15);

        // Preferir cURL si está disponible; fallback a stream wrapper
        if(function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if($httpCode === 200 || $httpCode === 202) {
                return true;
            }

            $detalle = $curlError ?: trim((string) $response);
            $this->ultimoError = "SendGrid HTTP {$httpCode}: " . ($detalle ?: 'Sin detalle');
            error_log($contextoError . ': ' . $this->ultimoError);
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' =>
                    "Authorization: Bearer {$apiKey}\r\n" .
                    "Content-Type: application/json\r\n",
                'content' => $jsonPayload,
                'timeout' => $timeout,
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($endpoint, false, $context);
        $headers = $http_response_header ?? [];
        $statusLine = $headers[0] ?? '';
        $httpCode = 0;
        if(preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            $httpCode = (int) $matches[1];
        }

        if($httpCode === 200 || $httpCode === 202) {
            return true;
        }

        $detalle = trim((string) $response);
        if(!$detalle) {
            $errorPhp = error_get_last();
            $detalle = $errorPhp['message'] ?? 'Sin detalle';
        }
        $this->ultimoError = "SendGrid HTTP {$httpCode}: {$detalle}";
        error_log($contextoError . ': ' . $this->ultimoError);
        return false;
    }

    public function enviarConfirmacion() : bool {
        $this->ultimoError = '';
        $subject = 'Confirma tu Cuenta';
        $contenido = $this->crearContenidoConfirmacion();

        if($this->usarSendGrid()) {
            return $this->enviarConSendGrid($subject, $contenido, 'Error SendGrid confirmación');
        }

        return $this->enviarConSmtp($subject, $contenido, 'Error SMTP confirmación');
    }

    public function enviarInstrucciones() : bool {
        $this->ultimoError = '';
        $subject = 'Reestablece tu password';
        $contenido = $this->crearContenidoRecuperacion();

        if($this->usarSendGrid()) {
            return $this->enviarConSendGrid($subject, $contenido, 'Error SendGrid recuperación');
        }

        return $this->enviarConSmtp($subject, $contenido, 'Error SMTP recuperación');
    }
}
