<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\AuthMiddleware;

return function ($app) {
    // === HOME PAGE ROUTE (Fixes 404 at root '/') ===
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('<h1>✅ EduTrack Backend is Running!</h1>');
        return $response;
    });

    // === TEST ROUTE ===
    $app->get('/api/test', function (Request $request, Response $response) {
        $response->getBody()->write(json_encode(["message" => "Test successful ✅"]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // === Register Route ===
    $app->post('/api/register', function (Request $request, Response $response) {
        $db = new PDO(
            "mysql:host=" . $_ENV["DB_HOST"] . ";dbname=" . $_ENV["DB_NAME"],
            $_ENV["DB_USER"],
            $_ENV["DB_PASS"]
        );

        $data = json_decode($request->getBody(), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'student';

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $role]);

        $response->getBody()->write(json_encode(['message' => 'User registered successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // === Login Route ===
    $app->post('/api/login', function (Request $request, Response $response) {
        $db = new PDO(
            "mysql:host=" . $_ENV["DB_HOST"] . ";dbname=" . $_ENV["DB_NAME"],
            $_ENV["DB_USER"],
            $_ENV["DB_PASS"]
        );

        $data = json_decode($request->getBody(), true);
        $email = $data['email'];
        $password = $data['password'];

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // DEBUGGING PURPOSE — print info
        $debug = [
            "input_email" => $email,
            "input_pass" => $password,
            "user_email" => $user['email'] ?? null,
            "user_hash" => $user['password'] ?? null,
            "match" => $user && password_verify($password, $user['password']) ? "✅ MATCH" : "❌ NO MATCH"
        ];

        if (!$user || !password_verify($password, $user['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials', 'debug' => $debug]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $payload = [
            "id" => $user['id'],
            "email" => $user['email'],
            "role" => $user['role'],
            "iat" => time(),
            "exp" => time() + (60 * 60)
        ];

        $jwt = JWT::encode($payload, $_ENV["JWT_SECRET"], 'HS256');
        $response->getBody()->write(json_encode(['token' => $jwt]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // === Protected Profile Route ===
    $app->get('/api/profile', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode(['profile' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add(new AuthMiddleware());

    // === TEMP: PASSWORD CHECK ROUTE ===
    $app->get('/api/test-pass', function (Request $request, Response $response) {
        $input_pass = 'password123';
        $stored_hash = '$2y$10$TKh8H1.PFhLzMZ6zS1Nnae5UQAFo8aN9L/nJ.1AzS3ULf8y0sHdae';

        $match = password_verify($input_pass, $stored_hash);
        $response->getBody()->write(json_encode([
            'input' => $input_pass,
            'match' => $match ? '✅ MATCH' : '❌ NO MATCH'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
