<?php
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

<?php require_once __DIR__ . "/../includes/template_end.php"; ?>