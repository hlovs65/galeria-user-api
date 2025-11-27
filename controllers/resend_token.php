<?php

// controllers/resend_token.php - Controlador para manejar el reenvío de token de verificación de correo electrónico

ob_start(); // Iniciar el almacenamiento en búfer de salida

// ----------------------------------------------------
// 1. Configuración Inicial y CORS (Necesario para React)
// ----------------------------------------------------
require_once __DIR__ . '/../config/cors_setup.php';

// ----------------------------------------------------
// PASO 2: Incluir las funciones de mensajes JWT. Conexion a la base de datos y otras utilidades.
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once '../includes/config.php'; // Incluir archivo de configuración

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Asegúrate de que este path sea correcto para tu proyecto
require_once '../includes/jwt_utils.php'; // Incluir funciones de JWT
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos
require_once '../includes/EmailVerificationHandler.php'; // Incluir funciones de validación

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // ----------------------------------------------------
        // PASO 4: Procesar el formulario solo si el método es POST
        // ----------------------------------------------------
        $errores    = []; // Array para almacenar errores
        $email      = trim($_POST['email'] ?? ""); // correo electrónico para recuperar id de usuario
        // Validar el email para prevenir que se envíe un formulario sin token debido a un ataque CSRF
        if (!isset($email) || empty($email)) {
            $errores[] = "El campo de correo electrónico es obligatorio.";
        }

        // Validar formato del correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del correo electrónico es inválido.";
        }

        // Si hay errores, los mostramos y detenemos la ejecución
        if (!empty($errores)) {
            send_json_error(implode(" ", $errores), 400);
        }
    
        // ----------------------------------------------------
        // PASO 5: Obtener el ID y estado de su correo_verificado del usuario, 
        // con el correo que tiene relacionado, desde la base de datos usuarios.
        // ----------------------------------------------------
        $columna_to_select = 'id, correo_verificado';
        $condition = ['email' => $email];

        $user_data_from_db = get_user_data_by_conditions($conn, $columna_to_select, $condition);

        if ($user_data_from_db['id'] === 0) { // Usuario no encontrado
            sleep(1); // Retardo para prevenir ataques de enumeración de usuarios
            // Responder con un mensaje genérico de éxito para no revelar si el correo está registrado o no
            $mensaje_ambiguo = "Si el correo electrónico está registrado, recibirás un enlace de activación.";
            send_json_response(200, [
                "status" => "success",
                "message" => $mensaje_ambiguo
            ]);
        } else if ($user_data_from_db['correo_verificado'] === true) { // Correo verificado
            // Se usa info para que el frontend no muestre un mensaje de exito real.
            send_json_response(200, [
                "status" => "info",
                "message" => "Tu cuenta ya esta verificada. No es necesario reenviar el enlace.",
                "isverified" => true
            ]);
        }
     
        // ----------------------------------------------------
        // PASO 6: Enviar el correo de verificación
        // ----------------------------------------------------
        $user_id = $user_data_from_db['id'];
        $name_link = "controllers/verify_email.php"; // Nombre del enlace para la verificación de correo 
        $name_table = "email_verifications"; // Nombre de la tabla para la verificación de correo
        $verification_subject = "Activar cuenta - Nuevo Enlace";
        $verification_message = "Hola,\n\nPara activar tu cuenta, haz clic en el siguiente enlace:\n\n{link}\n\nEl enlace expirará en 1 hora.";
    
        email_verification($conn, $email, $user_id, $name_link, $name_table, $verification_subject, $verification_message);

        // ----------------------------------------------------
        // 7. Respuesta final
        // ----------------------------------------------------
    
        $mensaje_exito = "¡Se ha enviado un nuevo enlace de activación! Por favor verifica tu correo electrónico para activar tu cuenta.";
        send_json_response(200, [
            "status" => "success",
            "message" => $mensaje_exito
        ]);

    } else {
        // Si la solicitud no es POST, redirigir al formulario para solicitar un nuevo token
        $mensaje_complementario = "Parece que intentaste acceder directamente a esta dirección. Nuestro sistema de activación requiere que uses el formulario. Por favor, **inicia sesión** en la aplicación para ser dirigido automáticamente al formulario de reenvío de token.";
        send_json_error("Método no permitido. " . $mensaje_complementario, 405);
    }
} catch (InvalidArgumentException $e) {
    error_log("Error de argumento inválido: " . $e->getMessage());
    send_json_error("Error de argumento inválido. Por favor, verifica los datos enviados.", 400);

} catch (PDOException $e) {
    $error_message = "Error de BD (PDO) en resend_token.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: \n" . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Error de base de datos. Por favor, inténtalo de nuevo más tarde.", 500);

} catch (Exception $e) {
    $error_message = "Error inseperado en resend_token.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: \n" . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Ocurrio un error inesperado. Por favor, inténtalo más tarde.", 500);

} finally {
    if (isset($conn)) { $conn = null; } // Cerrar la conexión a la base de datos
}
?>

