<?php

namespace App\Services;

use Exception;

class PaymentService
{

    /**
     * Realizar un pago
     */
    public function processPayment($paymentData, $token)
    {
        $url = 'http://localhost:8002/api/payments/process';

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post(
                $url,
                [
                    'json' => $paymentData,
                    'headers' => [
                        'Authorization' => $token
                    ]
                ]
            );
            $body = json_decode($response->getBody(), true);
            return $body;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}
