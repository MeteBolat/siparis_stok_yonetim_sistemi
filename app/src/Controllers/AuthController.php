<?php

final class AuthController extends Controller
{
    public function login(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = new UserModel($this->pdo);
        $user = $userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php?c=dashboard&a=index");
            exit;
        }

        echo "Hatalı kullanıcı adı veya şifre";
        return;
    }

    echo '
    <form method="POST">
        <input type="text" name="username" placeholder="Kullanıcı adı">
        <input type="password" name="password" placeholder="Şifre">
        <button type="submit">Giriş</button>
    </form>
    ';
}


    public function logout(): void
    {
        $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

        session_destroy();
        header("Location: index.php?c=auth&a=login");
        exit;
    }
}
