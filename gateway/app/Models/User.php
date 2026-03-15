<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Campos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Campos ocultos en la serialización (nunca se devuelven al cliente).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversiones de tipos automáticas.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Métodos requeridos por la interfaz JWTSubject
    // Sin estos dos métodos el modelo no puede generar tokens JWT
    // ─────────────────────────────────────────────────────────

    /**
     * Retorna el identificador único que se almacena en el payload del JWT.
     * Por defecto es el ID del usuario en la base de datos.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Retorna claims adicionales que se incluirán en el payload del JWT.
     * Se puede usar para agregar datos extra como el rol del usuario.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}