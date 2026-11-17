<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Store/SemanticStore.php';
require_once __DIR__ . '/../src/FrontendApi/FrontendApi.php';

use FrnlWP\Store\SemanticStore;
use FrnlWP\FrontendApi\FrontendApi;

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function rejects(callable $action, string $class, string $message): void {
    try { $action(); }
    catch (Throwable $error) {
        check($error instanceof $class, "$message: wrong exception");
        return;
    }
    throw new RuntimeException("$message: call should fail");
}
$store = new SemanticStore();
$store->registerEntity('button', 'idle');
$store->registerEntity('button.details', 'idle');
$api = new FrontendApi($store, ['applicationId' => 'parity-test']);
$called = 0;
$off = $store->on('button', 'click', function($payload, array $context) use (&$called): int {
    $called++;
    check($context['state'] === 'idle', 'handler sees current state');
    return $payload['value'] * 2;
});
$store->on('button.details', 'click', fn() => 'child-clicked');
$store->on('button', 'setState', fn() => 'unreachable reserved event');
$button = $api->inspectComponent('button');
check($button['events'] === ['click'], 'only callable registered events advertised');
check(($button['controls']['click']['kind'] ?? null) === 'event', 'registered event is a control');
check($api->invoke('button', 'click', ['value' => 4]) === 8 && $called === 1, 'frontend uses real event handler');
check($api->invoke('button.details', 'click') === 'child-clicked', 'dotted component IDs are isolated');
check($button['controls']['applyTheme']['available'] === false, 'theme unavailable before registration');
rejects(fn() => $api->invoke('button', 'applyTheme', 'dark'), RuntimeException::class, 'unavailable control blocked');
rejects(fn() => $api->invoke('button', 'missing-event'), InvalidArgumentException::class, 'unknown event blocked');
$api->invoke('button', 'setState', 'loading');
check($store->getEntity('button')['state'] === 'loading', 'built-in state control preserved');
$store->registerTheme('dark', []);
check($api->invoke('button', 'applyTheme', 'dark') === true, 'registered theme usable');

$off();
check($api->inspectComponent('button')['events'] === [], 'unsubscribed events disappear from API');
$previous = $store->on('button', 'hover', fn() => 'old');
$store->unregisterEntity('button');
rejects(fn() => $store->invoke('button', 'hover'), RuntimeException::class, 'removed component fails closed');
$store->registerEntity('button');
$store->on('button', 'hover', fn() => 'new');
$previous();
check($api->invoke('button', 'hover') === 'new', 'stale disposer cannot remove new listener');
check($api->invoke('button.details', 'click') === 'child-clicked', 'parent removal does not affect dotted child');
echo "FrnlWP frontend event parity tests: OK\n";
