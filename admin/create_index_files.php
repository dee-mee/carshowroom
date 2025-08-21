<?php
$template = '<?php
require_once __DIR__ . "/../includes/template_start.php";

$page_title = ucfirst(basename(dirname($_SERVER["PHP_SELF"]))) . " Management";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4"><?php echo $page_title; ?></h1>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-tools me-2"></i>
                This section is currently under construction. Please check back later.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/template_end.php"; ?>';

$directories = [
    'brands', 'models', 'body-types', 'transmissions', 'fuel-types', 
    'colors', 'features', 'car-features', 'car-images', 'test-drives',
    'inquiries', 'reviews', 'dealers', 'users', 'roles', 'permissions',
    'pages', 'sliders', 'testimonials', 'subscribers', 'backup', 'logs', 'reports'
];

$baseDir = __DIR__ . '/';

foreach ($directories as $dir) {
    $filePath = $baseDir . $dir . '/index.php';
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $template);
        echo "Created: $filePath\n";
    } else {
        echo "Skipped (exists): $filePath\n";
    }
}

echo "\nDone creating index files.\n";
