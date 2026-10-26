<?php
namespace FrnlWP\Store;
final class SemanticStore {
    private array $entities=[];private array $definitions=[];private array $themes=[];private array $handlers=[];private array $events=[];
    public function registerEntity(string $id,string $state='idle',array $properties=[]):void{$this->entities[$id]=array_replace($this->entities[$id]??['state'=>$state,'properties'=>[]],['properties'=>array_replace($this->entities[$id]['properties']??[],$properties)]);}
    public function setState(string $id,string $state):void{$this->requireEntity($id);$previous=$this->entities[$id]['state'];$this->entities[$id]['state']=$state;$this->emit(['type'=>'entity.stateChanged','payload'=>['entityId'=>$id,'previousState'=>$previous,'state'=>$state]]);}
    public function setProperty(string $id,string $key,mixed $value):void{$this->requireEntity($id);$this->entities[$id]['properties'][$key]=$value;$this->emit(['type'=>'entity.propertyChanged','payload'=>['entityId'=>$id,'key'=>$key,'value'=>$value]]);}
    public function entity(string $id):array{return $this->entities[$id]??throw new \RuntimeException("Unknown component: $id");}
    public function registerDefinition(string $id,array $definition):void{$this->definitions[$id]=$definition;}
    public function registerTheme(string $name,array $theme):void{$this->themes[$name]=$theme;}
    public function applyTheme(string $id,string $theme):void{$this->requireEntity($id);if(!isset($this->themes[$theme]))throw new \RuntimeException("Unknown theme: $theme");$this->entities[$id]['theme']=$theme;}
    public function on(string $id,string $event,callable $handler):void{$this->handlers[$id][$event]=$handler;}
    public function invoke(string $id,string $event,mixed $payload=null):mixed{$handler=$this->handlers[$id][$event]??null;if(!$handler)throw new \RuntimeException("No handler for $event");return $handler($payload,$this);}
    public function events():array{return $this->events;}
    private function emit(array $event):void{$this->events[]=$event;}
    private function requireEntity(string $id):void{if(!isset($this->entities[$id]))throw new \RuntimeException("Unknown component: $id");}
}