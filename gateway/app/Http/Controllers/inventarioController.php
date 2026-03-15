<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class inventarioController extends Controller
{
    private string $url;

    public function __construct()
    {
        $this->url = env('INVENTARIO_URL', 'http://localhost:5000');
    }

    /**
     * Retorna un cliente HTTP preconfigurado con la clave interna.
     * Todos los requests al microservicio de inventario usan este cliente.
     */
    private function http()
    {
        return Http::withHeaders([
            'X-Internal-Key' => env('INTERNAL_KEY'),
            'Content-Type'   => 'application/json',
            'Accept'         => 'application/json',
        ]);
    }

    /**
     * Obtener todos los productos del microservicio Flask.
     * GET /api/productos
     */
    public function getProductos()
    {
        try {
            $response = $this->http()->get("{$this->url}/productos");
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de inventario no disponible'], 503);
        }
    }

    /**
     * Crear un nuevo producto en el microservicio Flask.
     * POST /api/productos
     */
    public function createProducto(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'precio' => 'required|numeric|min:0',
            'stock'  => 'required|integer|min:0',
        ]);

        try {
            $response = $this->http()->post("{$this->url}/productos", $request->only('nombre', 'precio', 'stock'));
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de inventario no disponible'], 503);
        }
    }

    /**
     * Consultar el stock de un producto específico.
     * GET /api/productos/{id}/stock
     */
    public function getStock(string $id)
    {
        try {
            $response = $this->http()->get("{$this->url}/productos/{$id}/stock");
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de inventario no disponible'], 503);
        }
    }

    /**
     * Obtener un producto específico por ID.
     * GET /api/productos/{id}
     */
    public function getProducto(string $id)
    {
        try {
            $response = $this->http()->get("{$this->url}/productos/{$id}");
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de inventario no disponible'], 503);
        }
    }
}