<?php
namespace FrnlWP\Core;
final class Lifecycle {
    private const MAP=['created'=>['initializing','disposing'],'initializing'=>['mounted','failed','disposing'],'mounted'=>['active','suspended','disposing','failed'],'active'=>['suspended','disposing','failed'],'suspended'=>['resuming','disposing','failed'],'resuming'=>['active','failed','disposing'],'disposing'=>['disposed'],'disposed'=>[],'failed'=>['initializing','disposing','disposed']];
    public function __construct(private string $phase='created'){}
    public function phase():string{return $this->phase;}
    public function transition(string $to):void{if($to===$this->phase)return;if(!in_array($to,self::MAP[$this->phase]??[],true))throw new \RuntimeException("Invalid lifecycle transition");$this->phase=$to;}
}