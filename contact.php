<?php
require_once 'config/database.php';
$page_title = 'Contact Us | CAR LISTO';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="fw-bold">Contact Us</h1>
                <p class="lead">Get in touch with our team</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <h2 class="fw-bold mb-4">Get In Touch</h2>
                <p class="mb-5">Have questions about our vehicles or services? Fill out the form and our team will get back to you as soon as possible.</p>
                
                <div class="contact-info mb-4">
                    <div class="d-flex mb-3">
                        <div class="me-3 text-primary">
                            <i class="bi bi-geo-alt-fill fs-4"></i>
                        </div>
                        <div>
                            <h5>Location</h5>
                            <p class="mb-0">Mombasa Road, Nairobi, Kenya</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="me-3 text-primary">
                            <i class="bi bi-envelope-fill fs-4"></i>
                        </div>
                        <div>
                            <h5>Email Us</h5>
                            <p class="mb-0">info@carlisto.co.ke</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="me-3 text-primary">
                            <i class="bi bi-telephone-fill fs-4"></i>
                        </div>
                        <div>
                            <h5>Call Us</h5>
                            <p class="mb-0">+254 712 345 678</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="me-3 text-primary">
                            <i class="bi bi-clock-fill fs-4"></i>
                        </div>
                        <div>
                            <h5>Working Hours</h5>
                            <p class="mb-0">Mon - Fri: 8:00 AM - 6:00 PM</p>
                            <p class="mb-0">Sat: 9:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                </div>
                
                <div class="social-links mt-5">
                    <h5 class="mb-3">Follow Us</h5>
                    <a href="#" class="text-primary me-3 fs-4"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-primary me-3 fs-4"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-primary me-3 fs-4"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-primary me-3 fs-4"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="text-primary me-3 fs-4"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <h3 class="fw-bold mb-4">Send Us a Message</h3>
                        <form id="contactForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" id="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Your Email *</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" class="form-control" id="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Your Message *</label>
                                <textarea class="form-control" id="message" rows="5" required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.813156678087!2d36.82155291475397!3d-1.2923596359776155!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d71b5665a1%3A0x3e4a1c8d8f5a4b0d!2sMombasa%20Rd%2C%20Nairobi!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Frequently Asked Questions</h2>
            <p class="lead">Find answers to common questions about our services</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item mb-3 border-0 shadow-sm">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept various payment methods including bank transfers, mobile money (M-Pesa), and credit/debit cards. We also offer flexible financing options through our partner financial institutions.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item mb-3 border-0 shadow-sm">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                Do you offer test drives?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, we encourage all potential buyers to test drive their preferred vehicles. Please contact us to schedule a test drive at your convenience.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item mb-3 border-0 shadow-sm">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                What is your return policy?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We offer a 7-day return policy on all vehicles, subject to terms and conditions. The vehicle must be in the same condition as when purchased and have no more than 500 additional kilometers.
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-5">
                        <p class="mb-3">Have more questions?</p>
                        <a href="#contact" class="btn btn-outline-primary">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const contactForm = document.getElementById('contactForm');
    const formResponse = document.createElement('div');
    contactForm.parentNode.insertBefore(formResponse, contactForm.nextSibling);

    contactForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            subject: document.getElementById('subject').value,
            phone: document.getElementById('phone').value,
            message: document.getElementById('message').value
        };

        fetch('/carshowroom/api/handlers/contact.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (typeof data.message !== "undefined") {
                const message = data.message;
                const alertClass = data.success ? 'alert-success' : 'alert-danger';
                formResponse.innerHTML = `<div class="alert ${alertClass} mt-3">${message}</div>`;
                if (data.success) {
                    contactForm.reset();
                }
            } else {
                formResponse.innerHTML = `<div class="alert alert-warning mt-3">Unexpected response from server</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            formResponse.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <strong>Error:</strong> ${error.message || 'An error occurred. Please try again later.'}
                </div>`;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

