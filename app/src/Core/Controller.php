<?php

class Controller
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
    protected function redirectWithFlash(string $type, string $message, string $url): void
    {
        Flash::set($type, $message);
        $this->redirect($url);
    }
}


