<?php
require_once 'config/database.php';
include 'includes/header.php';
?>

<style>
.hero-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
    overflow: hidden;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.car-showcase {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
    opacity: 0.1;
}

.search-box {
    max-width: 800px;
    margin: 0 auto;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.car-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.car-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.car-specs {
    font-size: 0.85rem;
}

.testimonial-item {
    position: relative;
}

.testimonial-dots {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #dee2e6;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.dot.active {
    background-color: #ffc107;
}

.section-title h2 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 10px;
}

.price-tag {
    font-size: 1.25rem;
    font-weight: 700;
}

.btn-warning {
    background-color: #ff6b35;
    border-color: #ff6b35;
    border-radius: 25px;
    padding: 10px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-warning:hover {
    background-color: #e55a2b;
    border-color: #e55a2b;
    transform: translateY(-2px);
}

.footer-section {
    background-color: #2c3e50;
    color: white;
    padding: 50px 0 20px;
}

.footer-section h5 {
    color: #ff6b35;
    margin-bottom: 20px;
}

.footer-section a {
    color: #bdc3c7;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section a:hover {
    color: #ff6b35;
}
</style>

<!-- Hero Section -->
<section class="hero-section position-relative py-5">
    <div class="container">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-6 hero-content">
                <h1 class="display-4 fw-bold mb-3 text-dark">CAR LISTING DIRECTORY</h1>
                <p class="lead mb-5 text-muted">Over 9500 Classified Listings</p>
                
                <!-- Search Form -->
                <div class="search-box bg-white p-4 rounded shadow">
                    <form class="row g-3" method="GET" action="search.php">
                        <div class="col-md-3">
                            <select name="brand" class="form-select">
                                <option value="">Brand</option>
                                <option value="toyota">Toyota</option>
                                <option value="honda">Honda</option>
                                <option value="nissan">Nissan</option>
                                <option value="bmw">BMW</option>
                                <option value="mercedes">Mercedes-Benz</option>
                                <option value="audi">Audi</option>
                                <option value="volkswagen">Volkswagen</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="condition" class="form-select">
                                <option value="">Condition</option>
                                <option value="new">New</option>
                                <option value="used">Used</option>
                                <option value="certified">Certified</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="price_range" class="form-select">
                                <option value="">Price Range (USD)</option>
                                <option value="0-5000">$0 - $5,000</option>
                                <option value="5000-15000">$5,000 - $15,000</option>
                                <option value="15000-30000">$15,000 - $30,000</option>
                                <option value="30000-50000">$30,000 - $50,000</option>
                                <option value="50000+">$50,000+</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-warning w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://via.placeholder.com/600x350/4a90e2/ffffff?text=Premium+Cars" class="img-fluid" alt="Car Showcase" style="border-radius: 15px;">
            </div>
        </div>
    </div>
</section>

<!-- Featured Cars Section -->
<section class="featured-cars py-5">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Featured Cars</h2>
            <p class="text-muted">Check out our featured vehicles with premium quality and verified details</p>
        </div>
        
        <div class="row g-4">
            <!-- Featured Car 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="card car-card h-100">
                    <div class="position-relative">
                        <span class="badge bg-danger position-absolute m-3" style="z-index: 2;">Featured</span>
                        <img src="https://via.placeholder.com/400x250/2c3e50/ffffff?text=2018+McLaren+650S" class="card-img-top" alt="McLaren 650S">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold">2018 McLaren 650S</h5>
                        <div class="d-flex justify-content-between text-muted mb-3">
                            <small><i class="bi bi-eye"></i> 2,510 Views</small>
                            <small><i class="bi bi-clock"></i> 3 weeks ago</small>
                        </div>
                        <div class="car-specs d-flex justify-content-between py-2 border-top border-bottom mb-3">
                            <div class="text-center">
                                <div><i class="bi bi-calendar2"></i></div>
                                <small>2018</small>
                            </div>
                            <div class="text-center">
                                <div><i class="bi bi-speedometer2"></i></div>
                                <small>12k km</small>
                            </div>
                            <div class="text-center">
                                <div><i class="bi bi-fuel-pump"></i></div>
                                <small>Petrol</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-warning price-tag">$ 89,500</h5>
                            <span class="badge bg-success rounded-pill">Certified</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Car 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="card car-card h-100">
                    <div class="position-relative">
                        <span class="badge bg-danger position-absolute m-3" style="z-index: 2;">Featured</span>
                        <img src="https://via.placeholder.com/400x250/34495e/ffffff?text=2019+Lexus+RX+350" class="card-img-top" alt="Lexus RX 350">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold">2019 Lexus RX 350</h5>
                        <div class="d-flex justify-content-between text-muted mb-3">
                            <small><i class="bi bi-eye"></i> 1,847 Views</small>
                            <small><i class="bi bi-clock"></i> 1 week ago</small>
                        </div>
                        <div class="car-specs d-flex justify-content-between py-2 border-top border-bottom mb-3">
                            <div class="text-center">
                                <div><i class="bi bi-calendar2"></i></div>
                                <small>2019</small>
                            </div>
                            <div class="text-center">
                                <div><i class="bi bi-speedometer2"></i></div>
                                <small>25k km</small>
                            </div>
                            <div class="text-center">
                                <div><i class="bi bi-fuel-pump"></i></div>
                                <small>Hybrid</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-warning price-tag">$ 52,900</h5>
                            <span class="badge bg-primary rounded-pill">New</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Car 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="card car-card h-100">
                    <div class="position-relative">
                        <span class="badge bg-danger position-absolute m-3" style="z-index: 2;">Featured</span>
                        <img src="https://via.placeholder.com/400x250/e74c3c/ffffff?text=2017+Mercedes+SL+Class" class="card-img-top" alt="Mercedes SL Class">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold">2017 Mercedes SL Class</h5>
                        <div class="d-flex justify-content-between text-muted mb-3">
                            <small><i class="bi bi-eye"></i> 3,205 Views</small>
                            <small><i class="bi bi-clock"></i> 5 days ago</small>
                        </div>
                        <div class="car-specs d-flex justify-content-between py-2 border-top border-bottom mb-3">
                            <div class="text-center">
                                <div><i class="bi bi-calendar2"></i></div>
                                <small>2017</small>
                            </div>
                            <div class="text-center">
                                <div><i class="bi bi-speedometer2"></i></div>
                                <small>18k km</small>
                            </div>
                            <div class="text-center">
                                <div><i class="bi bi-fuel-pump"></i></div>
                                <small>Petrol</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-warning price-tag">$ 75,500</h5>
                            <span class="badge bg-info rounded-pill">Premium</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Cars Section -->
<section class="latest-cars py-5 bg-light">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Latest Cars</h2>
            <p class="text-muted">Browse our most recent listings with competitive prices</p>
        </div>
        
        <div class="row g-4">
            <?php
            // Sample latest cars data - in real implementation, this would come from database
            $latest_cars = [
                ['name' => '2018 McLaren 650S', 'views' => '2,156', 'time' => '3 weeks ago', 'price' => '$ 79,900', 'status' => 'Used', 'status_class' => 'bg-warning'],
                ['name' => 'Lexus LFA - 2014', 'views' => '1,823', 'time' => '2 weeks ago', 'price' => '$ 385,450', 'status' => 'Rare', 'status_class' => 'bg-danger'],
                ['name' => '2015 Lexus RC 350', 'views' => '945', 'time' => '1 week ago', 'price' => '$ 38,750', 'status' => 'Certified', 'status_class' => 'bg-success'],
                ['name' => '2011 Nissan Juke SL', 'views' => '672', 'time' => '4 days ago', 'price' => '$ 18,500', 'status' => 'Good', 'status_class' => 'bg-info'],
                ['name' => '2013 Lexus RX 350', 'views' => '1,234', 'time' => '6 days ago', 'price' => '$ 26,900', 'status' => 'Featured', 'status_class' => 'bg-primary'],
                ['name' => '2019 Volkswagen Touareg', 'views' => '856', 'time' => '1 week ago', 'price' => '$ 48,200', 'status' => 'New', 'status_class' => 'bg-success']
            ];
            
            foreach($latest_cars as $index => $car): 
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card car-card h-100">
                    <img src="https://via.placeholder.com/400x250/<?php echo sprintf('%06X', mt_rand(0, 0xFFFFFF)); ?>/ffffff?text=<?php echo urlencode($car['name']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($car['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($car['name']); ?></h5>
                        <div class="d-flex justify-content-between text-muted mb-3">
                            <small><i class="bi bi-eye"></i> <?php echo $car['views']; ?> Views</small>
                            <small><i class="bi bi-clock"></i> <?php echo $car['time']; ?></small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-warning price-tag"><?php echo $car['price']; ?></h6>
                            <span class="badge <?php echo $car['status_class']; ?> rounded-pill"><?php echo $car['status']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="listings.php" class="btn btn-warning btn-lg px-5">View More</a>
        </div>
    </div>
</section>

<!-- Customer Reviews -->
<section class="testimonials py-5">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Customer Reviews</h2>
            <p class="text-muted">What our clients say about our exceptional service and quality vehicles</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="testimonial-item text-center">
                    <img src="https://via.placeholder.com/100x100/4a90e2/ffffff?text=MK" class="rounded-circle mb-3" alt="Mamun Khan">
                    <h5 class="fw-bold">Mamun Khan</h5>
                    <p class="text-muted">CEO of Apple</p>
                    <div class="testimonial-text bg-light p-4 my-4 rounded">
                        <i class="bi bi-quote display-6 d-block text-warning mb-3"></i>
                        <p class="mb-0 fst-italic">"Outstanding service and incredible selection of premium vehicles. The team went above and beyond to help me find the perfect car. The verification process gave me complete confidence in my purchase. Highly recommended for anyone looking for quality and reliability!"</p>
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
            <p class="text-muted">Stay updated with automotive news, tips, and industry insights</p>
        </div>
        
        <div class="row g-4">
            <?php
            // Sample blog posts - in real implementation, this would come from database
            $blog_posts = [
                [
                    'title' => 'Top five secrets for car maintenance',
                    'author' => 'Admin',
                    'date' => 'Aug 20, 2023',
                    'excerpt' => 'Learn the essential tips and tricks to keep your vehicle running smoothly for years to come.',
                    'image_bg' => 'b19cd9'
                ],
                [
                    'title' => 'How to choose the right car insurance',
                    'author' => 'Sarah Johnson',
                    'date' => 'Aug 18, 2023',
                    'excerpt' => 'Navigate through different insurance options and find the perfect coverage for your needs.',
                    'image_bg' => '5cb85c'
                ],
                [
                    'title' => 'Electric vs Gasoline: Complete guide',
                    'author' => 'Mike Chen',
                    'date' => 'Aug 15, 2023',
                    'excerpt' => 'Compare the pros and cons of electric and gasoline vehicles in our comprehensive guide.',
                    'image_bg' => 'f0ad4e'
                ]
            ];
            
            foreach($blog_posts as $post):
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="https://via.placeholder.com/400x250/<?php echo $post['image_bg']; ?>/ffffff?text=<?php echo urlencode(substr($post['title'], 0, 20)); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <small><i class="bi bi-person"></i> By <?php echo htmlspecialchars($post['author']); ?></small>
                            <small><i class="bi bi-calendar"></i> <?php echo $post['date']; ?></small>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($post['title']); ?></h5>
                        <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        <a href="blog-single.php" class="btn btn-link text-warning text-decoration-none p-0 fw-semibold">
                            Read More <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter py-5" style="background-color: #2c3e50;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-3">Newsletter</h2>
                <p class="text-light mb-4">Subscribe to get updates on new arrivals, special offers, and automotive tips</p>
                <form class="d-flex justify-content-center gap-2">
                    <input type="email" class="form-control" style="max-width: 300px;" placeholder="Enter your email address" required>
                    <button type="submit" class="btn btn-warning px-4">FOLLOW US</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Simple testimonial dots functionality
document.addEventListener('DOMContentLoaded', function() {
    const dots = document.querySelectorAll('.dot');
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            dots.forEach(d => d.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>