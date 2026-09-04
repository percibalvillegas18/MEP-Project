<?php
namespace App\Middleware;
use App\Core\Request;use App\Core\Response;
final class CsrfMiddleware
{
    public function handle(Request $request,callable $next): mixed{if(in_array($request->method(),['POST','PUT','PATCH','DELETE'],true)){$token=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??$request->post('csrf_token',''));if(!isset($_SESSION['csrf_token'])||!hash_equals((string)$_SESSION['csrf_token'],$token)){if($request->expectsJson())Response::json(['error'=>['code'=>'CSRF_INVALID','message'=>'The security token is invalid or expired.'],'request_id'=>request_id()],419);Response::error(419,'Your security token expired. Refresh the page and try again.');}}return $next();}
}

