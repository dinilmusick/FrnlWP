<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Store/SemanticStore.php';
require_once __DIR__ . '/../src/FrontendApi/FrontendApi.php';

use FrnlWP\Store\SemanticStore;
use FrnlWP\FrontendApi\FrontendApi;

function assertHierarchy(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function expectHierarchyError(callable $action, string $message): void {
    try { $action(); }
    catch (InvalidArgumentException|RuntimeException $error) { return; }
    throw new RuntimeException($message);
}

$store = new SemanticStore();
$store->registerEntity('root');
$store->registerEntity('child');
$store->registerEntity('leaf');
$store->parent('child', 'root');
$store->parent('leaf', 'child');
$api = new FrontendApi($store);

assertHierarchy($api->inspectComponent('leaf')['path'] === ['root', 'child', 'leaf'],
    'initial three-level hierarchy must be rendered');
expectHierarchyError(fn() => $store->parent('root', 'leaf'), 'transitive cycle must be rejected');
expectHierarchyError(fn() => $store->parent('root', 'root'), 'self cycle must be rejected');
expectHierarchyError(fn() => $store->parent('leaf', 'missing'), 'unknown parent must be rejected');
expectHierarchyError(fn() => $store->parent('missing', 'root'), 'unknown child must be rejected');
assertHierarchy($api->inspectComponent('leaf')['path'] === ['root', 'child', 'leaf'],
    'invalid hierarchy mutations must be transactional');

$store->unregisterEntity('root');
assertHierarchy($store->parent('child') === null, 'orphaned child must be detached');
assertHierarchy($api->inspectComponent('child')['parentId'] === null, 'detached child becomes root');
assertHierarchy($api->inspectComponent('leaf')['path'] === ['child', 'leaf'],
    'surviving descendants preserve a valid path');
$snapshot = $api->inspect();
assertHierarchy($snapshot['componentCount'] === 2, 'removed entity absent from snapshot');
assertHierarchy(count($snapshot['tree']) === 1 && $snapshot['tree'][0]['id'] === 'child',
    'tree contains only currently mounted roots');

$store->parent('leaf', null);
assertHierarchy($api->inspectComponent('leaf')['path'] === ['leaf'], 'explicit unparent works');
$store->registerEntity('root');
assertHierarchy($store->parent('child') === null, 'remount does not revive stale parent relationship');
$store->parent('child', 'root');
assertHierarchy($api->inspectComponent('child')['path'] === ['root', 'child'],
    'valid explicit reparenting after remount is supported');
echo "FrnlWP hierarchy integrity regressions: OK\n";
