<?php
// functions.php

// ----------------------------------------------------
// Función de utilidad para enviar respuestas JSON y establecer el código de estado HTTP
// ----------------------------------------------------
function send_json_response($http_code, $data) {
    // Establece el código de estado HTTP (ej: 200, 400, 500)
    http_response_code($http_code);
    
    // Indica que la respuesta es JSON
    header('Content-Type: application/json');
    
    // Codifica y envía los datos
    echo json_encode($data);
    
    exit; // Termina la ejecución del script
}

// ----------------------------------------------------
// Lógica para enviar errores en formato JSON
// ----------------------------------------------------
function send_json_error(string $message, int $http_code = 401) {
    // 1. Limpiar cualquier salida que se haya generado ANTES de esta llamada (el famoso <br>)
    if (ob_get_level()) {
        ob_clean(); // Limpia (descarta) el contenido actual del buffer
    }

    // Establece el código de estado HTTP
    http_response_code($http_code); 
    // Imprime el mensaje de error en JSON
    echo json_encode(['status' => 'error', 'message' => $message]);

    exit;
}

/**
 * Detiene la ejecución y envía una respuesta de éxito en formato JSON.
 *
 * @param string $message Mensaje de éxito a devolver.
 * @param array $data Datos adicionales a incluir (ej. 'token' y 'username').
 * @param int $http_code Código de estado HTTP (por defecto 200 OK).
 */
function send_json_success(string $message, array $data = [], int $http_code = 200) {
    // 1. Limpiar cualquier salida que se haya generado ANTES de esta llamada (el famoso <br>)
    if (ob_get_level()) {
        ob_clean(); // Limpia (descarta) el contenido actual del buffer
    }

    // 2. Establece el código de estado HTTP
    http_response_code($http_code); 
    
    // 3. Construye el array de respuesta base
    $response = [
        'status' => 'success',
        'message' => $message,
    ];
    
    // 4. Fusiona los datos adicionales (como el token)
    $response = array_merge($response, $data);
    
    // 5. Imprime la respuesta JSON
    echo json_encode($response);

    // 6. Detiene el script
    exit;
}

// Puedes añadir esta función a tu archivo 'functions.php' o a otro archivo de utilidades de base de datos.

/**
 * Actualiza el estatus de un usuario en la base de datos a 0 (desconectado).
 * Actualiza el campo especificado de un usuario en la base de datos: correo_verificado o estado.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param int $userId El ID del usuario cuyo estatus se va a modificar.
 * @param string $columnName El campo que se va a actualizar (correo_verificado o estado).
 * @param int $newValue El nuevo valor del campo (0 o 1).
 * @return bool True si la actualización fue exitosa, false en caso contrario.
 */
function update_user_field($conn, $userId, $columnName, $newValue) {
    // Asegurarse de que la conexión sea válida
    if (!$conn || !$userId || !$columnName) {
        error_log("update_user_field: Conexión inválida o ID o nombre de columna no proporcionado.");
        return false;
    }
    
    $allowed_columns = ['correo_verificado', 'estado'];
    if (!in_array($columnName, $allowed_columns)) {
        error_log("update_user_field: Columna no permitida.");
        return false;
    }

    // Preparar la consulta
    $sql_query = "UPDATE usuarios SET `" . $columnName . "` = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_query);
    if (!$stmt) {
        error_log("Error en la preparación de la consulta update_user_field: " . $conn->error);
        return false;
    }

    // Vincular el parámetro
    $stmt->bind_param("ii", $newValue, $userId);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Error al ejecutar la consulta update_user_field: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Obtiene el estatus actual de un usuario desde la base de datos.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param string $columnToSelect El campo que se desea seleccionar (Todas las columnas de tabla usuarios).
 * @param string $columnToSearch El campo que se va a buscar (id, username, password, email, nombre, fecha_registro, estado o correo_verificado).
 * @param mixed $searchValue El valor del campo que se va a buscar (ejemplo: id, username, password, email, nombre, fecha_registro, estado o correo_verificado).
 * @return array|null Un array con los datos del usuario si se encuentra, o null si no existe o hay un error.
 */
function get_user_data_by_field($conn, $columnToSelect, $columnToSearch, $searchValue) {
    // 1.- Validar parametro de entrada
    if (!$conn || empty($columnToSelect) || empty($columnToSearch) || $searchValue === "" || $searchValue === null) {
        error_log("get_user_data_by_field: Conexión inválida o parámetros invalidos.");
        return null;
    }

    // 2.- Validar que los campos de búsqueda y selección sean válidos
    $allowed_columns = ['id', 'username', 'password', 'email', 'nombre', 'fecha_registro', 'estado', 'correo_verificado'];

    // 2.1 Validar que la columna de busqueda sea válida
    if (!in_array($columnToSearch, $allowed_columns)) {
        error_log("get_user_data_by_field: Columnas no permitidas para busqueda.");
        return null;
    }

    // 2.2 Validar que la columna de selección sea válida
    if ($columnToSelect !== '*') {
        $select_columns = explode(',', $columnToSelect); // Dividir la cadena de nombres de columnas en un array

        // Iterar sobre las columnas seleccionadas que existan en la lista permitida
        foreach ($select_columns as $col) {
            if (!in_array(trim($col), $allowed_columns)) {
                error_log("get_user_data_by_field: Columnas no permitidas para selección.");
                return null;
            }
        }
    }

    // 3. Preparar la consulta SQL
    $sql_query = "SELECT " . $columnToSelect . " FROM usuarios WHERE " . $columnToSearch . " = ?";
    // Usar una consulta preparada para evitar inyecciones SQL
    $stmt = $conn->prepare($sql_query);
    if (!$stmt) {
        error_log("Error en la preparación de la consulta get_user_data_by_field: " . $conn->error);
        return null;
    }

    // 4. Determinar el tipo de parámetro
    $param_type = "s";  // Por defecto string
    if (is_int($searchValue)) {
        $param_type = "i";
    } elseif (is_float($searchValue)) {
        $param_type = "d";
    }

    // 5. Vincular el parámetro
    $stmt->bind_param($param_type, $searchValue);

    // 6. Ejecutar la consulta
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta get_user_data_by_field: " . $stmt->error);
        $stmt->close();
        return null;
    }       

    $result = $stmt->get_result();

    // Verificar si se encontró el usuario
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $stmt->close();
        return $user_data;
    } else {
        $stmt->close();
        return null;
    }
}

/**
 * Obtiene la fecha de expiración y token de un usuario desde la base de datos.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param string $token del usuario cuyo estatus se desea consultar.
 * @param string $nombre_tabla El nombre de la tabla donde se almacenan los tokens.
 * @return array|null Un array con 'user_id' si 'token' se encuentra, o null si el token no existe o hay un error.
 */
function get_user_current_id($conn, $token, $nombre_tabla) {
    // Asegurarse de que la conexión sea válida
    $allowed_tables = ['email_verifications', 'password_resets'];
    if (!$conn || !$token  || empty($nombre_tabla) || !in_array($nombre_tabla, $allowed_tables)) {
        return null;
    }

    // Preparar la consulta
    $sql_query = "SELECT `id`, `user_id`, `expires_at` FROM `" . $nombre_tabla . "` WHERE `token` = ? AND `expires_at` > NOW()";
    // Usar una consulta preparada para evitar inyecciones SQL
    $stmt = $conn->prepare($sql_query);
    if (!$stmt) {
        error_log("Error en la preparación de la consulta: " . $conn->error);
        return null;
    }

    // Vincular el parámetro
    $stmt->bind_param("s", $token);

    // Ejecutar la consulta
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontró el token
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['user_id'];
    } else {
        $stmt->close();
        return null;
    }
}

/**
 * Elimina un registro de una tabla basándose en un token o id.
 *
 * @param mysqli $conn La conexión a la base de datos.
 * @param string $nombre_tabla El nombre de la tabla donde se encuentra el registro.
 * @param string $columnToSearch El nombre de la columna por la que se busca. (Ejemplo: token, id)
 * @param mixed $searchValue El valor a buscar en la tabla. (Ejemplo: "string", int)
 * @return bool True si la eliminación fue exitosa, false en caso de error o si el token no se encontró.
 */
function delete_record_by_field($conn, $nombre_tabla, $columnToSearch, $searchValue) {
    // Asegurarse de que la conexión sea válida
    $allowed_tables = ['usuarios', 'email_verifications', 'password_resets'];
    if (!$conn || !$searchValue || empty($nombre_tabla) || empty($columnToSearch) || !in_array($nombre_tabla, $allowed_tables)) {
        error_log("delete_record_by_field: Conexión inválida o parámetros invalidos.");
        return false;
    }

    // Preparar la consulta
    $sql_query = "DELETE FROM `" . $nombre_tabla . "` WHERE `" . $columnToSearch . "` = ?";
    // Usar una consulta preparada para evitar inyecciones SQL
    $stmt = $conn->prepare($sql_query);
    if (!$stmt) {
        error_log("delete_record_by_field - Error en la preparación de la consulta: " . $conn->error);
        return false;
    }

    // Vincular el parámetro
    $param_type = "s"; // Por defecto string
    if (is_int($searchValue)) {
        $param_type = "i";
    }
    
    $stmt->bind_param($param_type, $searchValue);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("delete_record_by_field - Error al ejecutar la consulta: " . $stmt->error);
        $stmt->close();
        return false;
    }
}



/**
 * Función para validar la fortaleza de una contraseña.
 *
 * @param string $contraseña La contraseña a validar.
 * @param array $errores Array para almacenar los mensajes de error.
 *              El array estará vacío si no hay errores.
 */
function validatePassword($contraseña) {

    $errores = [];

    // Validar la longitud de la contraseña
    if (strlen($contraseña) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres.";
    }

    // Validar que la contraseña contenga al menos un número
    if (!preg_match('/[0-9]/', $contraseña)) {
        $errores[] = "La contraseña debe contener al menos un número.";
    }

    // Validar que la contraseña contenga al menos una letra mayúscula
    if (!preg_match('/[A-Z]/', $contraseña)) {
        $errores[] = "La contraseña debe contener al menos una letra mayúscula.";
    }

    // Validar que la contraseña contenga al menos un carácter especial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $contraseña)) {
        $errores[] = "La contraseña debe contener al menos un carácter especial.";
    }

    return $errores;
}