<?php
/**
 * Quick PHP extensions check
 * DELETE THIS FILE after checking!
 */

header('Content-Type: text/plain');

echo "PHP Version: " . PHP_VERSION . "\n\n";

echo "=== Image Processing Extensions ===\n";
echo "GD: " . (extension_loaded('gd') ? '✓ INSTALLED' : '✗ NOT FOUND') . "\n";
echo "Imagick: " . (extension_loaded('imagick') ? '✓ INSTALLED' : '✗ NOT FOUND') . "\n\n";

if (extension_loaded('gd')) {
    echo "GD Info:\n";
    $gdInfo = gd_info();
    foreach ($gdInfo as $key => $value) {
        echo "  $key: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "\n";
    }
    echo "\n";
}

if (extension_loaded('imagick')) {
    echo "Imagick Info:\n";
    $imagick = new Imagick();
    echo "  Version: " . $imagick->getVersion()['versionString'] . "\n";
    echo "  Formats: " . count($imagick->queryFormats()) . " supported\n";
    echo "  SVG Support: " . (in_array('SVG', $imagick->queryFormats()) ? 'Yes' : 'No') . "\n";
    echo "\n";
}

echo "=== Other Useful Extensions ===\n";
echo "cURL: " . (extension_loaded('curl') ? '✓' : '✗') . "\n";
echo "mbstring: " . (extension_loaded('mbstring') ? '✓' : '✗') . "\n";
echo "PDO: " . (extension_loaded('pdo') ? '✓' : '✗') . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? '✓' : '✗') . "\n";

echo "\n=== All Loaded Extensions ===\n";
echo implode(', ', get_loaded_extensions());
