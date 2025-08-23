<?php
// Test file upload handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

$upload_dir = __DIR__ . '/uploads/header-banner/';
$web_path = '/carshowroom/uploads/header-banner/';

// Create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['test_upload'])) {
    $file = $_FILES['test_upload'];
    $filename = 'test_' . time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        chmod($target_path, 0664);
        echo json_encode([
            'success' => true,
            'web_path' => $web_path . $filename,
            'file_info' => [
                'name' => $filename,
                'size' => $file['size'],
                'type' => $file['type']
            ]
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to move uploaded file',
            'file_error' => $file['error'],
            'upload_errors' => [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ]
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .preview { max-width: 100%; margin-top: 20px; display: none; }
    </style>
</head>
<body>
    <h1>File Upload Test</h1>
    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="test_upload" id="test_upload" required>
        <button type="submit">Upload Test File</button>
    </form>
    <div id="result"></div>
    <img id="preview" class="preview" alt="Preview">

    <script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        const fileInput = document.getElementById('test_upload');
        
        if (fileInput.files.length === 0) {
            alert('Please select a file first');
            return;
        }
        
        formData.append('test_upload', fileInput.files[0]);
        
        fetch('test_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const result = document.getElementById('result');
            if (data.success) {
                result.innerHTML = `
                    <h3>Upload Successful!</h3>
                    <p>File: ${data.file_info.name}</p>
                    <p>Size: ${(data.file_info.size / 1024).toFixed(2)} KB</p>
                    <p>Type: ${data.file_info.type}</p>
                    <p>Web Path: <a href="${data.web_path}" target="_blank">${data.web_path}</a></p>
                `;
                
                // Show image preview if it's an image
                if (data.file_info.type.startsWith('image/')) {
                    const preview = document.getElementById('preview');
                    preview.src = data.web_path;
                    preview.style.display = 'block';
                }
            } else {
                result.innerHTML = `
                    <h3>Upload Failed</h3>
                    <p>Error: ${data.error || 'Unknown error'}</p>
                    ${data.file_error ? `<p>File Error: ${data.upload_errors[data.file_error] || 'Unknown error code: ' + data.file_error}</p>` : ''}
                `;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `
                <h3>Error</h3>
                <p>${error.message}</p>
            `;
        });
    });
    </script>
</body>
</html>
