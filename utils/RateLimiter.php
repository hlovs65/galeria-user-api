<?php
// utils/RateLimiter.php

require_once __DIR__ . '/../config/ratelimit.php';

// Bloque para crear la carpeta /tmp/ratelimit_logs/ si no existe
if (!is_dir(RATE_LIMIT_DIR)) {
    // 0777 da permisos de escritura, 'true' permite la creación recursiva
    @mkdir(RATE_LIMIT_DIR, 0777, true); 
}

/**
 * Verifica si la dirección IP actual ha excedido el límite de solicitudes.
 *
 * @return bool True si la IP está bloqueada, False en caso contrario.
 */
function is_rate_limited() {
    $ip = get_client_ip();
    // Creamos un hash de la IP para usarlo como nombre de archivo
    $ip_hash = md5($ip); 
    $file_path = RATE_LIMIT_DIR . $ip_hash . '.json';
    $current_time = time();

    // 1. Cargar el registro de intentos
    $attempts = [];
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $attempts = json_decode($file_content, true) ?: [];
    }

    // 2. Limpiar intentos expirados (fuera de la ventana de tiempo)
    $recent_attempts = array_filter($attempts, function($timestamp) use ($current_time) {
        // Solo conservamos los intentos dentro de la ventana de tiempo (60 segundos)
        return $timestamp > $current_time - TIME_WINDOW;
    });

   // Reindexamos el array para asegurar claves consecutivas (0, 1, 2...)    
   // Esto fuerza a json_encode() a usar el formato de lista [] en lugar del objeto {}
    $recent_attempts = array_values($recent_attempts);

    // 3. Verificar si existe un bloqueo activo (El último intento fallido fue un bloqueo)
    if (!empty($attempts) && ($attempts[array_key_last($attempts)] < $current_time - TIME_WINDOW)) {
        // Si el último registro es anterior a la ventana de tiempo y fue un bloqueo,
        // significa que el bloqueo ha expirado y los intentos se han limpiado.
        // Pero en este caso, el array_filter ya limpia, así que nos centramos solo en el conteo.
    }


    // 4. Verificar si se ha superado el límite
    if (count($recent_attempts) >= MAX_ATTEMPTS) {
        
        // Registrar el intento de bloqueo (para saber cuándo debe expirar el bloqueo)
        // Se añade un timestamp que se considerará como el inicio del bloqueo.
        $recent_attempts[] = $current_time;
        file_put_contents($file_path, json_encode($recent_attempts));
        
        // Calcular cuánto tiempo queda del bloqueo
        $time_remaining = BLOCK_DURATION - ($current_time - $recent_attempts[array_key_last($recent_attempts)]);
        
        // Asegurarse de que el tiempo restante sea positivo y enviar el encabezado 429
        if ($time_remaining > 0) {
            // PASO CLAVE: Añadir encabezados CORS antes de enviar la respuesta 429
            header("Access-Control-Allow-Origin: http://localhost:5173"); // 👈 AJUSTA TU ORIGEN REAL
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");            
            header('Content-Type: application/json', true, 429);
            // La fecha del encabezado Retry-After indica cuándo se puede volver a intentar
            header("Retry-After: " . $time_remaining); 
            echo json_encode(['success' => false, 'message' => "Demasiadas solicitudes. Inténtalo de nuevo en " . $time_remaining . " segundos.", 'status' => 'rate_limited']);
            exit();
        }
    } else {
        // 5. Registrar el intento actual (solo si no estamos bloqueados)
        $recent_attempts[] = $current_time;
        file_put_contents($file_path, json_encode($recent_attempts));
    }
    

    return false; // No hay límite
}