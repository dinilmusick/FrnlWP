<?php
namespace FrnlWP\Routing;
final class Router {
    public function __construct(private array $routes=[]){}
    public function add(string $id,string $pattern):void{$this->routes[$id]=$pattern;}
    public function match(string $path):?array{foreach($this->routes as $id=>$pattern){$regex=preg_replace('/\{([a-zA-Z0-9_]+)\}/','(?P<$1>[^/]+)',$pattern);if(preg_match('#^'.rtrim($regex,'/').'/?$#',$path,$m)){return ['id'=>$id,'params'=>array_filter($m,'is_string',ARRAY_FILTER_USE_KEY),'path'=>$path];}}return null;}
    public function url(string $id,array $params=[]):string{$pattern=$this->routes[$id]??throw new \RuntimeException("Unknown route");return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/',fn($m)=>urlencode((string)($params[$m[1]]??throw new \InvalidArgumentException("Missing parameter"))),$pattern);}
}