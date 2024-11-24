<?php

namespace App\Services;

use Exception;

class PaymentService
{

    /**
     * Realizar un pago
     */
    public function processPayment($paymentData)
    {
        $url = 'http://localhost:8000/api/payments/process';

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post(
                $url,
                [
                    'json' => $paymentData,
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
