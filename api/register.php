<?php
// api/register.php - Manejo del registro de usuarios con verificación de correo electrónico

ob_start(); // Iniciar el búfer de salida

// ----------------------------------------------------
// 1. Configuración Inicial y CORS (Necesario para React)
// ----------------------------------------------------
require_once __DIR__ . '/../config/cors_setup.php';

// ----------------------------------------------------
// PASO CLAVE: INCLUSIÓN DE COMPOSER Y CONFIGURACIÓN
// ----------------------------------------------------
require_once '../vendor/autoload.php'; // Incluir Composer autoload si es necesario
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once "../includes/config.php"; //  Incluir la configuración (base de datos, JWT_SECRET_KEY, etc.)

// ----------------------------------------------------
// INCLUSIONES DE UTILIDADES
// ----------------------------------------------------
require_once '../includes/functions.php'; // Asegúrate de que este path sea correcto para tu proyecto
require_once '../includes/jwt_utils.php'; // Incluir las funciones de JWT
require_once '../includes/db_connection.php'; // Incluir el archivo de conexión a la base de datos
require_once '../includes/EmailVerificationHandler.php'; // Proceso para generar token y enviar correo
require_once '../includes/send_email.php'; // Incluir la función de envío de correo por PHPMAILER



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 4. Recibir los datos del  formulario y que no estén vacíos
    // Usamos isset para verificar si las variables están definidas y no son nulas
    $errores    = []; // Array para almacenar errores
    $estado     = 0; // Estado inactivo por defecto
    $correo_verificado = 0; // Correo no verificado por defecto
    $usuario = htmlspecialchars(trim($_POST['username']));
    $contraseña = trim($_POST['password']);
    $confirmaContraseña = trim($_POST['confirm-password']);
    $email = htmlspecialchars(trim($_POST['email']));
    $nombre = htmlspecialchars(trim($_POST['nombre']));

    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['confirm-password']) || empty($_POST['email']) || empty($_POST['nombre'])) {
        $errores[] = "Todos los campos son obligatorios.";
    }
    
    // Validar que las contraseñas coincidan
    if ($contraseña !== $confirmaContraseña) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    
    // Validar el formato del email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email es inválido.";
    }

    $errores_validacion = validatePassword($contraseña);

    if (!empty($errores_validacion)) {
        $errores = array_merge($errores, $errores_validacion);
    }

    // Validar usuario y correo electrónico no este repetido
    $stmt_check = $conn->prepare("SELECT * FROM usuarios WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $usuario, $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $errores[] = "El usuario o el email ya están en uso.";
    }
    $stmt_check->close();

    // Si hay errores, los mostramos y detenemos la ejecución
    if (!empty($errores)) {
        // Cierra recursos si es necesario antes de la redirección
        if (isset($conn) && $conn->ping()) { $conn->close(); }
        send_json_error(implode(" ", $errores), 400);
    }

    // Encriptar la contraseña antes de almacenarla
    if (empty($errores)) {
        $contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
    }

    // 5. Preparar y ejecutar la consulta SQL para insertar los datos
    $stmt = $conn->prepare("INSERT INTO usuarios (username, password, email, nombre, estado, correo_verificado) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $usuario, $contraseña, $email, $nombre, $estado, $correo_verificado); // Asignar 'no' por defecto a correo_verificado

    if ($stmt->execute()) {
        // 6. Verificar la dirección de correo electrónico

        // Obtener los datos del usuario recién insertado
        $insert_id = $stmt->insert_id; // Obtener el ID del usuario recién insertado
        
        // Preparar los datos para la verificación de correo
        $name_link = "controllers/verify_email.php"; // Nombre del enlace para la verificación de correo
        $name_table = "email_verifications"; // Nombre de la tabla para la verificación de correo
        $verification_subject = "Activar cuenta";
        $verification_message = "Hola,\n\nPara activar tu cuenta, haz clic en el siguiente enlace:\n\n{link}\n\nEl enlace expirará en 1 hora.";

        // Llamar a la función de verificación de correo
        $correo_enviado = email_verification($conn, $email, $insert_id, $name_link, $name_table, $verification_subject, $verification_message);

        $stmt->close(); // Cerramos la declaración antes de redirigir
        $conn->close(); // Cerramos la conexión a la base de datos

        if ($correo_enviado) {
            // generar JWT Y enviar respuesta exitosa
            $role = 'user'; // Rol por defecto para nuevos usuarios
            $jwt = create_user_token($insert_id, $usuario, $email, $nombre, $role);
            $mensaje_exito = "¡Registro exitoso! Por favor verifica tu correo electrónico para activar tu cuenta.";
            $datos_usuario = [
                "token" => $jwt, // Incluir el token JWT en la respuesta
                'id' => $insert_id,
                'username' => $usuario, // Incluir el nombre de usuario
                'email' => $email, // Incluir el correo electrónico
                'nombre' => $nombre, // Incluir el nombre completo
                'role_name' => $role // Incluir el nombre del rol por defecto
            ];
            send_json_success($mensaje_exito, $datos_usuario, 201); // Enviar respuesta exitosa con datos del usuario y token JWT
        } else {
            send_json_error("Registro exitoso, pero hubo un problema al enviar el correo de verificación. Por favor, inténtalo más tarde.", 500);
        }
    } else {
        error_log("Error al intentar registrar al usuario: " . $stmt->error);
        $stmt->close(); // Cerramos la declaración antes de redirigir
        $conn->close(); // Cerramos la conexión a la base de datos
        send_json_error("Error al registrar el usuario en la base de datos. Por favor, inténtalo más tarde.", 500);
    }
}else {
    // Método no permitido 
    if ($conn && $conn->ping()) {$conn->close();}
    send_json_error("Método no permitido. Usa POST para registrar un usuario.", 405);
}