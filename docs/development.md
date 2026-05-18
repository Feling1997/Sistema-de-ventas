# Guía de Desarrollo

## Estructura del Código

### Patrón MVC
El proyecto sigue estrictamente el patrón Modelo-Vista-Controlador:

#### Modelos (`aplicacion/modelos/`)
- Métodos estáticos para operaciones CRUD
- Conexión a BD mediante PDO
- Manejo de excepciones con try/catch
- Logging de errores

#### Vistas (`aplicacion/vistas/`)
- Archivos PHP con HTML embebido
- Uso de `include` para parciales
- Sanitización con `htmlspecialchars()`
- Bootstrap para estilos

#### Controladores (`aplicacion/controladores/`)
- Un método por acción
- Validación de entrada
- Coordinación entre modelo y vista
- Manejo de sesiones y redirecciones

### Convenciones de Código

#### Nombres
- Clases: `PascalCase` (ControladorAuth)
- Métodos: `camelCase` (buscarPorId)
- Variables: `snake_case` ($usuario_logueado)
- Archivos: `PascalCase.php`

#### Base de Datos
- Tablas: `snake_case` (usuarios, detalle_venta)
- Campos: `snake_case` (id_usuario, precio_final)
- FK: `id_tabla` (id_cliente, id_producto)

### Funciones Auxiliares

#### `configuraciones/ayudas.php`
```php
function obtener_get(string $key, $default = null)
function obtener_post(string $key, $default = null)
function texto_invalido(string $texto): bool
function registrar_log(string $origen, string $mensaje): void
```

#### `configuraciones/seguridad.php`
```php
function iniciar_sesion(): void
function usuario_logueado(): ?array
function requiere_login(): void
function requiere_rol(string $rol): void
```

#### `configuraciones/csrf.php`
```php
function csrf_token(): string
function csrf_valido(string $token): bool
```

### Desarrollo de Nuevas Funcionalidades

#### 1. Crear Modelo
```php
class NuevoModelo {
    public static function listar_todos(): array {
        // Implementación
    }
    
    public static function buscar_por_id(int $id): ?array {
        // Implementación
    }
    
    public static function crear(array $datos): bool {
        // Implementación
    }
}
```

#### 2. Crear Controlador
```php
class ControladorNuevo {
    public function index(): void {
        $datos = NuevoModelo::listar_todos();
        include __DIR__ . "/../vistas/nuevo/index.php";
    }
    
    public function crear(): void {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Procesar formulario
        }
        include __DIR__ . "/../vistas/nuevo/formulario.php";
    }
}
```

#### 3. Actualizar Enrutador
Agregar al array `$mapa` en `publico/index.php`:
```php
"nuevo" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorNuevo.php", "clase" => "ControladorNuevo"],
```

#### 4. Crear Vistas
- `vistas/nuevo/index.php`: Lista de elementos
- `vistas/nuevo/formulario.php`: Formulario CRUD
- Usar parciales para header/footer

### Validación y Seguridad

#### Validación de Entrada
```php
$nombre = trim((string)obtener_post("nombre", ""));
if (texto_invalido($nombre)) {
    $error = "Nombre inválido";
}
```

#### Protección CSRF
```php
// En formulario
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">

// En controlador
$csrf = obtener_post("csrf", "");
if (!csrf_valido($csrf)) {
    $error = "Token inválido";
}
```

#### Sanitización de Salida
```php
echo htmlspecialchars($variable);
```

### Manejo de Errores

#### Try/Catch en Modelos
```php
try {
    // Operación de BD
} catch (Throwable $e) {
    registrar_log("Modelo::metodo", $e->getMessage());
    return false; // o null/array vacío
}
```

#### Validación en Controladores
```php
if ($resultado === false) {
    $error = "Error al guardar";
    // Mostrar vista con error
}
```

### Base de Datos

#### Conexión PDO
```php
$pdo = obtener_pdo();
if ($pdo !== null) {
    // Operaciones
}
```

#### Consultas Preparadas
```php
$sql = "SELECT * FROM tabla WHERE id = ?";
$st = $pdo->prepare($sql);
$st->execute([$id]);
$resultado = $st->fetch();
```

#### Transacciones
```php
$pdo->beginTransaction();
try {
    // Múltiples operaciones
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
```

### Testing

#### Pruebas Manuales
- Verificar CRUD completo
- Probar validaciones
- Comprobar permisos
- Testear en diferentes navegadores

#### Debugging
- Usar `var_dump()` para variables
- Revisar logs en `almacenamiento/logs/`
- Verificar queries en MySQL

### Despliegue

#### Checklist Pre-Despliegue
- [ ] Ejecutar `composer install --no-dev`
- [ ] Verificar configuración de BD
- [ ] Probar funcionalidades críticas
- [ ] Configurar permisos de archivos
- [ ] Configurar logs

#### Variables de Entorno
Considerar mover configuraciones sensibles a variables de entorno:
```php
$host = getenv('DB_HOST') ?: 'localhost';
```

### Mejoras Futuras

#### Posibles Extensiones
- API REST para integraciones
- Autenticación OAuth
- Sistema de reportes avanzado
- Notificaciones por email
- Integración con sistemas de pago
- Multi-tenancy

#### Migración a Framework
- Laravel: Estructura similar, fácil migración
- Symfony: Más robusto para aplicaciones grandes
- CodeIgniter: Ligero y rápido

### Contribución

#### Estándares de Código
- Usar PSR-12 para PHP
- Commits descriptivos en español
- Documentar funciones complejas
- Mantener cobertura de funcionalidades

#### Code Review
- Verificar seguridad
- Validar lógica de negocio
- Comprobar manejo de errores
- Revisar rendimiento de queries