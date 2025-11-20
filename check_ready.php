<?php
session_start();

header('Content-Type: application/json');

$telefono = $_SESSION['celular'] ?? null;

if ($telefono === null) {
    echo json_encode(['ready' => false]);
    exit;
}

$safe = preg_replace('/[^0-9]+/', '_', $telefono);
$flag = __DIR__ . '/ready_' . $safe . '.flag';

if (is_file($flag)) {
    // Eliminamos el flag para que no vuelva a dispararse
    @unlink($flag);
    echo json_encode(['ready' => true]);
} else {
    echo json_encode(['ready' => false]);
}
