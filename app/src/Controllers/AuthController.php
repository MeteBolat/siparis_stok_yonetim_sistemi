<?php

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($username === 'admin' && $password === '1234') {

                session_regenerate_id(true);

                $_SESSION['user_id'] = 1;
                $_SESSION['role'] = 'admin';

                header("Location: index.php?c=orders&a=index");
                exit;
            }

            echo "Hatalı giriş";
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
        session_destroy();
        header("Location: index.php?c=auth&a=login");
        exit;
    }
}
