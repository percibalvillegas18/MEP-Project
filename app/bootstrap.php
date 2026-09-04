<?php
spl_autoload_register(function(string $class): void {
    $prefix='App\\'; if (!str_starts_with($class,$prefix)) return;
    $relative=str_replace('\\','/',substr($class,strlen($prefix)));
    $file=__DIR__.'/'.$relative.'.php'; if (is_file($file)) require_once $file;
});
\App\Core\ErrorHandler::register();
require_once __DIR__ . '/../includes/auth.php';
