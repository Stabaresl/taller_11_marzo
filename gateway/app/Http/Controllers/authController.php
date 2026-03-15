<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class authController extends Controller
{
    /**
     * Fuerza el uso del guard JWT (api) en todos los métodos.
     * Sin esto Laravel resuelve el guard "web" que no tiene login() ni attempt().
     */
    protected function guard()
    {
        return auth()->guard('api');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $this->guard()->login($user);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'token'   => $token,
            'type'    => 'bearer',
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = $this->guard()->attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        return response()->json([
            'token' => $token,
            'type'  => 'bearer',
            'user'  => $this->guard()->user(),
        ]);
    }

    public function logout()
    {
        $this->guard()->logout();
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function me()
    {
        return response()->json($this->guard()->user());
    }
}