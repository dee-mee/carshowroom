<?php
// List of required directories
$requiredDirs = [
    'brands', 'models', 'body-types', 'transmissions', 'fuel-types', 
    'colors', 'features', 'car-features', 'car-images', 'test-drives',
    'inquiries', 'reviews', 'dealers', 'users', 'roles', 'permissions',
    'pages', 'sliders', 'testimonials', 'subscribers', 'backup', 'logs', 'reports',
    'includes', 'car-specs', 'cars', 'plans', 'pricing', 'sellers', 'settings',
    'settings/email', 'settings/general', 'settings/home', 'settings/menu'
];

$baseDir = __DIR__ . '/';

foreach ($requiredDirs as $dir) {
    $dirPath = $baseDir . $dir;
    if (!file_exists($dirPath)) {
        if (mkdir($dirPath, 0755, true)) {
            echo "Created directory: $dirPath\n";
        } else {
            echo "Failed to create directory: $dirPath\n";
        }
    } else {
        echo "Directory exists: $dirPath\n";
    }
}

echo "\nDirectory structure verification complete.\n";
?>
