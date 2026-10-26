<?php
namespace FrnlWP\Component;

final class Definition {
    public function __construct(
        public readonly string $id,
        public readonly array $states=[],
        public readonly array $visual=[],
        public readonly array $events=[],
    ) {}
    public function toArray(): array {
        return ['id'=>$this->id,'states'=>$this->states,'visual'=>$this->visual,'events'=>$this->events];
    }
}