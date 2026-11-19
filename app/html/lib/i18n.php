<?php
declare(strict_types=1);

/**
 * Returns the localized string for $key, or the key itself if it is missing.
 */
function t(string $key): string
{
    static $strings = null;
    if ($strings === null) {
        $strings = require __DIR__ . '/../lang/es_CR.php';
    }
    return $strings[$key] ?? $key;
}
