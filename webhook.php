<?php
    const TOKEN = "CREARTOKENPERSONALLOQUESEA";
    const WEBHOOK_URL = "https://yoururl/webhook.php";
    const PROCESSED_MESSAGES_FILE = "processed_messages.json";

    // Función para obtener mensajes procesados y evitar respuestas duplicados
    function getProcessedMessages() {
        if (file_exists(PROCESSED_MESSAGES_FILE)) {
            $content = file_get_contents(PROCESSED_MESSAGES_FILE);
            return json_decode($content, true) ?: [];
        }
        return [];
    }

    function verificarToken() {
        try{
            $token = $req['hub_verify_token'];
            $challenge = $req['hub_challenge'];

            if(isset($challenge) && isset($token) && $token === TOKEN_ANDERCODE){
                $res->send($challenge);
            }else{
                $res ->status(400)->send();
            }
        }
        catch (Exception $e) {
            $res ->status(400)->send();
            }
    }

    function recibirMensajes($req, $res) {
        try{
            $entry = $req['entry'][0];
            $changes = $entry['changes'][0];
            $value = $changes['value'];
            //Se seleccionan únicamente los datos del mensaje
            $objetomensaje = $value['messages'];
            $mensaje = $objetomensaje[0];

            //Se selecciona el contenido del mensaje y el número de teléfono
            $comentario = $mensaje['text']['body'];
            // Fue necesario eliminar el tercer dígito del número para poder responder el mensaje (Ejemplo: 5216682135047 -> 526682135047, si existen errores después revisar si cambia la numeración)
            $numero = substr($mensaje['from'], 0, 2). substr($mensaje['from'], 3);

            // Enviar mensaje de respuesta
            EnviarMensajeWhatsapp($comentario, $numero);
            // Se crea un log para revisar el contenido de las respuestas
            $archivo = fopen("log.txt", "a");
            $texto = json_encode($numero);
            fwrite($archivo, $texto);
            fclose($archivo);

            $res ->send("EVENT_RECEIVED");
        }
        catch (Exception $e) {
            $res ->send("EVENT_RECEIVED");
            }
    }

    function EnviarMensajeWhatsapp($comentario, $numero) {
        $comentario = strtolower($comentario);

        if(strpos($comentario, 'hola') !== false){
            $data = json_encode([
                "messaging_product"=> "whatsapp",    
                "recipient_type"=> "individual",
                "to"=> $numero,
                "type"=> "text",
                "text"=> [
                    "preview_url"=> false,
                    "body"=> "Hola mundo"
                ]
            ]);
        }else{
            $data = json_encode([
                "messaging_product"=> "whatsapp",    
                "recipient_type"=> "individual",
                "to"=> $numero,
                "type"=> "text",
                "text"=> [
                    "preview_url"=> false,
                    "body"=> "Selecciona una opción: \n1. Más información\n2. Agendar una cita"
                ]
            ]);
        }
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/json\r\nAuthorization: Bearer TUTOKENGENERADOENCONFIGURACIONDEAPIDEVELOPERSFACEBOOK\r\n",
                'content' => $data,
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents('https://graph.facebook.com/v22.0/TUURLGENERADAENCONFIGURACIONDEAPI/messages', false, $context);

        if($response === FALSE){
            echo "Error enviando mensaje\n";
        }else{
            echo "Mensaje enviado correctamente\n";
        }
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $intput = file_get_contents('php://input');
        $data = json_decode($intput, true);
        recibirMensajes($data, http_response_code());
    }else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if(isset($_GET['hub_mode']) && isset($_GET['hub_verify_token']) && ($_GET['hub_challenge']) && $_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === TOKEN_ANDERCODE) {
            echo $_GET['hub_challenge'];
        }else{
            http_response_code(403);
        }
    }


?>
