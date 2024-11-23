<?php

namespace App\Services;

use Exception;

class UserService
{
    protected $wsdlUrl;

    public function __construct()
    {
        // URL del servicio SOAP de usuarios
        $this->wsdlUrl = 'http://localhost:8002/LoginSoap.php';
    }

    /**
     * Verificar si el usuario está registrado y logueado.
     */
    public function validateSesion($token)
    {
        // Paso 1: Realizar la solicitud SOAP para verificar si el usuario está logueado
        $response = $this->checkUserLogin($token);
        $message = $response['message'];
        $userId = $response['usuario_id'];

        // Paso 2: Verificar si el servicio devuelve éxito
        if ($response['success']) {
            // El usuario está logueado y registrado
            return [
                'success' => true,
                'message' => $message,
                'usuario_id' => $userId,
                'token' => $token
            ];
        }

        // El usuario no está registrado o logueado
        return [
            'success' => false
        ];
    }

    /**
     * Realizar la petición SOAP para verificar el login del usuario por su nickname.
     */
    private function checkUserLogin($token)
    {
        // Crear la solicitud SOAP para verificar si el usuario está logueado
        $xmlRequest = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="Usuarios">
            <soapenv:Header/>
            <soapenv:Body>
                <tns:ValidarSesion>
                    <usuario_id>{$token}</usuario_id>
                </tns:ValidarSesion>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;

        try {
            $ch = curl_init($this->wsdlUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xmlRequest),
            ]);

            // Ejecutar la petición SOAP
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);

            // Procesar la respuesta SOAP
            $xmlResponse = simplexml_load_string($response);

            // Registrar los espacios de nombres
            $namespaces = $xmlResponse->getNamespaces(true);
            $xmlResponse->registerXPathNamespace('ns1', $namespaces['ns1']);

            // Usar xpath con el prefijo de namespace adecuado
            $responseBody = $xmlResponse->xpath('//ns1:ValidarSesionResponse//return')[0];

            return [
                'success' => true,
                'message' => $responseBody->message,
                'usuario_id' => $responseBody->usuario_id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'SOAP request failed: ' . $e->getMessage()
            ];
        }
    }
}
