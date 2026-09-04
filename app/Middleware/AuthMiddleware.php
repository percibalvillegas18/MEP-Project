<?php
namespace App\Middleware;
use App\Core\Request;use App\Core\Response;
final class AuthMiddleware{public function handle(Request $request,callable $next): mixed{if(!\refresh_authenticated_user()){if($request->expectsJson())Response::json(['error'=>['code'=>'UNAUTHENTICATED','message'=>'Authentication is required.'],'request_id'=>\request_id()],401);Response::redirect('login');}return $next();}}

