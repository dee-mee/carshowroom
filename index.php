<?php
require_once 'config/database.php';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section position-relative py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-12 text-center py-5">
                <h1 class="display-4 fw-bold mb-3">Car Listing Directory</h1>
                <p class="lead mb-5">Over 9500 Classified Listings</p>
                
                <!-- Search Form -->
                <div class="search-box bg-white p-4 rounded shadow">
                    <form class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select">
                                <option selected>Select Brand</option>
                                <option>Toyota</option>
                                <option>Honda</option>
                                <option>Nissan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option selected>Condition</option>
                                <option>New</option>
                                <option>Used</option>
                                <option>Certified</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option selected>Price Range</option>
                                <option>$0 - $5,000</option>
                                <option>$5,000 - $10,000</option>
                                <option>$10,000+</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-warning w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Cars Section -->
<section class="featured-cars py-5">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Featured Cars</h2>
            <p>Check out our featured vehicles</p>
        </div>
        
        <div class="row g-4">
            <!-- Car Card 1 -->
            <div class="col-md-4">
                <div class="card car-card h-100">
                    <span class="badge bg-danger position-absolute m-2">Featured</span>
                    <img src="https://via.placeholder.com/800x500" class="card-img-top" alt="Car">
                    <div class="card-body">
                        <h5 class="card-title">2016 McLaren 650S</h5>
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <small><i class="bi bi-eye"></i> 2.5k Views</small>
                            <small>3 weeks ago</small>
                        </div>
                        <div class="car-specs d-flex justify-content-between py-2 border-top border-bottom mb-3">
                            <div><i class="bi bi-calendar"></i> 2016</div>
                            <div><i class="bi bi-speedometer2"></i> 12,000 km</div>
                            <div><i class="bi bi-fuel-pump"></i> Petrol</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-warning">$89,000</h5>
                            <span class="badge bg-success">Certified</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Repeat for 2 more featured cars -->
        </div>
    </div>
</section>

<!-- Latest Cars Section -->
<section class="latest-cars py-5 bg-light">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Latest Cars</h2>
            <p>Browse our most recent listings</p>
        </div>
        
        <div class="row g-4">
            <!-- Repeat this block for 6 cars -->
            <div class="col-md-4 col-lg-4">
                <div class="card car-card h-100">
                    <img src="https://via.placeholder.com/800x500" class="card-img-top" alt="Car">
                    <div class="card-body">
                        <h5 class="card-title">2020 Honda Civic</h5>
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <small><i class="bi bi-eye"></i> 1.2k Views</small>
                            <small>1 week ago</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-warning">$24,500</h6>
                            <span class="badge bg-primary">New</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Repeat for 5 more latest cars -->
        </div>
        
        <div class="text-center mt-5">
            <a href="#" class="btn btn-warning px-4">View More</a>
        </div>
    </div>
</section>

<!-- Customer Reviews -->
<section class="testimonials py-5">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Customer Reviews</h2>
            <p>What our clients say about us</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="testimonial-item text-center">
                    <img src="https://via.placeholder.com/100" class="rounded-circle mb-3" alt="Client">
                    <h5>Mamun Khan</h5>
                    <p class="text-muted">CEO of Apple</p>
                    <div class="testimonial-text bg-light p-4 my-4">
                        <i class="bi bi-quote display-6 d-block text-warning mb-3"></i>
                        <p>"Excellent service and great selection of vehicles. Highly recommended!"</p>
                    </div>
                    <div class="testimonial-dots">
                        <span class="dot active"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Blog -->
<section class="latest-blog py-5 bg-light">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Latest Blog</h2>
            <p>Stay updated with our news and tips</p>
        </div>
        
        <div class="row g-4">
            <!-- Blog Post 1 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <img src="https://via.placeholder.com/400x250" class="card-img-top" alt="Blog">
                    <div class="card-body">
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <small>By Admin</small>
                            <small>Aug 20, 2023</small>
                        </div>
                        <h5 class="card-title">Top Five Secrets for Car Maintenance</h5>
                        <p class="card-text">Learn the essential tips to keep your car running smoothly for years to come.</p>
                        <a href="#" class="btn btn-link text-warning text-decoration-none p-0">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <!-- Repeat for 3 more blog posts -->
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
