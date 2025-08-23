<?php
// Debug function
function debug_log($message) {
    $log_file = '/tmp/upload_test.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

debug_log('=== Starting test upload ===');
debug_log('POST data: ' . print_r($_POST, true));
debug_log('FILES data: ' . print_r($_FILES, true));

echo "<h1>Test File Upload</h1>";
echo "<form method='post' enctype='multipart/form-data'>";
echo "<input type='file' name='test_file' required>";
echo "<button type='submit'>Upload Test File</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['test_file'])) {
        $file = $_FILES['test_file'];
        $upload_dir = '/opt/lampp/htdocs/carshowroom/admin/uploads/test/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $target_file = $upload_dir . basename($file['name']);
        
        debug_log('Attempting to move uploaded file to: ' . $target_file);
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            echo "<p style='color:green;'>File uploaded successfully to: " . htmlspecialchars($target_file) . "</p>";
            debug_log('File uploaded successfully');
            
            // Test file permissions
            $test_file = $upload_dir . 'test_permission.txt';
            if (file_put_contents($test_file, 'test') !== false) {
                echo "<p style='color:green;'>✓ Can write to upload directory</p>";
                unlink($test_file);
            } else {
                echo "<p style='color:red;'>✗ Cannot write to upload directory</p>";
            }
            
        } else {
            $error = error_get_last();
            echo "<p style='color:red;'>Upload failed. Error: " . ($error['message'] ?? 'Unknown error') . "</p>";
            debug_log('Upload failed: ' . print_r($error, true));
            debug_log('PHP upload errors: ' . print_r([
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
                'upload_tmp_dir' => ini_get('upload_tmp_dir'),
                'file_uploads' => ini_get('file_uploads')
            ], true));
        }
    }
}
?>
