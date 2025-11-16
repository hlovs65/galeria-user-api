<?php
// config/ratelimit.php

// -----------------------------------------------------------
// CONFIGURACIÓN DE RATE LIMITING
// -----------------------------------------------------------

// Límite máximo de intentos permitidos en el intervalo de tiempo.
define('MAX_ATTEMPTS', 5);

// Intervalo de tiempo en segundos (ej: 60 segundos = 1 minuto).
define('TIME_WINDOW', 60); 

// Duración del bloqueo en segundos si se excede el límite (ej: 5 minutos).
define('BLOCK_DURATION', 300); 

// Directorio donde se almacenarán los archivos de conteo.
// Asegúrate de que el servidor web tenga permisos de escritura en este directorio.
define('RATE_LIMIT_DIR', '/tmp/ratelimit_logs/'); 

// -----------------------------------------------------------
// FUNCIONES DE UTILIDAD
// -----------------------------------------------------------

/**
 * Obtiene la dirección IP real del cliente.
 * Necesario para entornos detrás de proxies/balanceadores (como la vida real).
 */
function get_client_ip() {
    // Verificar encabezados comunes para IP real detrás de proxy/CDN
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Puede contener múltiples IPs, tomamos la primera (la del cliente)
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // Sanitizar y devolver
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}