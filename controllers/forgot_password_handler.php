<?php
// forgot_password_handler.php - El controlador para manejar la lógica de restablecimiento de contraseña

ob_start(); // Iniciar el almacenamiento en búfer de salida

// ----------------------------------------------------
// 1. Configuración Inicial y CORS (Necesario para React)
// ----------------------------------------------------
require_once __DIR__ . '/../config/cors_setup.php';

// ----------------------------------------------------
// PASO 2: Incluir las funciones de mensajes JWT. Conexion a la base de datos y otras utilidades.
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once '../includes/config.php'; // Incluir archivo de configuración

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Incluir funciones de mensajes flash
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos
require_once '../includes/EmailVerificationHandler.php'; // Incluir funciones de validación

try {
// PASO 3. Recibir los datos del  formulario y que no estén vacíos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errores    = []; // Array para almacenar errores
    $email = htmlspecialchars(trim($_POST['email'] ?? ""));

    //validar email
    if (empty($email)) {
        $errores[] = "El campo de correo electronico es obligatorio.";
    } 
    
    // Validar formato del email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email es invalido.";
    }
        
    // Si hay errores, los mostramos y detenemos la ejecución
    if (!empty($errores)) {
        send_json_error(implode(" ", $errores), 400);
    }
    

    //PASO 4: Validar correo electrónico existe
    $columna_to_select = 'id';
    $condition = ['email' => $email];

    $user_id_from_db = get_user_data_by_conditions($conn, $columna_to_select, $condition);

    if ($user_id_from_db === 0) {
            send_json_response(200, [
                "status" => "info",
                "message" => "El correo electrónico no está registrado."
            ]);
    }

        // ----------------------------------------------------
        // PASO 5: Enviar el correo de verificación
        // ----------------------------------------------------
        $user_id = $user_id_from_db['id'];
        $name_link = "/ResetPasswordPage"; // Nombre del enlace para restablecer la contraseña
        $name_table = "password_resets"; // Nombre de la tabla para los tokens de restablecimiento de contraseña
        $subject = "Restablecer tu contraseña";
        $message = "Hola,\n\nPara restablecer tu contraseña, haz clic en el siguiente enlace:\n\n{link}\n\nSi no solicitaste un cambio de contraseña, ignora este correo.\n\nEl enlace expirará en 1 hora.";

        email_verification($conn, $email, $user_id, $name_link, $name_table, $subject, $message);

        // ----------------------------------------------------
        // 7. Respuesta final
        // ----------------------------------------------------
    
        $mensaje_exito = "¡Se ha enviado un enlace a tu correo electrónico, para restablecer tu contraseña. Por favor verifica tu correo electrónico para continuar.";
        send_json_response(200, [
            "status" => "success",
            "message" => $mensaje_exito
        ]);
    } else {
        // Método no permitido
        $mensaje_complementario = "Parece que intentaste acceder directamente a esta dirección. Para ello, se requiere que uses el formulario. Por favor, dirigete a la sección de **inicio sesión** y busca el enlace al formulario de reenvío de token.";
        send_json_error($mensaje_complementario, 405);
    }
} catch (InvalidArgumentException $e) {
    error_log("Error de argumento inválido: " . $e->getMessage());
    send_json_error("Error de argumento inválido. Por favor, verifica los datos enviados.", 400);

} catch (PDOException $e) {
    $error_message = "Error de BD (PDO) en forgot_password_handler.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: \n" . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Error de base de datos. Por favor, inténtalo de nuevo más tarde.", 500);

} catch (Exception $e) {
    $error_message = "Error inseperado en forgot_password_handler.php: " . $e->getMessage() . " en " . $e->getFile() . " en la línea " . $e->getLine() . 
                     " | SQLSTATE: " . $e->getCode() .
                     " | Trace: \n" . $e->getTraceAsString();
    error_log($error_message);
    send_json_error("Ocurrio un error inesperado. Por favor, inténtalo más tarde.", 500);

} finally {
    if (isset($conn)) { $conn = null; } // Cerrar la conexión a la base de datos
}
?>
