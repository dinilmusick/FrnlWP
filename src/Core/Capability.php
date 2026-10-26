<?php
namespace FrnlWP\Core;
final class Capability {
    public function __construct(public readonly string $id,public readonly int $version=1,public readonly array $provides=[],public readonly array $requires=[]){}
}