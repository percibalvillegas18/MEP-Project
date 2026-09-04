<?php
namespace App\Core;
final class Router
{
    private array $routes=[];
    public function get(string $route,callable|array $handler,array $middleware=[]): void { $this->add('GET',$route,$handler,$middleware); }
    public function post(string $route,callable|array $handler,array $middleware=[]): void { $this->add('POST',$route,$handler,$middleware); }
    public function put(string $route,callable|array $handler,array $middleware=[]): void { $this->add('PUT',$route,$handler,$middleware); }
    public function delete(string $route,callable|array $handler,array $middleware=[]): void { $this->add('DELETE',$route,$handler,$middleware); }
    public function match(array $methods,string $route,callable|array $handler,array $middleware=[]): void { foreach($methods as $method)$this->add(strtoupper($method),$route,$handler,$middleware); }
    private function add(string $method,string $route,callable|array $handler,array $middleware): void { $this->routes[$method][trim($route,'/')]=compact('handler','middleware'); }
    public function dispatch(Request $request): void
    {
        [$definition,$params]=$this->resolve($request->method(),trim($request->route(),'/'));
        if(!$definition)Response::error(404,'Page not found.');
        $request->setRouteParams($params);
        if(is_array($definition['handler'])){
            $_SERVER['MEP_HANDLER']=$definition['handler'][0].'::'.$definition['handler'][1];
        }
        try{$destination=fn()=>$this->invoke($definition['handler'],$request);foreach(array_reverse($definition['middleware']) as $middleware){$next=$destination;$destination=fn()=>(new $middleware())->handle($request,$next);}$destination();}
        catch(\Throwable $error){ErrorHandler::render($error);}
    }
    private function resolve(string $method,string $route): array
    {
        $routes=$this->routes[$method]??[];
        if(isset($routes[$route]))return[$routes[$route],[]];
        foreach($routes as $pattern=>$definition){
            if(!str_contains($pattern,'{'))continue;
            $names=[];$offset=0;$regex='';
            preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/',$pattern,$tokens,PREG_OFFSET_CAPTURE);
            foreach($tokens[0] as $i=>$token){[$placeholder,$position]=$token;$regex.=preg_quote(substr($pattern,$offset,$position-$offset),'#').'([^/]+)';$names[]=$tokens[1][$i][0];$offset=$position+strlen($placeholder);}
            $regex.=preg_quote(substr($pattern,$offset),'#');
            if(!preg_match('#^'.$regex.'$#',$route,$matches))continue;
            array_shift($matches);$params=[];foreach($names as $i=>$name)$params[$name]=urldecode($matches[$i]??'');
            return[$definition,$params];
        }
        return[null,[]];
    }
    private function invoke(callable|array $handler,Request $request): mixed
    {
        if(is_array($handler)){
            [$class,$action]=$handler;
            $_SERVER['MEP_HANDLER']=$class.'::'.$action;
            return(new $class())->$action($request);
        }
        $_SERVER['MEP_HANDLER']='Closure/callable route';
        return $handler($request);
    }
}

