<?php
namespace FrnlWP\Event;

final class EventResolver {
    public function __construct(private array $handlers=[]) {}
    public function resolve(string $event,string $state): ?callable {
        return $this->handlers[$state.'.'.$event] ?? $this->handlers[$event] ?? null;
    }
}