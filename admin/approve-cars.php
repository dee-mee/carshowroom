<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /carshowroom/login.php");
    exit();
}

require_once '../config/database.php';

$success = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['car_id'])) {
    $car_id = (int)$_POST['car_id'];
    $action = $_POST['action'];
    
    // Validate action
    $allowed_actions = ['approve', 'reject', 'publish', 'unpublish'];
    if (!in_array($action, $allowed_actions)) {
        $error = 'Invalid action';
    } else {
        try {
            $status_map = [
                'approve' => 'approved',
                'reject' => 'draft',
                'publish' => 'published',
                'unpublish' => 'approved'
            ];
            
            $new_status = $status_map[$action];
            
            $stmt = $conn->prepare("UPDATE cars SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $car_id]);
            
            $success = "Car {$action}d successfully!";
            
            // Redirect to avoid form resubmission
            header("Location: approve-cars.php?success=" . urlencode($success));
            exit();
            
        } catch (PDOException $e) {
            $error = 'Error updating car status: ' . $e->getMessage();
        }
    }
}

// Get pending and approved cars
try {
    // Get pending cars
    $stmt = $conn->query("
        SELECT c.*, m.name as make_name, m2.name as model_name, u.user_name as owner_name 
        FROM cars c
        LEFT JOIN makes m ON c.make_id = m.id
        LEFT JOIN models m2 ON c.model_id = m2.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.status = 'pending'
        ORDER BY c.created_at DESC
    ");
    $pending_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get approved cars
    $stmt = $conn->query("
        SELECT c.*, m.name as make_name, m2.name as model_name, u.user_name as owner_name 
        FROM cars c
        LEFT JOIN makes m ON c.make_id = m.id
        LEFT JOIN models m2 ON c.model_id = m2.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.status IN ('approved', 'published')
        ORDER BY c.status, c.created_at DESC
        LIMIT 50
    ");
    $approved_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error fetching cars: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Cars - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .car-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .car-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-pending {
            border-left: 4px solid #ffc107;
        }
        .status-approved {
            border-left: 4px solid #198754;
        }
        .car-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Car Approvals</h1>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Pending Approvals -->
                <div class="mb-5">
                    <h3>Pending Approval</h3>
                    <?php if (empty($pending_cars)): ?>
                        <div class="alert alert-info">No cars pending approval.</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($pending_cars as $car): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card mb-4 status-pending">
                                        <?php if (!empty($car['featured_image'])): ?>
                                            <img src="/carshowroom/uploads/cars/<?php echo htmlspecialchars($car['featured_image']); ?>" class="card-img-top car-image" alt="<?php echo htmlspecialchars($car['title']); ?>">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="fas fa-car fa-4x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($car['title']); ?></h5>
                                            <p class="card-text">
                                                <strong>Make:</strong> <?php echo htmlspecialchars($car['make_name']); ?><br>
                                                <strong>Model:</strong> <?php echo htmlspecialchars($car['model_name']); ?><br>
                                                <strong>Year:</strong> <?php echo htmlspecialchars($car['year']); ?><br>
                                                <strong>Price:</strong> $<?php echo number_format($car['price']); ?><br>
                                                <strong>Posted by:</strong> <?php echo htmlspecialchars($car['owner_name']); ?>
                                            </p>
                                            <div class="d-flex justify-content-between">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                    <button type="submit" name="action" value="reject" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to reject this listing? It will be moved to drafts.');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                                <a href="/carshowroom/car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm" target="_blank">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Approved Cars -->
                <div class="mb-5">
                    <h3>Approved Listings</h3>
                    <?php if (empty($approved_cars)): ?>
                        <div class="alert alert-info">No approved cars found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Make</th>
                                        <th>Model</th>
                                        <th>Year</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_cars as $car): ?>
                                        <tr>
                                            <td><?php echo $car['id']; ?></td>
                                            <td>
                                                <a href="/carshowroom/car-details.php?id=<?php echo $car['id']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($car['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($car['make_name']); ?></td>
                                            <td><?php echo htmlspecialchars($car['model_name']); ?></td>
                                            <td><?php echo htmlspecialchars($car['year']); ?></td>
                                            <td>$<?php echo number_format($car['price']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $car['status'] === 'published' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($car['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($car['status'] === 'approved'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                            <button type="submit" name="action" value="publish" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i> Publish
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                            <button type="submit" name="action" value="unpublish" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-eye-slash"></i> Unpublish
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="/carshowroom/car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-info btn-sm" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
