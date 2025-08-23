<?php
require_once 'config/database.php';
$page_title = 'About Us | CAR LISTO';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="fw-bold">About CAR LISTO</h1>
                <p class="lead">Your Trusted Partner in the Automotive Industry</p>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4">
                <img src="https://via.placeholder.com/800x600" alt="Our Showroom" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <h2 class="fw-bold mb-4">Your Trusted Car Dealer Since 2008</h2>
                <p>At CAR LISTO, we're committed to providing the highest quality vehicles and exceptional customer service to car buyers across Kenya.</p>
                <p>Founded in 2008, CAR LISTO has grown from a small local dealership to one of the most trusted names in the Kenyan automotive industry.</p>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <i class="bi bi-check2-circle text-primary me-3 mt-1"></i>
                            <div>
                                <h5>Quality Vehicles</h5>
                                <p class="mb-0">Rigorous inspection process</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex">
                            <i class="bi bi-people text-primary me-3 mt-1"></i>
                            <div>
                                <h5>Expert Team</h5>
                                <p class="mb-0">Knowledgeable staff</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Our Team</h2>
            <p>Meet our dedicated professionals</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3 col-6 text-center">
                <img src="https://via.placeholder.com/200" class="rounded-circle mb-3" width="150" alt="Team Member">
                <h5>John Kamau</h5>
                <p class="text-muted">CEO</p>
            </div>
            <div class="col-md-3 col-6 text-center">
                <img src="https://via.placeholder.com/200" class="rounded-circle mb-3" width="150" alt="Team Member">
                <h5>Sarah Wanjiku</h5>
                <p class="text-muted">Sales Director</p>
            </div>
            <div class="col-md-3 col-6 text-center">
                <img src="https://via.placeholder.com/200" class="rounded-circle mb-3" width="150" alt="Team Member">
                <h5>David Ochieng</h5>
                <p class="text-muted">Service Head</p>
            </div>
            <div class="col-md-3 col-6 text-center">
                <img src="https://via.placeholder.com/200" class="rounded-circle mb-3" width="150" alt="Team Member">
                <h5>Grace Mwende</h5>
                <p class="text-muted">Customer Relations</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
