<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ventasController extends Controller
{
    private string $ventasUrl;
    private string $inventarioUrl;

    public function __construct()
    {
        $this->ventasUrl     = env('VENTAS_URL',     'http://localhost:3001');
        $this->inventarioUrl = env('INVENTARIO_URL', 'http://localhost:5000');
    }

    /**
     * Retorna un cliente HTTP preconfigurado con la clave interna.
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
     * Consultar todas las ventas con filtro opcional por fecha.
     * GET /api/ventas?desde=2025-01-01&hasta=2025-12-31
     */
    public function getVentas(Request $request)
    {
        try {
            $response = $this->http()->get("{$this->ventasUrl}/api/ventas", $request->only('desde', 'hasta'));
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de ventas no disponible'], 503);
        }
    }

    /**
     * Registrar una nueva venta.
     * Flujo: validar JWT → verificar stock → guardar venta → descontar stock
     * POST /api/ventas
     */
    public function createVenta(Request $request)
    {
        // 1. Validar campos del request
        $request->validate([
            'productoId' => 'required|string',
            'cantidad'   => 'required|integer|min:1',
            'total'      => 'required|numeric|min:0',
        ]);

        $usuarioId  = (string) auth()->id();  // extraído del JWT, no del body
        $productoId = $request->productoId;
        $cantidad   = $request->cantidad;

        // 2. Verificar disponibilidad y cantidad de stock en Flask
        try {
            $stockResponse = $this->http()->get("{$this->inventarioUrl}/productos/{$productoId}/stock");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de inventario no disponible'], 503);
        }

        if (!$stockResponse->ok()) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $stockData = $stockResponse->json();

        if (!$stockData['disponible']) {
            return response()->json(['error' => 'Producto sin stock disponible'], 400);
        }

        if ($stockData['stock'] < $cantidad) {
            return response()->json([
                'error'             => 'Stock insuficiente',
                'stock_disponible'  => $stockData['stock'],
                'cantidad_solicitada' => $cantidad,
            ], 400);
        }

        // 3. Registrar la venta en Express — usuarioId viene del JWT
        try {
            $ventaResponse = $this->http()
                ->withHeaders(['X-User-Id' => $usuarioId])
                ->post("{$this->ventasUrl}/api/ventas", [
                    'usuarioId'  => $usuarioId,
                    'productoId' => $productoId,
                    'cantidad'   => $cantidad,
                    'total'      => $request->total,
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de ventas no disponible'], 503);
        }

        // 4. Si la venta falló, NO descontar stock — evita desincronización
        if (!$ventaResponse->successful()) {
            return response()->json(
                ['error' => 'Error al registrar la venta', 'detalle' => $ventaResponse->json()],
                $ventaResponse->status()
            );
        }

        // 5. Descontar stock en Flask — solo si la venta fue exitosa
        try {
            $this->http()->put("{$this->inventarioUrl}/productos/{$productoId}/stock", [
                'cantidad' => $cantidad,
            ]);
        } catch (\Exception $e) {
            // La venta ya fue guardada — registrar el error para conciliación manual
            \Log::error("Stock no descontado tras venta exitosa", [
                'productoId' => $productoId,
                'cantidad'   => $cantidad,
                'venta'      => $ventaResponse->json(),
            ]);
        }

        return response()->json($ventaResponse->json(), $ventaResponse->status());
    }

    /**
     * Consultar ventas de un usuario específico.
     * Solo el propio usuario puede ver sus ventas.
     * GET /api/ventas/usuario/{usuarioId}
     */
    public function getVentasPorUsuario(string $usuarioId)
    {
        // Verificar que el usuario autenticado solo pueda ver sus propias ventas
        if ((string) auth()->id() !== $usuarioId) {
            return response()->json(['error' => 'No autorizado para ver las ventas de otro usuario'], 403);
        }

        try {
            $response = $this->http()->get("{$this->ventasUrl}/api/ventas/usuario/{$usuarioId}");
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Servicio de ventas no disponible'], 503);
        }
    }
}