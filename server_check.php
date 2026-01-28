<?php
echo "PHP Version: " . phpversion() . "\n";
echo "Architecture: " . (PHP_INT_SIZE * 8) . "-bit\n";
echo "Extensions:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- " . $ext . "\n";
}
