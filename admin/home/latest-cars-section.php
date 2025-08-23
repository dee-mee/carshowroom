<?php
session_start();

// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database configuration
require_once '../../config/database.php';

// Create latest_cars table if it doesn't exist
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `latest_cars` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `image_url` varchar(500) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `views` varchar(50) NOT NULL,
            `time_posted` varchar(50) NOT NULL,
            `status` varchar(50) NOT NULL,
            `status_class` varchar(50) NOT NULL,
            `is_active` enum('yes','no') NOT NULL DEFAULT 'yes',
            `sort_order` int(11) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            // Get form data
            $title = $_POST['title'] ?? '';
            $image_url = $_POST['image_url'] ?? '';
            $price = $_POST['price'] ?? 0;
            $views = $_POST['views'] ?? '';
            $time_posted = $_POST['time_posted'] ?? '';
            $status = $_POST['status'] ?? '';
            $status_class = $_POST['status_class'] ?? '';
            $is_active = $_POST['is_active'] ?? 'yes';
            $sort_order = $_POST['sort_order'] ?? 0;
            $edit_id = $_POST['edit_id'] ?? null;
            
            // Basic validation
            if (empty($title) || empty($image_url) || empty($price)) {
                throw new Exception("Title, image URL and price are required");
            }
            
            if ($action === 'edit' && $edit_id) {
                // Update existing latest car
                $stmt = $conn->prepare("
                    UPDATE latest_cars 
                    SET title = ?, image_url = ?, price = ?, views = ?, time_posted = ?, 
                        status = ?, status_class = ?, is_active = ?, sort_order = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $title, $image_url, $price, $views, $time_posted, 
                    $status, $status_class, $is_active, $sort_order, $edit_id
                ]);
                
                $success_message = "Latest car updated successfully!";
            } else {
                // Insert new latest car
                $stmt = $conn->prepare("
                    INSERT INTO latest_cars (title, image_url, price, views, time_posted, status, status_class, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $title, $image_url, $price, $views, $time_posted, 
                    $status, $status_class, $is_active, $sort_order
                ]);
                
                $success_message = "Latest car added successfully!";
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM latest_cars WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success_message = "Latest car deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Error deleting latest car: " . $e->getMessage();
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    try {
        $stmt = $conn->prepare("SELECT is_active FROM latest_cars WHERE id = ?");
        $stmt->execute([$_GET['toggle_status']]);
        $car = $stmt->fetch();
        
        if ($car) {
            $new_status = ($car['is_active'] === 'yes') ? 'no' : 'yes';
            $stmt = $conn->prepare("UPDATE latest_cars SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $_GET['toggle_status']]);
            $success_message = "Latest car status updated successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get all latest cars
$stmt = $conn->query("
    SELECT * FROM latest_cars 
    ORDER BY sort_order ASC, created_at DESC
");
$latest_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->query("SELECT 
    COUNT(*) as total_cars,
    COUNT(CASE WHEN is_active = 'yes' THEN 1 END) as active_cars,
    COUNT(CASE WHEN is_active = 'no' THEN 1 END) as inactive_cars
    FROM latest_cars");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latest Cars Management - Car Showroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin-layout.css" rel="stylesheet">
    <style>
        /* Page-specific styles */
        .latest-cars-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .latest-cars-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: white;
        }

        .latest-cars-table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }

        .latest-cars-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #555;
        }

        .latest-cars-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .car-image {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-yes {
            background-color: #d4edda;
            color: #155724;
        }

        .status-no {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php $active_page = 'home'; ?>
    <?php require_once '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php require_once '../includes/navbar.php'; ?>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clock"></i>
                    Latest Cars Management
                </h1>
                <nav class="breadcrumb">
                    <a href="../dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">></span>
                    <span>Home Management</span>
                    <span class="breadcrumb-separator">></span>
                    <span>Latest Cars</span>
                </nav>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_cars']; ?></div>
                    <div class="stat-label">Total Latest Cars</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_cars']; ?></div>
                    <div class="stat-label">Active Cars</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['inactive_cars']; ?></div>
                    <div class="stat-label">Inactive Cars</div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Latest Car Form -->
            <div class="card">
                <div class="card-header">
                    <h5 id="formTitle"><i class="fas fa-plus me-2"></i>Add New Latest Car</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="latestCarForm">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="edit_id" id="editId" value="">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Car Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="image_url" class="form-label">Image URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="image_url" name="image_url" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="price" name="price" placeholder="e.g., $ 79,900" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="views" class="form-label">Views</label>
                                <input type="text" class="form-control" id="views" name="views" placeholder="e.g., 2,156">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="time_posted" class="form-label">Time Posted</label>
                                <input type="text" class="form-control" id="time_posted" name="time_posted" placeholder="e.g., 3 weeks ago">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="Used">Used</option>
                                    <option value="New">New</option>
                                    <option value="Certified">Certified</option>
                                    <option value="Featured">Featured</option>
                                    <option value="Good">Good</option>
                                    <option value="Rare">Rare</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status_class" class="form-label">Status Color Class</label>
                                <select class="form-select" id="status_class" name="status_class">
                                    <option value="bg-warning">Warning (Yellow)</option>
                                    <option value="bg-success">Success (Green)</option>
                                    <option value="bg-primary">Primary (Blue)</option>
                                    <option value="bg-danger">Danger (Red)</option>
                                    <option value="bg-info">Info (Light Blue)</option>
                                    <option value="bg-secondary">Secondary (Gray)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="is_active" class="form-label">Active Status</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="yes">Active</option>
                                    <option value="no">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                            </div>
                        </div>
                        
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">Reset</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i> Save Latest Car
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Latest Cars List -->
            <div class="latest-cars-container">
                <h5 class="mb-3"><i class="fas fa-list me-2"></i>Latest Cars List</h5>
                
                <?php if (empty($latest_cars)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-car fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No latest cars found. Add your first latest car using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="latest-cars-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Price</th>
                                    <th>Views</th>
                                    <th>Time Posted</th>
                                    <th>Status</th>
                                    <th>Active</th>
                                    <th>Sort Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_cars as $car): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
                                                 alt="Car" class="car-image" 
                                                 onerror="this.src='https://via.placeholder.com/60x40/6c5ce7/ffffff?text=Car'">
                                        </td>
                                        <td><?php echo htmlspecialchars($car['title']); ?></td>
                                        <td><?php echo htmlspecialchars($car['price']); ?></td>
                                        <td><?php echo htmlspecialchars($car['views']); ?></td>
                                        <td><?php echo htmlspecialchars($car['time_posted']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $car['status_class']; ?> rounded-pill">
                                                <?php echo htmlspecialchars($car['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $car['is_active']; ?>">
                                                <?php echo ucfirst($car['is_active']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $car['sort_order']; ?></td>
                                        <td>
                                            <button class="action-btn btn-primary" onclick="editCar(<?php echo htmlspecialchars(json_encode($car)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="?toggle_status=<?php echo $car['id']; ?>" 
                                               class="action-btn btn-warning"
                                               onclick="return confirm('Are you sure you want to change the status?')">
                                                <i class="fas fa-toggle-on"></i> Toggle
                                            </a>
                                            <a href="?delete=<?php echo $car['id']; ?>" 
                                               class="action-btn btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this latest car?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function editCar(car) {
            // Populate form with car data
            document.getElementById('formAction').value = 'edit';
            document.getElementById('editId').value = car.id;
            document.getElementById('title').value = car.title;
            document.getElementById('image_url').value = car.image_url;
            document.getElementById('price').value = car.price;
            document.getElementById('views').value = car.views;
            document.getElementById('time_posted').value = car.time_posted;
            document.getElementById('status').value = car.status;
            document.getElementById('status_class').value = car.status_class;
            document.getElementById('is_active').value = car.is_active;
            document.getElementById('sort_order').value = car.sort_order;
            
            // Update form title and button
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Latest Car';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i> Update Latest Car';
            
            // Scroll to form
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('latestCarForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('editId').value = '';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add New Latest Car';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i> Save Latest Car';
        }

    </script>
    <script src="../assets/js/admin-layout.js"></script>
</body>
</html>