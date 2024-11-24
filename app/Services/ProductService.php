<?php

namespace App\Services;

use Exception;

class ProductService
{
    protected $wsdlUrl;

    public function __construct()
    {
        $this->wsdlUrl = 'http://localhost:8001/ProductoSoap.php';
    }

    /**
     * Obtener la información de un producto por ID.
     */
    public function getProductById($productId)
    {
        $xmlRequest = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="Productos">
            <soapenv:Header/>
            <soapenv:Body>
                <tns:VerProducto>
                    <id>{$productId}</id>
                </tns:VerProducto>
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

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);

            // Cargar la respuesta XML
            $xmlResponse = simplexml_load_string($response);

            // Registrar los espacios de nombres
            $namespaces = $xmlResponse->getNamespaces(true);
            $xmlResponse->registerXPathNamespace('ns1', $namespaces['ns1']); // registrar el namespace 'ns1'

            // Usar xpath con el prefijo de namespace adecuado
            $responseBody = $xmlResponse->xpath('//ns1:VerProductoResponse//return')[0];

            // Extraer los datos del producto
            $product = [
                'id' => (string) $responseBody->id,
                'nombre' => (string) $responseBody->nombre,
                'precio' => (string) $responseBody->precio,
                'stock' => (int) $responseBody->stock,
                'descripcion' => (string) $responseBody->descripcion,
                'categoria_id' => (int) $responseBody->categoria_id,
            ];

            // Retornar los datos del producto
            return $product;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
