<?php
// includes/EmailSenderHandler.php (o BHandler.php)

use PHPMailer\PHPMailer\Exception as PHPMailerException;
require_once 'send_email.php'; // Incluye la función de bajo nivel

function email_sender($email, $token, $name_link, $subject, $message): void {

    // 1. Construir el enlace

    $link_url = $name_link . "?token=" . urlencode($token);
    $message = str_replace("{link}", $link_url, $message);
    
    // 2. Enviar y Envolver la Excepción (lo que discutimos)
    // PROPAGACIÓN: Llamamos a send_mail y dejamos que la Exception se propague a register.php
    // Si send_mail lanza una excepción, el flujo se detiene aquí y salta al catch de register.php

    try {

        send_mail($email, $subject, $message); 

    } catch (PHPMailerException $e) {

        error_log("Error al enviar correo (PHPMailer): " . $e->getMessage());
        // Propaga una excepción limpia para que AHandler y register.php la entiendan
        throw new Exception("Error al enviar el correo de verificación. Fallo en el servidor.", 500); 

    }
}