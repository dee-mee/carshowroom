<?php
$page_title = 'Dashboard';
require_once 'includes/template_start.php';

// Get dashboard statistics
$stats = [];

// Total Cars
$stmt = $pdo->query("SELECT COUNT(*) as total FROM cars");
$stats['total_cars'] = $stmt->fetch()['total'];

// Total Sellers
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_sellers'] = $stmt->fetch()['total'];

// Total Categories
$stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
$stats['total_categories'] = $stmt->fetch()['total'];

// Total Brands
$stmt = $pdo->query("SELECT COUNT(*) as total FROM brands");
$stats['total_brands'] = $stmt->fetch()['total'];
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">Dashboard Overview</h1>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card border-0 bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-white-50 mb-1">Total Cars</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_cars']); ?></h2>
                    </div>
                    <div class="icon-shape bg-white-10 rounded-circle p-3">
                        <i class="fas fa-car fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card border-0 bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-white-50 mb-1">Total Sellers</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_sellers']); ?></h2>
                    </div>
                    <div class="icon-shape bg-white-10 rounded-circle p-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card border-0 bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-white-50 mb-1">Categories</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_categories']); ?></h2>
                    </div>
                    <div class="icon-shape bg-white-10 rounded-circle p-3">
                        <i class="fas fa-tags fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card border-0 bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-white-50 mb-1">Brands</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_brands']); ?></h2>
                    </div>
                    <div class="icon-shape bg-white-10 rounded-circle p-3">
                        <i class="fas fa-copyright fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Cars -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Recent Cars</h6>
        <a href="cars/index.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT c.*, b.name as brand_name 
                                       FROM cars c 
                                       LEFT JOIN brands b ON c.brand_id = b.id 
                                       ORDER BY c.created_at DESC 
                                       LIMIT 5");
                    $recent_cars = $stmt->fetchAll();
                    
                    if (count($recent_cars) > 0):
                        foreach ($recent_cars as $car):
                    ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($car['image_url'] ?? '../../assets/images/placeholder.jpg'); ?>" 
                                 alt="Car Image" 
                                 style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td><?php echo htmlspecialchars($car['title']); ?></td>
                        <td><?php echo htmlspecialchars($car['brand_name']); ?></td>
                        <td>$<?php echo number_format($car['price']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $car['status'] === 'active' ? 'success' : 
                                    ($car['status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($car['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="cars/edit.php?id=<?php echo $car['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="cars/delete.php?id=<?php echo $car['id']; ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this car?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No cars found. <a href="cars/add.php">Add a new car</a>.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Sellers -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Recent Sellers</h6>
        <a href="sellers/index.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
                    $recent_sellers = $stmt->fetchAll();
                    
                    if (count($recent_sellers) > 0):
                        foreach ($recent_sellers as $seller):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($seller['name']); ?></td>
                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                        <td><?php echo htmlspecialchars($seller['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($seller['created_at'])); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $seller['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($seller['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">No sellers found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/template_end.php'; ?>
