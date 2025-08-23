<?php
require_once 'config/database.php';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="fw-bold">Our Vehicle Inventory</h1>
                <nav aria-label="breadcrumb" class="d-flex justify-content-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Inventory</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- Search Filters -->
<section class="filter-section py-4 bg-white">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-3">
                <select class="form-select">
                    <option selected>All Makes</option>
                    <option>Toyota</option>
                    <option>Nissan</option>
                    <option>Mitsubishi</option>
                    <option>Subaru</option>
                    <option>Mazda</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option selected>All Models</option>
                    <option>Land Cruiser</option>
                    <option>Prado</option>
                    <option>Rav4</option>
                    <option>X-Trail</option>
                    <option>Pajero</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select">
                    <option selected>Year</option>
                    <option>2023</option>
                    <option>2022</option>
                    <option>2021</option>
                    <option>2020</option>
                    <option>2019</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select">
                    <option selected>Price Range</option>
                    <option>Under $10,000</option>
                    <option>$10,000 - $20,000</option>
                    <option>$20,000 - $30,000</option>
                    <option>Over $30,000</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-warning w-100">Search</button>
            </div>
        </div>
    </div>
</section>

<!-- Vehicle Listings -->
<section class="vehicle-listings py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <p class="mb-0">Showing <strong>1-12</strong> of <strong>48</strong> vehicles</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-inline-block me-3">
                    <span class="me-2">Sort by:</span>
                    <select class="form-select form-select-sm d-inline-block w-auto">
                        <option>Newest First</option>
                        <option>Price: Low to High</option>
                        <option>Price: High to Low</option>
                        <option>Mileage: Low to High</option>
                    </select>
                </div>
                <div class="d-inline-block">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active"><i class="bi bi-grid"></i></button>
                        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Vehicle Card 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="card vehicle-card h-100">
                    <div class="position-relative">
                        <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Toyota Land Cruiser">
                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">Featured</span>
                        <div class="vehicle-actions position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between">
                            <a href="#" class="text-white"><i class="bi bi-heart"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrows-angle-expand"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrow-repeat"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">Toyota Land Cruiser V8</h5>
                            <span class="badge bg-success">New</span>
                        </div>
                        <p class="text-muted mb-3">2023 | 5,000 km | Automatic</p>
                        <div class="vehicle-specs d-flex justify-content-between mb-3">
                            <span><i class="bi bi-speedometer2 me-1"></i> 8.5L/100km</span>
                            <span><i class="bi bi-people me-1"></i> 7 Seats</span>
                            <span><i class="bi bi-fuel-pump me-1"></i> Diesel</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">$85,000</h4>
                            <a href="car-details.php" class="btn btn-sm btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Card 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="card vehicle-card h-100">
                    <div class="position-relative">
                        <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Nissan X-Trail">
                        <div class="vehicle-actions position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between">
                            <a href="#" class="text-white"><i class="bi bi-heart"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrows-angle-expand"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrow-repeat"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">Nissan X-Trail</h5>
                            <span class="badge bg-info">Used</span>
                        </div>
                        <p class="text-muted mb-3">2021 | 35,000 km | Automatic</p>
                        <div class="vehicle-specs d-flex justify-content-between mb-3">
                            <span><i class="bi bi-speedometer2 me-1"></i> 7.2L/100km</span>
                            <span><i class="bi bi-people me-1"></i> 5 Seats</span>
                            <span><i class="bi bi-fuel-pump me-1"></i> Petrol</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">$32,500</h4>
                            <a href="car-details.php" class="btn btn-sm btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Card 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="card vehicle-card h-100">
                    <div class="position-relative">
                        <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Mitsubishi Pajero">
                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">Hot Deal</span>
                        <div class="vehicle-actions position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between">
                            <a href="#" class="text-white"><i class="bi bi-heart"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrows-angle-expand"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrow-repeat"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">Mitsubishi Pajero Sport</h5>
                            <span class="badge bg-info">Used</span>
                        </div>
                        <p class="text-muted mb-3">2020 | 45,000 km | Automatic</p>
                        <div class="vehicle-specs d-flex justify-content-between mb-3">
                            <span><i class="bi bi-speedometer2 me-1"></i> 8.0L/100km</span>
                            <span><i class="bi bi-people me-1"></i> 7 Seats</span>
                            <span><i class="bi bi-fuel-pump me-1"></i> Diesel</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">$38,900</h4>
                            <a href="car-details.php" class="btn btn-sm btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Card 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="card vehicle-card h-100">
                    <div class="position-relative">
                        <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Subaru Forester">
                        <div class="vehicle-actions position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between">
                            <a href="#" class="text-white"><i class="bi bi-heart"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrows-angle-expand"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrow-repeat"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">Subaru Forester</h5>
                            <span class="badge bg-success">New</span>
                        </div>
                        <p class="text-muted mb-3">2023 | 2,500 km | Automatic</p>
                        <div class="vehicle-specs d-flex justify-content-between mb-3">
                            <span><i class="bi bi-speedometer2 me-1"></i> 7.5L/100km</span>
                            <span><i class="bi bi-people me-1"></i> 5 Seats</span>
                            <span><i class="bi bi-fuel-pump me-1"></i> Petrol</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">$42,300</h4>
                            <a href="car-details.php" class="btn btn-sm btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Card 5 -->
            <div class="col-lg-4 col-md-6">
                <div class="card vehicle-card h-100">
                    <div class="position-relative">
                        <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Mazda CX-5">
                        <div class="vehicle-actions position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between">
                            <a href="#" class="text-white"><i class="bi bi-heart"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrows-angle-expand"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrow-repeat"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">Mazda CX-5</h5>
                            <span class="badge bg-info">Used</span>
                        </div>
                        <p class="text-muted mb-3">2022 | 18,000 km | Automatic</p>
                        <div class="vehicle-specs d-flex justify-content-between mb-3">
                            <span><i class="bi bi-speedometer2 me-1"></i> 6.8L/100km</span>
                            <span><i class="bi bi-people me-1"></i> 5 Seats</span>
                            <span><i class="bi bi-fuel-pump me-1"></i> Petrol</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">$35,700</h4>
                            <a href="car-details.php" class="btn btn-sm btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Card 6 -->
            <div class="col-lg-4 col-md-6">
                <div class="card vehicle-card h-100">
                    <div class="position-relative">
                        <img src="https://via.placeholder.com/800x600" class="card-img-top" alt="Toyota RAV4">
                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">Featured</span>
                        <div class="vehicle-actions position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-between">
                            <a href="#" class="text-white"><i class="bi bi-heart"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrows-angle-expand"></i></a>
                            <a href="#" class="text-white"><i class="bi bi-arrow-repeat"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">Toyota RAV4 Hybrid</h5>
                            <span class="badge bg-success">New</span>
                        </div>
                        <p class="text-muted mb-3">2023 | 3,200 km | Automatic</p>
                        <div class="vehicle-specs d-flex justify-content-between mb-3">
                            <span><i class="bi bi-speedometer2 me-1"></i> 4.7L/100km</span>
                            <span><i class="bi bi-people me-1"></i> 5 Seats</span>
                            <span><i class="bi bi-lightning-charge me-1"></i> Hybrid</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">$48,500</h4>
                            <a href="car-details.php" class="btn btn-sm btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="mb-3 mb-lg-0">Can't find what you're looking for?</h3>
                <p class="mb-0">Let us know your requirements and we'll help you find the perfect vehicle.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="contact.php" class="btn btn-warning btn-lg">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
