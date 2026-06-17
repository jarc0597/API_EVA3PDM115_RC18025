<?php
// =====================================================
// API REST - Evaluado 3 | RC18025 Rivera Coreas, Josué Alberto
// PDM115 - Programación de Dispositivos Móviles
// Ciclo I-2026
// =====================================================
 
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
 
require __DIR__ . '/../vendor/autoload.php';
 
// Cenexion de la BD
//se debe de cambiar al trabajar con hosting
function getConexion(): PDO {
    $host    = 'etpzew.h.filess.io';
    $db      = 'ev3_rc18025_striplamp';
    $user    = 'ev3_rc18025_striplamp';
    $pass    = '7102ea051d1252737f5f7253fefbaf5660c686b2';
    $port    = 61001;
    $charset = 'utf8mb4';

 
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
	
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
 
    return new PDO($dsn, $user, $pass, $options);
}
 
//se crea la app
$app = AppFactory::create();
 
// Permitir que Slim lea el basePath es la dirección base del proyecto
$app->setBasePath('/api_rc18025/public');
 
// Middleware para parsear JSON en el body
$app->addBodyParsingMiddleware();
 
// Middleware de errores (muestra detalles, útil en desarrollo)
$app->addErrorMiddleware(true, true, true);
 
// =====================================================
// ENDPOINTS DE VEHÍCULOS
// =====================================================
 
// GET /vehiculos
// Retorna todos los vehículos registrados

$app->get('/vehiculos', function (Request $request, Response $response) {
    try {
        $pdo  = getConexion();
        $stmt = $pdo->query("SELECT * FROM Vehiculos");
        $data = $stmt->fetchAll();
 
        $response->getBody()->write(json_encode([
            'success' => true,
            'data'    => $data
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
 
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'mensaje' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
 
/**
 * método POST /vehiculos
 * Inserta un nuevo vehículo
 * Ejemplo del Body JSON esperado:
 * {
 *   "Placa": "P123-456",
 *   "ModeloVehiculo": "Corolla",
 *   "Color": "Blanco",
 *   "AnioFabricacion": 2022,
 *   "Kilometraje": 15000,
 *   "PrecioOriginal": 18500.00,
 *   "IdMarca": "TOY"
 * }
 */
$app->post('/vehiculos', function (Request $request, Response $response) {
    try {
        $body = $request->getParsedBody();
 
        // Validación de campos obligatorios
        if (empty($body['Placa']) || empty($body['ModeloVehiculo']) || empty($body['IdMarca'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'mensaje' => 'Placa, ModeloVehiculo e IdMarca son obligatorios.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
 
        $pdo  = getConexion();
        $stmt = $pdo->prepare("
            INSERT INTO Vehiculos (Placa, ModeloVehiculo, Color, AnioFabricacion, Kilometraje, PrecioOriginal, IdMarca)
            VALUES (:Placa, :ModeloVehiculo, :Color, :AnioFabricacion, :Kilometraje, :PrecioOriginal, :IdMarca)
        ");
 
        $stmt->execute([
            ':Placa'           => $body['Placa'],
            ':ModeloVehiculo'  => $body['ModeloVehiculo'],
            ':Color'           => $body['Color']            ?? null,
            ':AnioFabricacion' => $body['AnioFabricacion']  ?? null,
            ':Kilometraje'     => $body['Kilometraje']      ?? null,
            ':PrecioOriginal'  => $body['PrecioOriginal']   ?? null,
            ':IdMarca'         => $body['IdMarca'],
        ]);
 
        $response->getBody()->write(json_encode([
            'success' => true,
            'mensaje' => 'Vehículo registrado correctamente.',
            'Placa'   => $body['Placa']
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
 
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'mensaje' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
 
// =====================================================
// ENDPOINTS DE MARCAS DE VEHÍCULOS
// =====================================================
 
// método GET /marcas/{id}
 //Retorna una marca específica por su IdMarca
 //Ejemplo: GET /marcas/TOY

$app->get('/marcas/{id}', function (Request $request, Response $response, array $args) {
    try {
        $id   = $args['id'];
        $pdo  = getConexion();
        $stmt = $pdo->prepare("SELECT * FROM MarcasVehiculos WHERE IdMarca = :id");
        $stmt->execute([':id' => $id]);
        $marca = $stmt->fetch();
 
        if (!$marca) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'mensaje' => 'Marca no encontrada.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
 
        $response->getBody()->write(json_encode([
            'success' => true,
            'data'    => $marca
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
 
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'mensaje' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
 
/**
 * método POST /marcas
 * Inserta una nueva Marca de Vehículo
 * ejemplo del JSON esperado:
 * {
 *   "IdMarca": "TOY",
 *   "DescripMarca": "Toyota",
 *   "PaisMarca": "Japón",
 *   "SitioWebOficial": "https://www.toyota.com.sv"
 * }
 */
$app->post('/marcas', function (Request $request, Response $response) {
    try {
        $body = $request->getParsedBody();
 
        // Validar campos obligatorios
        if (empty($body['IdMarca']) || empty($body['DescripMarca'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'mensaje' => 'IdMarca y DescripMarca son obligatorios.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
 
        $pdo  = getConexion();
        $stmt = $pdo->prepare("
            INSERT INTO MarcasVehiculos (IdMarca, DescripMarca, PaisMarca, SitioWebOficial)
            VALUES (:IdMarca, :DescripMarca, :PaisMarca, :SitioWebOficial)
        ");
 
        $stmt->execute([
            ':IdMarca'         => $body['IdMarca'],
            ':DescripMarca'    => $body['DescripMarca'],
            ':PaisMarca'       => $body['PaisMarca']       ?? null,
            ':SitioWebOficial' => $body['SitioWebOficial'] ?? null,
        ]);
 
        $response->getBody()->write(json_encode([
            'success' => true,
            'mensaje' => 'Marca registrada correctamente.',
            'IdMarca' => $body['IdMarca']
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
 
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'mensaje' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
 
$app->run();