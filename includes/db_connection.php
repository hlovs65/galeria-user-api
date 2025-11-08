<?php
// db_connection.php

// 1. Crear conexión
// Usamos MySQLi para una conexión orientada a objetos, es más moderna y segura que las funciones mysql_

try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 2. Verificar la conexión
} catch (mysqli_sql_exception $e) {
    //Captura la excepcion lanzada por new mysqli()
    $errores = "Error de conexion a la base de datos: ". $e->getMessage();
    error_log($errores);
    send_json_error("Lo sentimos, no pudimos conectar con la base de datos en este momento. Por favor, intentalo mas tarde.", 500);
} catch (Exception $e) {
    //Captura cualquier otra excepcion que no sea mysqli_sql_exception
    $errores = "Ha ocurrido un error inesperado: ". $e->getMessage();
    error_log($errores);
    send_json_error("Ha ocurrido un error inesperado. Por favor, intentalo mas tarde.", 500);
}

// Opcional: Establecer el conjunto de caracteres a UTF-8 para evitar problemas con acentos y caracteres especiales
$conn->set_charset("utf8");