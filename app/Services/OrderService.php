<?php

namespace App\Services;

use Exception;

class OrderService
{

    /**
     * Realizar un pago
     */
    public function createOrder($user_id, $cartItems, $total, $token)
    {
        $url = 'http://localhost:8004/api/newOrder';

        $client = new \GuzzleHttp\Client();

        // Convertir los cartItems
        $items = collect($cartItems)->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        try {
            $response = $client->post(
                $url,
                [
                    'json' => [
                        'user_id' => $user_id,
                        'items' => $items,
                        'total' => $total
                    ],
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
