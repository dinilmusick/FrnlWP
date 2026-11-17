<?php

declare(strict_types=1);

$enqueued = [];
function wp_enqueue_script(string $handle, string $url, array $deps = [], string $version = '', bool $inFooter = false): void {
    global $enqueued;
    $enqueued[] = compact('handle', 'url', 'deps', 'version', 'inFooter');
}
require_once __DIR__ . '/../src/WordPress/Assets.php';

\FrnlWP\WordPress\Assets::enqueueSurface('https://example.test/wp-content/plugins/frnlwp/');
if (count($enqueued) !== 1) throw new RuntimeException('Expected one UI surface asset');
$script = $enqueued[0];
if ($script['handle'] !== 'frnlwp-ui-surface'
    || $script['url'] !== 'https://example.test/wp-content/plugins/frnlwp/assets/ui-surface.js'
    || $script['version'] !== '0.5.3'
    || !$script['inFooter']) {
    throw new RuntimeException('Incorrect shipped UI surface asset path or parameters');
}
echo "FrnlWP UI surface WordPress asset test: OK\n";
