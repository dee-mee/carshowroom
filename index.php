<?php
// Load configuration
require_once 'config/config.php';

// Get banner data from database
try {
    require_once 'config/database.php';
    
    // Get the latest banner by upload time
    $bannerStmt = $conn->prepare("SELECT * FROM header_banner ORDER BY updated_at DESC LIMIT 1");
    $bannerStmt->execute();
    $banner = $bannerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$banner) {
        $defaultBanner = str_replace(BASE_URL, '', DEFAULT_BANNER);
        $insertStmt = $conn->prepare("INSERT INTO header_banner (background_image, title, subtitle) VALUES (:bg_image, 'Welcome to Car Showroom', 'Find your dream car today')");
        $insertStmt->execute([':bg_image' => $defaultBanner]);
        // Get the newly inserted banner
        $bannerStmt->execute();
        $banner = $bannerStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    
    // Process background image path - don't fall back to default if we have a valid path
    if (!empty($banner['background_image'])) {
        // For web display, add /carshowroom prefix if not already present
        if (strpos($banner['background_image'], '/carshowroom/') !== 0) {
            $web_path = '/carshowroom' . $banner['background_image'];
        } else {
            $web_path = $banner['background_image'];
        }
    } else {
        // Only use default if no image path exists
        $web_path = DEFAULT_BANNER;
    }
    
    // Use web path for display
    $banner['background_image'] = $web_path;
    
    // Get featured cars from database
    $featuredStmt = $conn->prepare("SELECT * FROM featured_cars WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT 6");
    $featuredStmt->execute();
    $featured_cars = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest cars from database
    $latestStmt = $conn->prepare("SELECT * FROM latest_cars WHERE is_active = 'yes' ORDER BY sort_order ASC, created_at DESC LIMIT 6");
    $latestStmt->execute();
    $latest_cars = $latestStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get testimonials from database
    $testimonialsStmt = $conn->prepare("SELECT * FROM testimonials WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
    $testimonialsStmt->execute();
    $testimonials = $testimonialsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    // Only override banner if it wasn't already set successfully
    if (!isset($banner) || empty($banner)) {
        $banner = [
            'background_image' => DEFAULT_BANNER,
            'bottom_image' => '',
            'title' => 'Car Showroom',
            'subtitle' => 'Find your dream car today'
        ];
    }
    // Set empty arrays for cars if database fails
    $featured_cars = [];
    $latest_cars = [];
    $testimonials = [];
}

// Ensure cars arrays exist even if there was an error
if (!isset($featured_cars)) {
    $featured_cars = [];
}
if (!isset($latest_cars)) {
    $latest_cars = [];
}
if (!isset($testimonials)) {
    $testimonials = [];
}

// Get latest published blog posts
$blog_posts = [];
try {
    $blogStmt = $conn->prepare("
        SELECT id, title, slug, excerpt, content, image_path, created_at, author_id
        FROM blogs 
        WHERE status = 'published' 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $blogStmt->execute();
    $blog_posts = $blogStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process blog post data
    foreach ($blog_posts as &$post) {
        // Create excerpt if none exists
        if (empty($post['excerpt'])) {
            $post['excerpt'] = substr(strip_tags($post['content']), 0, 120) . '...';
        }
        
        // Process image path
        if (!empty($post['image_path'])) {
            // If the path doesn't start with http or /, add BASE_URL
            if (strpos($post['image_path'], 'http') !== 0 && $post['image_path'][0] !== '/') {
                $post['image_path'] = BASE_URL . '/' . ltrim($post['image_path'], '/');
            } elseif ($post['image_path'][0] === '/' && strpos($post['image_path'], BASE_URL) !== 0) {
                $post['image_path'] = BASE_URL . $post['image_path'];
            }
        } else {
            // Use placeholder if no image
            $post['image_path'] = 'https://via.placeholder.com/400x250/6c5ce7/ffffff?text=' . urlencode(substr($post['title'], 0, 20));
        }
        
        // Format date
        $post['formatted_date'] = date('M d, Y', strtotime($post['created_at']));
        
        // Get author name (you might want to join with users table if you have one)
        $post['author_name'] = 'Admin'; // Default author name
        
        // Create blog post URL
        $post['url'] = 'blog-single.php?slug=' . urlencode($post['slug']);
    }
    
} catch(PDOException $e) {
    error_log('Blog Error: ' . $e->getMessage());
    // Fallback to sample data if database query fails
    $blog_posts = [
        [
            'title' => 'Top five secrets for car maintenance',
            'author_name' => 'Admin',
            'formatted_date' => 'Aug 20, 2023',
            'excerpt' => 'Learn the essential tips and tricks to keep your vehicle running smoothly for years to come.',
            'image_path' => 'https://via.placeholder.com/400x250/b19cd9/ffffff?text=Car+Maintenance',
            'url' => 'blog-single.php'
        ],
        [
            'title' => 'How to choose the right car insurance',
            'author_name' => 'Admin',
            'formatted_date' => 'Aug 18, 2023',
            'excerpt' => 'Navigate through different insurance options and find the perfect coverage for your needs.',
            'image_path' => 'https://via.placeholder.com/400x250/5cb85c/ffffff?text=Car+Insurance',
            'url' => 'blog-single.php'
        ],
        [
            'title' => 'Electric vs Gasoline: Complete guide',
            'author_name' => 'Admin',
            'formatted_date' => 'Aug 15, 2023',
            'excerpt' => 'Compare the pros and cons of electric and gasoline vehicles in our comprehensive guide.',
            'image_path' => 'https://via.placeholder.com/400x250/f0ad4e/ffffff?text=Electric+Cars',
            'url' => 'blog-single.php'
        ]
    ];
}

include 'includes/header.php';
?>

<style>
.hero-section {
    background-image: url('<?php 
        echo htmlspecialchars($banner['background_image'], ENT_QUOTES, 'UTF-8');
    ?>?v=<?php echo time(); ?>');
    background-repeat: no-repeat;
    background-position: center center;
    background-size: cover;
    position: relative;
    overflow: hidden;
    min-height: 100vh;
    display: flex;
    align-items: center;
    color: #fff;
}

.hero-section:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    width: 100%;
}

.hero-content h1 {
    color: #fff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    font-size: 3.5rem;
    font-weight: 800;
}

.hero-content p {
    color: #f8f9fa;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
    font-size: 1.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-section {
        min-height: 500px;
    }
}

.search-box {
    max-width: 1000px;
    margin: 0 auto;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.search-box .form-select {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px 15px;
    font-weight: 500;
}

.search-box .btn-warning {
    padding: 12px 15px;
    font-size: 1.1rem;
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

/* Blog card hover effects */
.blog-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.blog-image {
    height: 250px;
    object-fit: cover;
    width: 100%;
}

.blog-meta {
    font-size: 0.85rem;
}

.blog-title {
    color: #2c3e50;
    line-height: 1.4;
}

.blog-excerpt {
    color: #6c757d;
    line-height: 1.6;
}

.read-more-link {
    color: #ff6b35;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s ease;
}

.read-more-link:hover {
    color: #e55a2b;
}
</style>

<!-- Hero/Banner Section -->
<section class="hero-section position-relative" style="background-image: url('<?php echo htmlspecialchars($banner['background_image'], ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo time(); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="hero-content py-5">
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($banner['title'] ?? 'CAR LISTING DIRECTORY', ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="lead mb-5"><?php echo htmlspecialchars($banner['subtitle'] ?? 'Over 9500 Classified Listings', ENT_QUOTES, 'UTF-8'); ?></p>
                    
                    <!-- Search Form -->
                    <div class="search-box p-4 rounded mx-auto">
                        <form class="row g-3" method="GET" action="search.php">
                            <div class="col-lg-3 col-md-6 col-sm-6">
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
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <select name="condition" class="form-select">
                                    <option value="">Condition</option>
                                    <option value="new">New</option>
                                    <option value="used">Used</option>
                                    <option value="certified">Certified</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <select name="price_range" class="form-select">
                                    <option value="">Price Range (USD)</option>
                                    <option value="0-5000">$0 - $5,000</option>
                                    <option value="5000-15000">$5,000 - $15,000</option>
                                    <option value="15000-30000">$15,000 - $30,000</option>
                                    <option value="30000-50000">$30,000 - $50,000</option>
                                    <option value="50000+">$50,000+</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6">
                                <button type="submit" class="btn btn-warning w-100 fw-bold">Search</button>
                            </div>
                        </form>
                    </div>
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
            <p class="text-muted">Check out our featured vehicles with premium quality and verified details</p>
        </div>
        
        <div class="row g-4">
            <?php if (empty($featured_cars)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-car-front fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No featured cars available at the moment. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featured_cars as $car): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card car-card h-100">
                            <div class="position-relative">
                                <span class="badge bg-danger position-absolute m-3" style="z-index: 2;">Featured</span>
                                <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($car['title']); ?>"
                                     onerror="this.src='https://via.placeholder.com/400x250/6c5ce7/ffffff?text=<?php echo urlencode($car['title']); ?>'">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($car['title']); ?></h5>
                                <div class="d-flex justify-content-between text-muted mb-3">
                                    <small><i class="bi bi-eye"></i> <?php echo htmlspecialchars($car['views']); ?> Views</small>
                                    <small><i class="bi bi-clock"></i> <?php echo htmlspecialchars($car['time_posted']); ?></small>
                                </div>
                                <div class="car-specs d-flex justify-content-between py-2 border-top border-bottom mb-3">
                                    <div class="text-center">
                                        <div><i class="bi bi-calendar2"></i></div>
                                        <small><?php echo htmlspecialchars($car['year']); ?></small>
                                    </div>
                                    <div class="text-center">
                                        <div><i class="bi bi-speedometer2"></i></div>
                                        <small><?php echo htmlspecialchars($car['mileage']); ?></small>
                                    </div>
                                    <div class="text-center">
                                        <div><i class="bi bi-fuel-pump"></i></div>
                                        <small><?php echo htmlspecialchars($car['fuel_type']); ?></small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-warning price-tag"><?php echo htmlspecialchars($car['price']); ?></h5>
                                    <span class="badge <?php echo htmlspecialchars($car['status_class']); ?> rounded-pill"><?php echo htmlspecialchars($car['condition']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            <?php if (empty($latest_cars)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-clock fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No latest cars available at the moment. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($latest_cars as $car): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card car-card h-100">
                            <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($car['title']); ?>"
                                 onerror="this.src='https://via.placeholder.com/400x250/6c5ce7/ffffff?text=<?php echo urlencode($car['title']); ?>'">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($car['title']); ?></h5>
                                <div class="d-flex justify-content-between text-muted mb-3">
                                    <small><i class="bi bi-eye"></i> <?php echo htmlspecialchars($car['views']); ?> Views</small>
                                    <small><i class="bi bi-clock"></i> <?php echo htmlspecialchars($car['time_posted']); ?></small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-warning price-tag"><?php echo htmlspecialchars($car['price']); ?></h6>
                                    <span class="badge <?php echo htmlspecialchars($car['status_class']); ?> rounded-pill"><?php echo htmlspecialchars($car['status']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        
        <?php if (!empty($blog_posts)): ?>
        <div class="row g-4">
            <?php foreach($blog_posts as $post): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card blog-card h-100 border-0 shadow-sm">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                         class="blog-image card-img-top" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         onerror="this.src='https://via.placeholder.com/400x250/6c5ce7/ffffff?text=<?php echo urlencode(substr($post['title'], 0, 20)); ?>'">
                    <div class="card-body d-flex flex-column">
                        <div class="blog-meta d-flex justify-content-between text-muted mb-2">
                            <small><i class="bi bi-person"></i> By <?php echo htmlspecialchars($post['author_name']); ?></small>
                            <small><i class="bi bi-calendar"></i> <?php echo $post['formatted_date']; ?></small>
                        </div>
                        <h5 class="blog-title card-title fw-bold"><?php echo htmlspecialchars($post['title']); ?></h5>
                        <p class="blog-excerpt card-text text-muted flex-grow-1"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        <a href="<?php echo $post['url']; ?>" class="read-more-link text-decoration-none fw-semibold">
                            Read More <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($blog_posts) >= 3): ?>
        <div class="text-center mt-5">
            <a href="blog.php" class="btn btn-warning btn-lg px-5">View All Posts</a>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="bg-white p-5 rounded shadow-sm">
                    <i class="bi bi-journal-text display-1 text-muted mb-3"></i>
                    <h4 class="text-muted">No Blog Posts Yet</h4>
                    <p class="text-muted">Blog posts will appear here once they are published by the admin.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials py-5 bg-light">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>What Our Customers Say</h2>
            <p class="text-muted">Read testimonials from our satisfied customers</p>
        </div>
        
        <div class="row g-4">
            <?php if (empty($testimonials)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-chat-quote fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No testimonials available at the moment. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card testimonial-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="testimonial-avatar mb-3">
                                    <?php if ($testimonial['image_path']): ?>
                                        <img src="/carshowroom<?php echo htmlspecialchars($testimonial['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" 
                                             class="rounded-circle" 
                                             style="width: 80px; height: 80px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 80px;">
                                            <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="rating mb-3">
                                    <?php 
                                    $full_stars = (int)$testimonial['rating'];
                                    $empty_stars = 5 - $full_stars;
                                    for ($i = 0; $i < $full_stars; $i++) {
                                        echo '<i class="bi bi-star-fill text-warning"></i>';
                                    }
                                    for ($i = 0; $i < $empty_stars; $i++) {
                                        echo '<i class="bi bi-star text-warning"></i>';
                                    }
                                    ?>
                                </div>
                                
                                <blockquote class="mb-3">
                                    <p class="text-muted fst-italic">"<?php echo htmlspecialchars($testimonial['testimonial']); ?>"</p>
                                </blockquote>
                                
                                <div class="testimonial-author">
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($testimonial['client_name']); ?></h6>
                                    <?php if ($testimonial['company']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($testimonial['company']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    
    // Handle image loading errors for blog posts
    const blogImages = document.querySelectorAll('.blog-image');
    blogImages.forEach(img => {
        img.addEventListener('error', function() {
            if (!this.hasAttribute('data-fallback-set')) {
                this.src = 'https://via.placeholder.com/400x250/6c5ce7/ffffff?text=Blog+Post';
                this.setAttribute('data-fallback-set', 'true');
            }
        });
    });
});
</script>