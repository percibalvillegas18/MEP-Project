<?php
namespace App\Core;
final class Request
{
    private array $routeParams=[];
    private ?array $jsonBody=null;
    public function method(): string{return strtoupper($_SERVER['REQUEST_METHOD']??'GET');}
    public function route(): string{return(string)($_GET['route']??'dashboard');}
    public function input(string $key,mixed $default=null): mixed{return $this->body()[$key]??$_POST[$key]??$_GET[$key]??$default;}
    public function query(string $key,mixed $default=null): mixed{return $_GET[$key]??$default;}
    public function post(string $key,mixed $default=null): mixed{return $this->body()[$key]??$_POST[$key]??$default;}
    public function all(): array{return in_array($this->method(),['POST','PUT','PATCH','DELETE'],true)?$this->body():$_GET;}
    public function body(): array {
        if($this->jsonBody!==null)return $this->jsonBody;
        $contentType=strtolower((string)($_SERVER['CONTENT_TYPE']??''));
        if(str_contains($contentType,'application/json')){
            $raw=(string)file_get_contents('php://input');
            $decoded=$raw===''?[]:json_decode($raw,true);
            if(!is_array($decoded))throw new \InvalidArgumentException('Request body must contain a JSON object.');
            return $this->jsonBody=$decoded;
        }
        return $this->jsonBody=$_POST;
    }
    public function setRouteParams(array $params): void{$this->routeParams=$params;}
    public function param(string $key,mixed $default=null): mixed{return $this->routeParams[$key]??$default;}
    public function expectsJson(): bool{return str_starts_with($this->route(),'api/')||str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');}
}

