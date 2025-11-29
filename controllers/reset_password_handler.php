<?php
if (ob_get_level()) {
    ob_clean(); 
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'success', 'message' => 'TEST ABSOLUTAMENTE LIMPIO']);
exit;
// NO HAY ESPACIOS NI SALTOS DE LÍNEA DESPUÉS DE ESTO