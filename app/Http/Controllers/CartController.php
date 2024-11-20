<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItems;
use App\Services\ProductService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

     /**
     * Agregar producto al carrito
     */
    public function addToCart(Request $request)
    {
        // Obtener los detalles del producto desde el microservicio SOAP
        $productDetails = $this->productService->getProductById($request->product_id);
        
        if ($productDetails['id']==null) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        // Crear o buscar el carrito del usuario
        $cart = Cart::firstOrCreate(['user_id' => $request->user_id]);

        // Crear o actualizar el item del carrito
        $cartItem = CartItems::firstOrCreate(
            [
                'cart_id' => $cart->id,
                'product_id' => $request->product_id
            ],
            ['price' => $productDetails['precio']]
        );

        // Si ya existe el producto en el carrito, sumar la cantidad
        $cartItem->quantity += $request->quantity;
        $cartItem->save();

        return response()->json(['message' => 'Producto agregado al carrito.', 'cartItem' => $cartItem], 201);
    }


    /**
     * Actualizar la cantidad de un producto en el carrito
     */
    public function updateCart(Request $request)
    {
        // Obtener el producto desde el servicio SOAP
        $productDetails = $this->productService->getProductById($request->product_id);

        if (!$productDetails) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        // Buscar el carrito del usuario
        $cart = Cart::where('user_id', $request->user_id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Carrito no encontrado.'], 404);
        }

        // Buscar el item en el carrito
        $cartItem = CartItems::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Producto no encontrado en el carrito.'], 404);
        }

        // Actualizar la cantidad del producto
        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json(['message' => 'Cantidad actualizada.', 'cartItem' => $cartItem], 200);
    }


    /**
     * Eliminar un producto del carrito
     */
    public function removeFromCart(Request $request)
    {
        // Buscar el carrito del usuario
        $cart = Cart::where('user_id', $request->user_id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Carrito no encontrado.'], 404);
        }

        // Buscar el item en el carrito y eliminarlo
        $cartItem = CartItems::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Producto no encontrado en el carrito.'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Producto eliminado del carrito.'], 200);
    }


    /**
     * Mostrar el contenido del carrito
     */
    public function showCart(Request $request)
    {
        // Cargar el carrito junto con los cartItems
        $cart = Cart::with('cartItems')->where('user_id', $request->user_id)->first();

        // Verificar si el carrito o los cartItems están vacíos
        if (!$cart || !$cart->cartItems) {
            return response()->json(['message' => 'Carrito vacío.'], 404);
        }

        $total = $cart->cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);

        $cart->total = $total;

        return response()->json(['cart' => $cart], 200);
    }

    public function paymentCart(Request $request)
    {
        // Llamar a showCart para obtener la información del carrito del usuario
        $cart = Cart::with('cartItems')->where('user_id', $request->user_id)->first();

        if (!$cart || !$cart->cartItems) {
            return response()->json(['message' => 'Carrito vacío.'], 404);
        }

        // Calcular el total del carrito
        $total = $cart->cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);

        // Preparar datos de pago
        $paymentData = [
            'user_id' => $request->user_id,
            'total_amount' => $total,
            'payment_method' => [
                'card_number' => $request->payment_method['card_number'],
                'expiry_date' => $request->payment_method['expiry_date'],
                'card_type' => $request->payment_method['card_type'],
                'cvv' => $request->payment_method['cvv'],
                'cardholder_name' => $request->payment_method['cardholder_name']
            ]
        ];

        // Llamada a la API externa para procesar el pago
        $response = $this->processPayment($paymentData);

        dd($response);
        // Verificar si el pago fue exitoso
        if ($response['status'] === 'success') {
            return response()->json([
                'message' => 'Pago realizado con éxito.',
                'transaction_id' => $response['transaction_id'],
                'amount' => $total
            ], 200);
        } else {
            return response()->json([
                'message' => 'El pago no pudo ser procesado.',
                'error' => $response['error']
            ], 500);
        }
    }


    private function processPayment($paymentData)
    {

        $url = 'http://127.0.0.1:8001/api/payments/process';

        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post($url, [
                'json' => $paymentData
            ]);
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
