<?php
namespace App\Core;
final class View
{
    public static function render(string $view,array $data=[]): void{$file=dirname(__DIR__).'/Views/'.trim($view,'/').'.php';if(!is_file($file))throw new \RuntimeException("View not found: {$view}");extract($data,EXTR_SKIP);require $file;}
}
