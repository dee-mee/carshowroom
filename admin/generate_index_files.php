<?php
// Define the template for index files
$template = '<?php
require_once __DIR__ . "/../includes/template_start.php";

$page_title = ucwords(str_replace("-", " ", basename(dirname(__FILE__))));
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

// List of directories to create index files in
$directories = [
    'brands', 'models', 'body-types', 'transmissions', 'fuel-types', 
    'colors', 'features', 'car-features', 'car-images', 'test-drives',
    'inquiries', 'reviews', 'dealers', 'users', 'roles', 'permissions',
    'pages', 'sliders', 'testimonials', 'subscribers', 'backup', 'logs', 'reports'
];

// Base directory
$baseDir = __DIR__ . '/';

// Create index.php in each directory
foreach ($directories as $dir) {
    $dirPath = $baseDir . $dir;
    $filePath = $dirPath . '/index.php';
    
    // Create directory if it doesn't exist
    if (!file_exists($dirPath)) {
        mkdir($dirPath, 0755, true);
        echo "Created directory: $dirPath\n";
    }
    
    // Create index.php if it doesn't exist
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $template);
        echo "Created: $filePath\n";
    } else {
        echo "Skipped (exists): $filePath\n";
    }
}

echo "\nDone generating index files.\n";
?>
