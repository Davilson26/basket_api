<?php

namespace App\Services;

use Exception;

class OrderService
{

    /**
     * Realizar un pago
     */
    public function createOrder($userId, $cartItems, $total)
    {
        $url = 'http://localhost:8004/api/orders/newOrder';

        $client = new \GuzzleHttp\Client();

        // Crear el array de items
        $items = $cartItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray(); // Convertir a array regular

        try {
            $response = $client->post(
                $url,
                [
                    'json' => [
                        'user_id' => $userId,
                        'total_amount' => $total,
                        'items' => $items
                    ],
               /*      'headers' => [
                        'Authorization' => $token,
                    ] */
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
