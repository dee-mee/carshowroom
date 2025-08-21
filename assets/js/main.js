// Main JavaScript File for CAR LISTO

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });
    }
    
    // Close mobile menu when clicking on a nav link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navbarCollapse.classList.contains('show')) {
                navbarCollapse.classList.remove('show');
            }
        });
    });
    
    // Sticky header on scroll
    const header = document.querySelector('nav');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                header.classList.add('sticky-top', 'shadow-sm');
                header.style.padding = '0.5rem 0';
                header.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            } else {
                header.classList.remove('sticky-top', 'shadow-sm');
                header.style.padding = '1rem 0';
                header.style.backgroundColor = '#fff';
            }
        });
    }
    
    // Testimonial slider
    const testimonialDots = document.querySelectorAll('.testimonial-dots .dot');
    const testimonials = [
        {
            name: 'Mamun Khan',
            position: 'CEO of Apple',
            text: 'Excellent service and great selection of vehicles. Highly recommended!'
        },
        {
            name: 'Sarah Johnson',
            position: 'Marketing Director',
            text: 'Found my dream car at an amazing price. The whole process was smooth and professional.'
        },
        {
            name: 'David Kimani',
            position: 'Business Owner',
            text: 'Best car dealership in Kenya. Their after-sales service is exceptional.'
        }
    ];
    
    let currentTestimonial = 0;
    
    function updateTestimonial(index) {
        const testimonial = testimonials[index];
        const testimonialElement = document.querySelector('.testimonial-item');
        
        if (testimonialElement) {
            testimonialElement.innerHTML = `
                <img src="https://via.placeholder.com/100" class="rounded-circle mb-3" alt="Client">
                <h5>${testimonial.name}</h5>
                <p class="text-muted">${testimonial.position}</p>
                <div class="testimonial-text bg-light p-4 my-4">
                    <i class="bi bi-quote display-6 d-block text-warning mb-3"></i>
                    <p>"${testimonial.text}"</p>
                </div>
                <div class="testimonial-dots">
                    ${testimonials.map((_, i) => 
                        `<span class="dot ${i === index ? 'active' : ''}" data-index="${i}"></span>`
                    ).join('')}
                </div>
            `;
            
            // Re-attach event listeners to dots
            document.querySelectorAll('.testimonial-dots .dot').forEach(dot => {
                dot.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    currentTestimonial = index;
                    updateTestimonial(index);
                });
            });
        }
    }
    
    // Auto-rotate testimonials
    setInterval(() => {
        currentTestimonial = (currentTestimonial + 1) % testimonials.length;
        updateTestimonial(currentTestimonial);
    }, 5000);
    
    // Initialize first testimonial
    if (testimonialDots.length > 0) {
        updateTestimonial(0);
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add animation on scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.fadeInUp');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Initial check
    animateOnScroll();
    
    // Check on scroll
    window.addEventListener('scroll', animateOnScroll);
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Price range slider
    const priceRange = document.getElementById('priceRange');
    const priceOutput = document.getElementById('priceOutput');
    
    if (priceRange && priceOutput) {
        priceOutput.textContent = '$' + priceRange.value;
        
        priceRange.addEventListener('input', function() {
            priceOutput.textContent = '$' + this.value;
        });
    }
});

// Back to top button
const backToTopButton = document.createElement('button');
backToTopButton.innerHTML = '<i class="bi bi-arrow-up"></i>';
backToTopButton.className = 'btn btn-warning btn-lg rounded-circle position-fixed';
backToTopButton.style.bottom = '30px';
backToTopButton.style.right = '30px';
backToTopButton.style.zIndex = '99';
backToTopButton.style.display = 'none';
backToTopButton.style.width = '50px';
backToTopButton.style.height = '50px';
backToTopButton.style.padding = '0';
backToTopButton.style.lineHeight = '50px';
backToTopButton.style.textAlign = 'center';

document.body.appendChild(backToTopButton);

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTopButton.style.display = 'block';
    } else {
        backToTopButton.style.display = 'none';
    }
});

backToTopButton.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Initialize tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Initialize popovers
const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
});
