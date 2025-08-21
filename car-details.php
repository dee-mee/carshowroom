<?php
require_once 'config/database.php';
include 'includes/header.php';

// In a real application, you would fetch car details from the database based on an ID
// For now, we'll use sample data
$car = [
    'id' => 1,
    'title' => 'Toyota Land Cruiser V8',
    'year' => 2023,
    'mileage' => '5,000',
    'transmission' => 'Automatic',
    'price' => '$85,000',
    'discount_price' => '$82,000',
    'condition' => 'New',
    'fuel_type' => 'Diesel',
    'engine' => '4.5L V8',
    'power' => '272 HP',
    'torque' => '650 Nm',
    'seats' => '7',
    'color' => 'Pearl White',
    'description' => 'The Toyota Land Cruiser V8 is a full-size luxury SUV that combines rugged off-road capability with premium comfort and advanced technology. This 2023 model comes with low mileage and is in showroom condition.',
    'features' => [
        'Leather Seats',
        'Sunroof',
        'Navigation System',
        'Bluetooth',
        'Backup Camera',
        'Third Row Seating',
        'Alloy Wheels',
        'Keyless Entry',
        'Heated Seats',
        'Premium Sound System',
        'Lane Departure Warning',
        'Adaptive Cruise Control',
        '360° Camera',
        'Wireless Charging',
        'Apple CarPlay/Android Auto'
    ]
];
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $car['title']; ?></li>
                    </ol>
                </nav>
                <h1 class="mt-3 mb-0"><?php echo $car['title']; ?></h1>
                <div class="d-flex align-items-center text-muted mt-2">
                    <span class="me-3"><i class="bi bi-calendar-check me-1"></i> <?php echo $car['year']; ?></span>
                    <span class="me-3"><i class="bi bi-speedometer2 me-1"></i> <?php echo $car['mileage']; ?> km</span>
                    <span><i class="bi bi-gear me-1"></i> <?php echo $car['transmission']; ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vehicle Details -->
<section class="vehicle-details py-5">
    <div class="container">
        <div class="row">
            <!-- Main Image -->
            <div class="col-lg-7">
                <div class="main-image mb-4">
                    <img src="https://via.placeholder.com/1200x800" alt="<?php echo $car['title']; ?>" class="img-fluid rounded-3 w-100">
                </div>
                
                <!-- Thumbnails -->
                <div class="thumbnails d-flex gap-2 mb-4">
                    <div class="thumbnail active">
                        <img src="https://via.placeholder.com/200x150" alt="Thumbnail 1" class="img-fluid rounded-2">
                    </div>
                    <div class="thumbnail">
                        <img src="https://via.placeholder.com/200x150" alt="Thumbnail 2" class="img-fluid rounded-2">
                    </div>
                    <div class="thumbnail">
                        <img src="https://via.placeholder.com/200x150" alt="Thumbnail 3" class="img-fluid rounded-2">
                    </div>
                    <div class="thumbnail">
                        <img src="https://via.placeholder.com/200x150" alt="Thumbnail 4" class="img-fluid rounded-2">
                    </div>
                </div>
                
                <!-- Vehicle Description -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Vehicle Overview</h5>
                        <p class="card-text"><?php echo $car['description']; ?></p>
                    </div>
                </div>
                
                <!-- Features & Specifications -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Features & Specifications</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Make: <strong>Toyota</strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Model: <strong>Land Cruiser V8</strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Year: <strong><?php echo $car['year']; ?></strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Condition: <span class="badge bg-success"><?php echo $car['condition']; ?></span></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Fuel Type: <strong><?php echo $car['fuel_type']; ?></strong></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Engine: <strong><?php echo $car['engine']; ?></strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Power: <strong><?php echo $car['power']; ?></strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Torque: <strong><?php echo $car['torque']; ?></strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Seats: <strong><?php echo $car['seats']; ?></strong></li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Color: <strong><?php echo $car['color']; ?></strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Features List -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Vehicle Features</h5>
                        <div class="row">
                            <?php 
                            $featuresPerColumn = ceil(count($car['features']) / 2);
                            $features = array_chunk($car['features'], $featuresPerColumn);
                            
                            foreach ($features as $column): 
                            ?>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <?php foreach ($column as $feature): ?>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> <?php echo $feature; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h4 class="card-title text-primary mb-4"><?php echo $car['price']; ?></h4>
                        <?php if (isset($car['discount_price'])): ?>
                        <p class="text-muted mb-3"><s><?php echo $car['price']; ?></s> <span class="badge bg-danger ms-2">Save $3,000</span></p>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-warning btn-lg mb-3"><i class="bi bi-telephone me-2"></i> Call Now</button>
                            <button class="btn btn-outline-primary btn-lg mb-3"><i class="bi bi-envelope me-2"></i> Send Message</button>
                            <button class="btn btn-outline-secondary btn-lg mb-3"><i class="bi bi-heart me-2"></i> Add to Favorites</button>
                            <button class="btn btn-outline-success btn-lg"><i class="bi bi-currency-dollar me-2"></i> Get Pre-Approved</button>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="dealer-info text-center">
                            <div class="dealer-logo mb-3">
                                <img src="https://via.placeholder.com/150x50" alt="Dealer Logo" class="img-fluid">
                            </div>
                            <h5>CAR LISTO Auto Group</h5>
                            <p class="text-muted mb-3">Your Trusted Car Dealer</p>
                            <div class="dealer-contacts">
                                <p class="mb-1"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Mombasa Road, Nairobi, Kenya</p>
                                <p class="mb-1"><i class="bi bi-telephone-fill text-primary me-2"></i> +254 712 345 678</p>
                                <p class="mb-0"><i class="bi bi-envelope-fill text-primary me-2"></i> info@carlisto.co.ke</p>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="safety-info text-center">
                            <h6 class="mb-3">Vehicle Safety & Protection</h6>
                            <div class="row g-3">
                                <div class="col-4">
                                    <div class="bg-light p-2 rounded-2">
                                        <i class="bi bi-shield-check display-6 text-primary mb-2"></i>
                                        <p class="small mb-0">Inspection Verified</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-light p-2 rounded-2">
                                        <i class="bi bi-key display-6 text-primary mb-2"></i>
                                        <p class="small mb-0">No Accident</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-light p-2 rounded-2">
                                        <i class="bi bi-file-earmark-text display-6 text-primary mb-2"></i>
                                        <p class="small mb-0">Full Service History</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar Vehicles -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Similar Vehicles</h3>
                <div class="row g-4">
                    <!-- Similar Vehicle 1 -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/600x400" class="card-img-top" alt="Toyota Prado">
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">New</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Toyota Prado VX</h5>
                                <p class="text-muted">2023 | 3,200 km | Automatic</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">$75,000</h5>
                                    <a href="car-details.php" class="btn btn-sm btn-outline-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Similar Vehicle 2 -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/600x400" class="card-img-top" alt="Lexus LX">
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">New</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Lexus LX 570</h5>
                                <p class="text-muted">2023 | 2,800 km | Automatic</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">$92,000</h5>
                                    <a href="car-details.php" class="btn btn-sm btn-outline-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Similar Vehicle 3 -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/600x400" class="card-img-top" alt="Nissan Patrol">
                                <span class="badge bg-info position-absolute top-0 end-0 m-2">Used</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Nissan Patrol V8</h5>
                                <p class="text-muted">2022 | 12,500 km | Automatic</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">$78,500</h5>
                                    <a href="car-details.php" class="btn btn-sm btn-outline-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Test Drive Modal -->
<div class="modal fade" id="testDriveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule a Test Drive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Preferred Date</label>
                        <input type="date" class="form-control" id="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="time" class="form-label">Preferred Time</label>
                        <input type="time" class="form-control" id="time" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="message" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Schedule Test Drive</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
