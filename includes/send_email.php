<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_mail($to_email, $subject, $message) {
    $mail = new PHPMailer(true); // Habilitar excepciones (true)

        // Configuración del servidor SMTP (usa aquí tus credenciales)
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;


        // Remitente
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        // Destinatarios y contenido

        $mail->addAddress($to_email);
        $mail->isHTML(true); // (true para HTML, false para texto plano)
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message); // Versión en texto plano

        $mail->send();

        return true;
}