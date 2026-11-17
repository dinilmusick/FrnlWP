<?php

declare(strict_types=1);

namespace FrnlWP\WordPress;

final class Assets {
    public static function enqueue(string $baseUrl,string $version='0.2.0'): void {
        wp_enqueue_script('frnlwp-runtime',rtrim($baseUrl, '/').'/dist/runtime.global.js',[], $version,true);
    }

    /** Ship a browser-native UI surface without needing an npm build in WordPress. */
    public static function enqueueSurface(string $baseUrl, string $version='0.5.3'): void {
        wp_enqueue_script('frnlwp-ui-surface',rtrim($baseUrl, '/').'/assets/ui-surface.js',[], $version,true);
    }
}