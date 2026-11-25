<?php

class Controller
{
    protected function view(string $view, array $data = [], string $layout = null): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = APP_PATH . '/views/' . trim($view, '/') . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException('View tidak ditemukan: ' . e($view));
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            require APP_PATH . '/views/layouts/' . $layout . '.php';
            return;
        }

        echo $content;
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }
}
