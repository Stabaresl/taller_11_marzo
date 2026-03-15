<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VentasController extends Controller
{
    private $ventasUrl;
    private $inventarioUrl;

    public function __construct()
    {
        $this->ventasUrl     = env('VENTAS_URL');
        $this->inventarioUrl = env('INVENTARIO_URL');
    }

    private function http()
    {
        return Http::withHeaders([
            'X-Internal-Key' => env('INTERNAL_KEY')
        ]);
    }

    public function getVentas(Request $request)
    {
        $response = $this->http()->get("{$this->ventasUrl}/api/ventas", $request->query());
        return response()->json($response->json(), $response->status());
    }

    public function createVenta(Request $request)
    {
        $usuarioId  = (string) auth()->id();
        $productoId = $request->productoId;
        $cantidad   = $request->cantidad;

        $stock = $this->http()->get("{$this->inventarioUrl}/productos/{$productoId}/stock");

        if (!$stock->ok() || !$stock->json()['disponible']) {
            return response()->json(['error' => 'Producto no encontrado o sin stock'], 400);
        }
        if ($stock->json()['stock'] < $cantidad) {
            return response()->json(['error' => 'No hay suficiente stock'], 400);
        }

        $venta = $this->http()->post("{$this->ventasUrl}/api/ventas", [
            'usuarioId'  => $usuarioId,
            'productoId' => $productoId,
            'cantidad'   => $cantidad,
            'total'      => $request->total,
        ]);

        $this->http()->put("{$this->inventarioUrl}/productos/{$productoId}/stock", [
            'cantidad' => $cantidad
        ]);

        return response()->json($venta->json(), $venta->status());
    }

    public function getVentasPorUsuario($usuarioId)
    {
        $response = $this->http()->get("{$this->ventasUrl}/api/ventas/usuario/{$usuarioId}");
        return response()->json($response->json(), $response->status());
    }
}