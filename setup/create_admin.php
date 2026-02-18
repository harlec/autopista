<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Si ya existen usuarios, no permitir usar este script
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users");
$stmt->execute();
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if ($c && intval($c['cnt']) > 0) {
    echo "Usuarios ya existen. El script de creación no se puede usar.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Usuario y contraseña son requeridos.';
    } else {
        if (create_user($username, $password, $email, 'admin')) {
            echo "Usuario administrador creado correctamente. <a href=\"/login.php\">Ir a login</a>";
        } else {
            $error = 'Error al crear el usuario.';
        }
        exit;
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Crear usuario administrador</title>
</head>
<body>
    <h2>Crear administrador</h2>
    <?php if (!empty($error)): ?><p style="color:red"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post">
        <label>Usuario: <input name="username"></label><br>
        <label>Email: <input name="email"></label><br>
        <label>Contraseña: <input name="password" type="password"></label><br>
        <button type="submit">Crear</button>
    </form>
</body>
</html>