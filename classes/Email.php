<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

class Email {

    public $email;
    public $nombre;
    public $token;
    
    public function __construct($email, $nombre, $token)
    {
        $this->email = $email;
        $this->nombre = $nombre;
        $this->token = $token;
    }

    public function enviarConfirmacion() {

         // create a new object
         $mail = new PHPMailer();
         $mail->isSMTP();
         $mail->Host = getenv('SMTP_HOST') ?: 'smtp.mailtrap.io';
         $mail->SMTPAuth = true;
         $mail->Port = (int) (getenv('SMTP_PORT') ?: 2525);
         $mail->Username = getenv('SMTP_USER') ?: '4ec54dfb980a42';
         $mail->Password = getenv('SMTP_PASS') ?: 'ae938c99960f22';
     
         $mail->setFrom(getenv('MAIL_FROM') ?: 'cuentas@appsalon.com', getenv('MAIL_FROM_NAME') ?: 'AppSalon.com');
         $mail->addAddress($this->email, $this->nombre);
         $mail->Subject = 'Confirma tu Cuenta';

         // Set HTML
         $mail->isHTML(TRUE);
         $mail->CharSet = 'UTF-8';
         $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:3000', '/');

         $contenido = '<html>';
         $contenido .= "<p><strong>Hola " . $this->nombre .  "</strong> Has Creado tu cuenta en App Salón, solo debes confirmarla presionando el siguiente enlace</p>";
         $contenido .= "<p>Presiona aquí: <a href='" . $appUrl . "/confirmar-cuenta?token=" . $this->token . "'>Confirmar Cuenta</a>";        
         $contenido .= "<p>Si tu no solicitaste este cambio, puedes ignorar el mensaje</p>";
         $contenido .= '</html>';
         $mail->Body = $contenido;

         //Enviar el mail
         $mail->send();

    }

    public function enviarInstrucciones() {

        // create a new object
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Port = (int) (getenv('SMTP_PORT') ?: 2525);
        $mail->Username = getenv('SMTP_USER') ?: '4ec54dfb980a42';
        $mail->Password = getenv('SMTP_PASS') ?: 'ae938c99960f22';
    
        $mail->setFrom(getenv('MAIL_FROM') ?: 'cuentas@appsalon.com', getenv('MAIL_FROM_NAME') ?: 'AppSalon.com');
        $mail->addAddress($this->email, $this->nombre);
        $mail->Subject = 'Reestablece tu password';

        // Set HTML
        $mail->isHTML(TRUE);
        $mail->CharSet = 'UTF-8';
        $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:3000', '/');

        $contenido = '<html>';
        $contenido .= "<p><strong>Hola " . $this->nombre .  "</strong> Has solicitado reestablecer tu password, sigue el siguiente enlace para hacerlo.</p>";
        $contenido .= "<p>Presiona aquí: <a href='" . $appUrl . "/recuperar?token=" . $this->token . "'>Reestablecer Password</a>";        
        $contenido .= "<p>Si tu no solicitaste este cambio, puedes ignorar el mensaje</p>";
        $contenido .= '</html>';
        $mail->Body = $contenido;

            //Enviar el mail
        $mail->send();
    }
}
