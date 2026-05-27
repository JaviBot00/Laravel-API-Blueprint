# 01 · Introducción: Laravel vs Slim PHP

> **Para quién es esta guía:** Desarrolladores que conocen PHP y han trabajado con Slim Framework. No se asume conocimiento previo de Laravel.

---

## ¿Por qué cambiar si Slim funciona?

Slim no es un framework malo. Es una librería de enrutamiento ligera que hace bien lo que promete: recibir una petición HTTP, ejecutar una función y devolver una respuesta. El problema no es Slim, es que Slim no opina sobre cómo debes organizar el resto de tu aplicación. Eso es libertad, pero también es una trampa.

Con el tiempo, una API en Slim sin arquitectura definida tiende a crecer así:

```php
// index.php — el archivo que lo tiene todo
$app->get('/users', function (Request $request, Response $response) {
    // validación aquí
    // consulta SQL aquí
    // lógica de negocio aquí
    // formato de respuesta aquí
    // y un include por si acaso
    include 'helpers.php';
});
```

Esto funciona. Pero no escala, no se testea bien y cuando alguien nuevo entra al proyecto tarda días en entender qué hace qué.

**Laravel resuelve esto obligándote a separar las responsabilidades desde el primer momento**, no porque seas disciplinado, sino porque si no lo haces, el framework directamente no funciona como esperas.

---

## La misma petición en Slim y en Laravel

Para entender el cambio, nada mejor que ver el mismo endpoint en los dos frameworks.

### En Slim

```php
// routes.php
$app->get('/api/todos', function (Request $request, Response $response) {
    $pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');
    $stmt = $pdo->query('SELECT * FROM todos');
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($todos));
    return $response->withHeader('Content-Type', 'application/json');
});
```

Todo en un sitio. Fácil de leer al principio, difícil de mantener después.

### En Laravel

```php
// routes/api.php — solo define la ruta
Route::get('/todos', [TodoController::class, 'index']);
```

```php
// app/Http/Controllers/TodoController.php — solo gestiona la petición
public function index(): JsonResponse
{
    $todos = Todo::all();
    return response()->json($todos);
}
```

```php
// app/Models/Todo.php — solo representa los datos
class Todo extends Model
{
    protected $fillable = ['title', 'completed', 'user_id'];
}
```

La lógica está repartida en clases con una responsabilidad cada una. Esto es lo que en programación se llama **Separación de Responsabilidades (SoC)** y es la base de cualquier arquitectura mantenible.

---

## Conceptos de Slim y su equivalente en Laravel

Si vienes de Slim, ya conoces los conceptos. Laravel los llama diferente y los expande:

| Concepto en Slim | Equivalente en Laravel | Diferencia principal |
|---|---|---|
| Función en la ruta (closure) | **Controller** | En Laravel es una clase dedicada, no una función anónima |
| Middleware | **Middleware** | Igual concepto, más integrado en el ciclo de vida |
| Inyección de dependencias (Container) | **Service Container** | Más potente, resuelve dependencias automáticamente |
| `$request`, `$response` | `Request`, `JsonResponse` | Similares, con más métodos disponibles |
| PDO / SQL directo | **Eloquent ORM** | No escribes SQL, trabajas con objetos PHP |
| Variables de entorno (dotenv) | `.env` + `config/` | Igual concepto, con capa de configuración encima |

---

## La estructura de carpetas de Laravel explicada

Cuando creas un proyecto Laravel nuevo, la cantidad de carpetas puede asustar. Aquí solo las que vas a tocar en esta API:

```cmd
app/
├── Http/
│   ├── Controllers/    ← Aquí va la lógica de cada endpoint
│   ├── Middleware/     ← Filtros que se ejecutan antes/después de cada petición
│   └── Requests/       ← Validación de datos de entrada (opcional pero recomendado)
├── Models/             ← Las clases que representan las tablas de la base de datos
└── Policies/           ← Reglas de autorización ("¿puede este usuario hacer esto?")

routes/
└── api.php             ← El único archivo de rutas que te importa para una API

database/
├── migrations/         ← Scripts que crean/modifican las tablas
└── seeders/            ← Scripts que insertan datos de prueba

config/
└── *.php               ← Configuración de cada paquete (jwt, swagger, etc.)
```

El resto de carpetas (`resources/`, `lang/`, `storage/`...) existen pero no las vas a necesitar para una API pura.

---

## El ciclo de vida de una petición en Laravel

Entender esto es clave. Cuando llega una petición HTTP a tu API, Laravel la procesa en este orden:

```cmd
Petición HTTP entrante
        ↓
   public/index.php          ← Punto de entrada único (como en Slim)
        ↓
   Bootstrap de la app       ← Laravel carga su configuración
        ↓
   Middlewares globales       ← Se ejecutan siempre (CORS, throttle...)
        ↓
   Router (routes/api.php)   ← Busca qué controlador maneja esta ruta
        ↓
   Middlewares de ruta        ← Se ejecutan si la ruta los tiene (auth, permisos...)
        ↓
   Controller@método          ← Tu código
        ↓
   Response                   ← Laravel envía la respuesta JSON
```

Si has trabajado con Slim, esto te resultará familiar. La diferencia es que en Laravel cada capa está bien definida y separada, no mezclada en un solo archivo.

---

## ¿Qué es Eloquent y por qué importa?

En Slim probablemente usabas PDO o alguna librería de queries para hablar con la base de datos. En Laravel usarás **Eloquent**, el ORM incluido.

Un ORM (Object-Relational Mapper) convierte las filas de tu base de datos en objetos PHP. En lugar de escribir SQL, trabajas con métodos:

```php
// Con PDO (Slim)
$stmt = $pdo->prepare('SELECT * FROM todos WHERE user_id = ? AND completed = 0');
$stmt->execute([$userId]);
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Con Eloquent (Laravel)
$todos = Todo::where('user_id', $userId)
             ->where('completed', false)
             ->get();
```

El resultado es el mismo. El código de Laravel es más legible, más seguro por defecto (previene SQL injection automáticamente) y más fácil de modificar.

---

## Siguiente paso

Con estos conceptos claros, el siguiente documento explica cómo funciona la autenticación JWT en Laravel y cómo proteger los endpoints de la API.

→ [02 · Autenticación JWT](02-autenticacion-jwt.md)
