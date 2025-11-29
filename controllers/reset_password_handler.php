<?php
// ----------------------------------------------------
// ✅ BLOQUE CORS AÑADIDO (¡CRÍTICO!)
// ----------------------------------------------------
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}
// ----------------------------------------------------
if (ob_get_level()) {
    ob_clean(); 
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'success', 'message' => 'TEST ABSOLUTAMENTE LIMPIO']);
exit;
// NO HAY ESPACIOS NI SALTOS DE LÍNEA DESPUÉS DE ESTO