<?php
// db_connection.php

// 1. Crear conexión usando PDO

try {
    //Usar PDO (PHP Data Objects)
    $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Devolver resultados como array asociativo
        PDO::ATTR_EMULATE_PREPARES   => false,                 // Desactivar emulacion para seguridad
    ];
    // El objeto de conexión se llama $pdo o $db en PDO. Usaremos $conn para mantener consistencia.
    $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);

// 2. Verificar la conexión
} catch (PDOException $e) {
    //Captura la excepcion lanzada por PDO
    $errores = "Error de conexion a la base de datos (PDO): ". $e->getMessage();
    error_log($errores);
    send_json_error("Lo sentimos, no pudimos conectar con la base de datos en este momento. Por favor, intentalo mas tarde.", 500);
}