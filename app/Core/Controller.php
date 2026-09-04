<?php
namespace App\Core;
abstract class Controller {
    protected function view(string $name,array $data=[]): void { View::render($name,$data); }
    protected function redirect(string $route,array $query=[]): never { Response::redirect($route,$query); }
    protected function json(array $payload,int $status=200): never { Response::json($payload,$status); }
}
