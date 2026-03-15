<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventarioController extends Controller
{
    private $url;

    public function __construct()
    {
        $this->url = env('INVENTARIO_URL');
    }

    private function http()
    {
        return Http::withHeaders([
            'X-Internal-Key' => env('INTERNAL_KEY')
        ]);
    }

    public function getProductos()
    {
        $response = $this->http()->get("{$this->url}/productos");
        return response()->json($response->json(), $response->status());
    }

    public function createProducto(Request $request)
    {
        $response = $this->http()->post("{$this->url}/productos", $request->all());
        return response()->json($response->json(), $response->status());
    }

    public function getStock($id)
    {
        $response = $this->http()->get("{$this->url}/productos/{$id}/stock");
        return response()->json($response->json(), $response->status());
    }
}