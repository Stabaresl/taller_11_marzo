# Sistema de Ventas con Microservicios

> Taller — Clase 11 de marzo  
> Tecnologías: Laravel 11 · Flask · Express · JWT · Firebase Firestore · MongoDB

---

## 1. Arquitectura del Sistema

El sistema está construido sobre una arquitectura de microservicios donde el cliente nunca habla directamente con los microservicios — todo pasa por el API Gateway.

### Componentes

| Componente | Tecnología | Puerto | Responsabilidad |
|---|---|---|---|
| **API Gateway** | Laravel 11 | 8000 | Autenticación JWT, punto de entrada único |
| **Microservicio Inventario** | Flask + Firebase | 5000 | Gestión de productos y stock |
| **Microservicio Ventas** | Express + MongoDB | 3001 | Registro y consulta de ventas |

### Principios de diseño

- **Punto de entrada único:** el cliente siempre habla con el Gateway en el puerto 8000. Nunca directamente con Flask o Express.
- **Autenticación por JWT:** el Gateway valida el token en cada request protegido. Si el token es inválido devuelve 401 sin llegar a los microservicios.
- **Comunicación interna por X-Internal-Key:** los microservicios solo aceptan requests que incluyan esta clave en el header. Esto impide el acceso directo desde fuera del sistema.
- **Bases de datos separadas:** Firebase Firestore para inventario y MongoDB para ventas. Cada microservicio es dueño de sus datos.

---

## 2. Diagrama del Sistema

```
┌──────────────────────────────────────────────────────┐
│           CLIENTE  (Thunder Client / App)            │
└──────────────────────────┬───────────────────────────┘
                           │  Authorization: Bearer <JWT>
                           ▼
┌──────────────────────────────────────────────────────┐
│        API GATEWAY  —  Laravel 11  (puerto 8000)     │
│                                                      │
│   ✓ Valida JWT          ✓ Genera token en login      │
│   ✓ Gestiona logout     ✓ Agrega X-Internal-Key      │
│   ✓ Extrae usuarioId    ✓ Verifica stock antes venta │
└──────────────┬───────────────────────┬───────────────┘
               │                       │
     X-Internal-Key           X-Internal-Key
               │               + X-User-Id
               ▼                       ▼
┌──────────────────────┐   ┌─────────────────────┐
│  INVENTARIO (Flask)  │   │   VENTAS (Express)  │
│  Puerto: 5000        │   │   Puerto: 3001      │
│                      │   │                     │
│  Firebase Firestore  │   │  MongoDB            │
└──────────────────────┘   └─────────────────────┘
```

---

## 3. Endpoints documentados (vía Gateway)

> Todos los endpoints excepto `/api/auth/login` y `/api/auth/register` requieren:  
> `Authorization: Bearer <token>`

### 3.1 Autenticación

Base URL: `http://localhost:8000/api/auth`

| Método | Endpoint | Body | Respuesta |
|---|---|---|---|
| `POST` | `/auth/register` | `{ "name", "email", "password" }` | 201 — token + usuario |
| `POST` | `/auth/login` | `{ "email", "password" }` | 200 — token + usuario |
| `GET` | `/auth/me` | — | 200 — usuario autenticado |
| `POST` | `/auth/logout` | — | 200 — mensaje de cierre |

### 3.2 Inventario (Gateway → Flask → Firebase)

Base URL: `http://localhost:8000/api`

| Método | Endpoint | Body / Params | Respuesta |
|---|---|---|---|
| `POST` | `/productos` | `{ "nombre", "precio", "stock" }` | 201 — id del producto |
| `GET` | `/productos` | — | 200 — lista de productos |
| `GET` | `/productos/:id` | — | 200 — producto |
| `GET` | `/productos/:id/stock` | — | 200 — stock y disponibilidad |
| `PUT` | `/productos/:id/stock` | `{ "cantidad" }` | 200 — stock actualizado |

### 3.3 Ventas (Gateway → Express → MongoDB)

Base URL: `http://localhost:8000/api`

| Método | Endpoint | Body / Params | Respuesta |
|---|---|---|---|
| `POST` | `/ventas` | `{ "productoId", "cantidad", "total" }` | 201 — venta registrada |
| `GET` | `/ventas` | `?desde=&hasta=` (opcionales) | 200 — lista de ventas |
| `GET` | `/ventas/usuario/:id` | — | 200 — ventas del usuario |
| `GET` | `/ventas/:id` | — | 200 — venta específica |

### 3.4 Códigos de error

| Código | Cuándo ocurre |
|---|---|
| `400` | Campo faltante, tipo incorrecto, stock insuficiente |
| `401` | Token JWT ausente, inválido o expirado |
| `403` | Sin X-Internal-Key o acceso a datos de otro usuario |
| `404` | Producto o venta no encontrada |
| `503` | Microservicio no disponible |

---

## 4. Flujo de Registro de una Venta

Cuando el cliente hace `POST /api/ventas`, el sistema ejecuta estos pasos internamente:

```
Paso 1 — Cliente envía el request al Gateway
         POST /api/ventas
         Headers: Authorization: Bearer <token>
         Body: { productoId, cantidad, total }

Paso 2 — Gateway valida el JWT
         Verifica firma y expiración.
         Extrae el usuarioId del payload.
         Si es inválido → 401, se detiene aquí.

Paso 3 — Gateway consulta stock en Flask
         GET http://localhost:5000/productos/:productoId/stock
         Headers: X-Internal-Key

Paso 4 — Flask verifica en Firebase Firestore
         Si el producto no existe → 404
         Si el stock es menor a la cantidad → 400

Paso 5 — Gateway registra la venta en Express
         POST http://localhost:3001/api/ventas
         Headers: X-Internal-Key + X-User-Id: <usuarioId del JWT>
         Body: { usuarioId, productoId, cantidad, total }

Paso 6 — Express guarda en MongoDB
         Crea el documento de venta con fecha y timestamps.
         Si falla → 500, el stock NO se descuenta.

Paso 7 — Gateway descuenta el stock en Flask
         PUT http://localhost:5000/productos/:productoId/stock
         Body: { "cantidad": N }
         Solo se ejecuta si el paso 6 fue exitoso.

Paso 8 — Gateway responde al cliente
         201 con el objeto de la venta registrada.
```

### Manejo de errores en el flujo

- Si el stock es insuficiente **(paso 4):** se devuelve `400` al cliente. No se registra ningún dato.
- Si MongoDB falla **(paso 6):** se devuelve `500` y el stock **no** se descuenta.
- Si Flask falla al descontar **(paso 7):** la venta ya fue guardada. Se registra el error en los logs para conciliación manual.

---

## 5. Variables de Entorno

> **Importante:** La `INTERNAL_KEY` debe ser idéntica en los tres servicios. Nunca subir el `.env` real al repositorio.

### Gateway (Laravel) — `.env`
```
APP_KEY=base64:...
JWT_SECRET=clave-secreta-jwt-minimo-32-caracteres
INVENTARIO_URL=http://localhost:5000
VENTAS_URL=http://localhost:3001
INTERNAL_KEY=una-clave-larga-y-aleatoria
```

### Inventario (Flask) — `.env`
```
SECRET_KEY=clave-flask-secreta
INTERNAL_KEY=una-clave-larga-y-aleatoria
GOOGLE_APPLICATION_CREDENTIALS=serviceAccountKey.json
PORT=5000
FLASK_DEBUG=false
```

### Ventas (Express) — `.env`
```
PORT=3001
MONGO_URI=mongodb://localhost:27017/ventas_db
INTERNAL_KEY=una-clave-larga-y-aleatoria
```

---

## 6. Instalación y configuración

### Requisitos previos

| Herramienta | Versión mínima | Para qué se usa |
|---|---|---|
| PHP | 8.2 | Gateway Laravel |
| Composer | 2.x | Dependencias PHP |
| Node.js | 18.x | Microservicio Express |
| npm | 9.x | Dependencias Node |
| Python | 3.10+ | Microservicio Flask |
| pip | 23.x | Dependencias Python |
| MySQL | 8.x | Base de datos del Gateway |
| MongoDB | 6.x | Base de datos de ventas |

---

### 6.1 Gateway Laravel

**Dependencias principales (`composer.json`):**

| Paquete | Versión | Descripción |
|---|---|---|
| `laravel/framework` | ^12.0 | Framework base |
| `php-open-source-saver/jwt-auth` | ^2.9 | Autenticación JWT |
| `laravel/tinker` | ^2.10.1 | REPL para debugging |

**Instalación:**

```bash
cd gateway

# 1. Instalar dependencias PHP
composer install

# 2. Copiar el archivo de entorno
cp .env.example .env

# 3. Generar la clave de la aplicación
php artisan key:generate

# 4. Configurar el .env
#    DB_DATABASE=taller
#    DB_USERNAME=root
#    DB_PASSWORD=
#    INVENTARIO_URL=http://localhost:5000
#    VENTAS_URL=http://localhost:3001
#    INTERNAL_KEY=una-clave-larga-y-aleatoria

# 5. Generar el JWT secret
php artisan jwt:secret

# 6. Crear las tablas en la base de datos
php artisan migrate

# 7. Iniciar el servidor
php artisan serve
```

> El Gateway quedará corriendo en `http://localhost:8000`

---

### 6.2 Microservicio Inventario (Flask)

**Dependencias principales (`requirements.txt`):**

| Paquete | Versión | Descripción |
|---|---|---|
| `Flask` | 3.1.3 | Framework web |
| `flask-cors` | 6.0.2 | Manejo de CORS |
| `firebase-admin` | 7.2.0 | SDK de Firebase |
| `google-cloud-firestore` | 2.24.0 | Cliente de Firestore |
| `python-dotenv` | 1.2.2 | Variables de entorno |

**Instalación:**

```bash
cd inventario-flask

# 1. Crear entorno virtual (recomendado)
python -m venv venv

# Activar en Windows:
venv\Scripts\activate
# Activar en Mac/Linux:
source venv/bin/activate

# 2. Instalar todas las dependencias
pip install -r requirements.txt

# 3. Copiar el archivo de entorno
cp .env.example .env

# 4. Configurar el .env
#    INTERNAL_KEY=una-clave-larga-y-aleatoria
#    GOOGLE_APPLICATION_CREDENTIALS=serviceAccountKey.json
#    PORT=5000

# 5. Colocar el serviceAccountKey.json en la raiz del proyecto
#    (descargado desde Firebase Console > Configuracion > Cuentas de servicio)

# 6. Iniciar el servidor
python app.py
```

> El microservicio quedará corriendo en `http://localhost:5000`

---

### 6.3 Microservicio Ventas (Express)

**Dependencias principales (`package.json`):**

| Paquete | Versión | Descripción |
|---|---|---|
| `express` | ^4.19.0 | Framework web |
| `mongoose` | ^8.4.0 | ODM para MongoDB |
| `dotenv` | ^16.4.0 | Variables de entorno |
| `cors` | ^2.8.5 | Manejo de CORS |
| `nodemon` *(dev)* | ^3.1.0 | Reinicio automático en desarrollo |

**Instalación:**

```bash
cd ventas-express

# 1. Instalar dependencias
npm install

# 2. Copiar el archivo de entorno
cp .env.example .env

# 3. Configurar el .env
#    PORT=3001
#    MONGO_URI=mongodb://localhost:27017/ventas_db
#    INTERNAL_KEY=una-clave-larga-y-aleatoria

# 4. Asegurarse de que MongoDB este corriendo
mongod

# 5. Iniciar el servidor
node server.js

# O en modo desarrollo con recarga automatica:
npm run dev
```

> El microservicio quedará corriendo en `http://localhost:3001`

---

### 6.4 Levantar el sistema completo

Una vez instalados los tres servicios, abralos en terminales separadas en este orden:

```bash
# Terminal 1 - Gateway
cd gateway && php artisan serve

# Terminal 2 - Inventario
cd inventario-flask && python app.py

# Terminal 3 - Ventas
cd ventas-express && node server.js
```

Verifica que los tres esten corriendo antes de hacer cualquier request:

| Servicio | URL de verificación | Respuesta esperada |
|---|---|---|
| Gateway | `http://localhost:8000/up` | `{"status": "ok"}` |
| Flask | `http://localhost:5000/health` | `{"status": "ok", "servicio": "inventario-flask"}` |
| Express | `http://localhost:3001/health` | `{"status": "ok", "servicio": "ventas-express"}` |

---

## 7. Estructura de archivos

```
taller_11_marzo/
├── gateway/                        # API Gateway Laravel
│   ├── app/Http/Controllers/
│   │   ├── authController.php
│   │   ├── inventarioController.php
│   │   └── ventasController.php
│   ├── app/Models/User.php
│   ├── config/auth.php
│   └── routes/api.php
│
├── inventario-flask/               # Microservicio Flask
│   ├── app.py
│   ├── routes.py
│   ├── config.py
│   ├── .env
│   ├── .env.example
│   ├── .gitignore
│   └── requirements.txt
│
└── ventas-express/                 # Microservicio Express
    ├── server.js
    ├── routes/ventas.js
    ├── models/venta.js
    ├── middleware/verificarGateway.js
    └── .env
```