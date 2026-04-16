<?php

namespace Classes;

use PHPMailer\PHPMailer\Exception;
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

    public function enviarConfirmacion() : bool {
        $mail = null;
        try {
            $mail = $this->configurarMailer();
            $mail->Subject = 'Confirma tu Cuenta';

            $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:3000', '/');

            $contenido = '<html>';
            $contenido .= "<p><strong>Hola " . $this->nombre .  "</strong> Has Creado tu cuenta en App Salón, solo debes confirmarla presionando el siguiente enlace</p>";
            $contenido .= "<p>Presiona aquí: <a href='" . $appUrl . "/confirmar-cuenta?token=" . $this->token . "'>Confirmar Cuenta</a>";
            $contenido .= "<p>Si tu no solicitaste este cambio, puedes ignorar el mensaje</p>";
            $contenido .= '</html>';
            $mail->Body = $contenido;

            $enviado = $mail->send();
            if(!$enviado) {
                $this->ultimoError = $mail->ErrorInfo ?: 'Error SMTP desconocido';
                error_log('Error SMTP confirmación: ' . $this->ultimoError);
            }
            return $enviado;
        } catch (\Throwable $e) {
            $detalle = $e->getMessage();
            if($mail instanceof PHPMailer && $mail->ErrorInfo) {
                $detalle .= ' | ' . $mail->ErrorInfo;
            }
            $this->ultimoError = $detalle ?: 'Error SMTP desconocido';
            error_log('Error SMTP confirmación: ' . $this->ultimoError);
            return false;
        }
    }

    public function enviarInstrucciones() : bool {
        $mail = null;
        try {
            $mail = $this->configurarMailer();
            $mail->Subject = 'Reestablece tu password';

            $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:3000', '/');

            $contenido = '<html>';
            $contenido .= "<p><strong>Hola " . $this->nombre .  "</strong> Has solicitado reestablecer tu password, sigue el siguiente enlace para hacerlo.</p>";
            $contenido .= "<p>Presiona aquí: <a href='" . $appUrl . "/recuperar?token=" . $this->token . "'>Reestablecer Password</a>";
            $contenido .= "<p>Si tu no solicitaste este cambio, puedes ignorar el mensaje</p>";
            $contenido .= '</html>';
            $mail->Body = $contenido;

            $enviado = $mail->send();
            if(!$enviado) {
                $this->ultimoError = $mail->ErrorInfo ?: 'Error SMTP desconocido';
                error_log('Error SMTP recuperación: ' . $this->ultimoError);
            }
            return $enviado;
        } catch (\Throwable $e) {
            $detalle = $e->getMessage();
            if($mail instanceof PHPMailer && $mail->ErrorInfo) {
                $detalle .= ' | ' . $mail->ErrorInfo;
            }
            $this->ultimoError = $detalle ?: 'Error SMTP desconocido';
            error_log('Error SMTP recuperación: ' . $this->ultimoError);
            return false;
        }
    }
}
