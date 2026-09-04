<?php
namespace App\Core;
final class Response
{
    public static function redirect(string $route,array $query=[]): never{header('Location: index.php?'.http_build_query(array_merge(['route'=>$route],$query)));exit;}
    public static function json(array $payload,int $status=200): never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
    public static function error(int $status,string $message): never{http_response_code($status);View::render('errors/http',compact('status','message'));exit;}
}
