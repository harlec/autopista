<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'] ?? '', $secure, true);
    }
    session_start();
}

// Tiempo de expiración por inactividad (segundos)
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 1800); // 30 min

function create_user($username, $password, $email = null, $role = 'admin') {
    $database = new Database();
    $db = $database->getConnection();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hash);
    $stmt->bindParam(':role', $role);
    return $stmt->execute();
}

function authenticate_user($userOrEmail, $password) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM users WHERE username = :u OR email = :u LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':u', $userOrEmail);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return ['success' => false, 'message' => 'Usuario o contraseña inválidos'];
    if (!$user['is_active']) return ['success' => false, 'message' => 'Cuenta desactivada'];

    // Verificar bloqueo por intentos
    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        return ['success' => false, 'message' => 'Cuenta bloqueada temporalmente. Intente más tarde.'];
    }

    if (password_verify($password, $user['password'])) {
        // Éxito: resetear contadores y registrar login
        $uquery = "UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id";
        $ustmt = $db->prepare($uquery);
        $ustmt->bindParam(':id', $user['id']);
        $ustmt->execute();

        login_user_by_id($user['id']);
        return ['success' => true];
    }

    // Falló: incrementar intentos
    $fails = intval($user['failed_attempts']) + 1;
    $locked_until = null;
    if ($fails >= 5) {
        $locked_until = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutos
    }
    $uquery = "UPDATE users SET failed_attempts = :fails, locked_until = :locked WHERE id = :id";
    $ustmt = $db->prepare($uquery);
    $ustmt->bindParam(':fails', $fails);
    $ustmt->bindParam(':locked', $locked_until);
    $ustmt->bindParam(':id', $user['id']);
    $ustmt->execute();

    return ['success' => false, 'message' => 'Usuario o contraseña inválidos'];
}

function login_user_by_id($id) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $id;
    $_SESSION['last_activity'] = time();
}

function logout_user() {
    // Limpiar sesión
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function current_user() {
    if (empty($_SESSION['user_id'])) return null;
    static $cached = null;
    if ($cached !== null) return $cached;

    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT id, username, email, role, is_active, last_login FROM users WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    $cached = $stmt->fetch(PDO::FETCH_ASSOC);
    return $cached ?: null;
}

function is_admin() {
    $u = current_user();
    return $u && ($u['role'] === 'admin');
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        $return = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?return=' . $return);
        exit;
    }

    // Inactividad
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logout_user();
        header('Location: /login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// CSRF helpers (uso básico)
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>