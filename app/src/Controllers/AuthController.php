<?php

final class AuthController extends Controller
{
    public function login(): void
    {
        echo "Login sayfası";
    }

    public function logout(): void
    {
        session_destroy();
        header("Location: index.php?c=auth&a=login");
        exit;
    }
}
