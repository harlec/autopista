<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$next = '/index.php';
if (!empty($_GET['return'])) $next = $_GET['return'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $error = 'Token CSRF inválido.';
    } else {
        $res = authenticate_user($username, $password);
        if ($res['success']) {
            header('Location: ' . ($next ?: '/index.php'));
            exit;
        } else {
            $error = $res['message'] ?? 'Credenciales inválidas';
        }
    }
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Iniciar sesión</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white p-8 rounded shadow">
        <h2 class="text-2xl font-bold mb-4">Iniciar sesión</h2>
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <div class="mb-4">
                <label class="block text-sm mb-1">Usuario o email</label>
                <input name="username" class="w-full px-3 py-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm mb-1">Contraseña</label>
                <input type="password" name="password" class="w-full px-3 py-2 border rounded" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="btn-primary px-4 py-2">Entrar</button>
                <a href="/setup/create_admin.php" class="text-sm text-gray-500 hover:underline">Crear admin (si no hay usuarios)</a>
            </div>
        </form>
    </div>
</body>
</html>