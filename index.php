<?php
  session_start();
  if(isset($_POST['submit'])){

  } else {

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="FitZone Gym - Transform Your Body & Mind" />
    <meta name="keywords" content="gym, fitness, training, workout, health" />
    <meta name="author" content="Sniper 2025" />
    <title>FitZone Gym - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/material.css" />
    <link rel="stylesheet" href="./dist/assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="./dist/assets/css/home.css" />
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">FitZone</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#classes">Classes</a></li>
                <li><a href="#trainers">Trainers</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="cta-btn">Member Login</a></li>
            </ul>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Transform Your Life</h1>
            <p>Join the ultimate fitness experience</p>
            <a href="#pricing" class="hero-btn">Start Your Journey</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Why Choose Us</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">💪</div>
                <h3>Expert Trainers</h3>
                <p>Certified professionals dedicated to your fitness goals with personalized training programs.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏋️</div>
                <h3>Modern Equipment</h3>
                <p>State-of-the-art machines and free weights for every type of workout.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🕐</div>
                <h3>Flexible Hours</h3>
                <p>Open 24/7 to fit your busy schedule. Work out whenever it suits you best.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Group Classes</h3>
                <p>Dynamic group sessions including yoga, spinning, HIIT, and more.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Personal Training</h3>
                <p>One-on-one sessions tailored to achieve your specific fitness objectives.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Results Driven</h3>
                <p>Proven programs that deliver real, measurable results for all fitness levels.</p>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="classes" class="gallery">
        <h2 class="section-title">Our Facility</h2>
        <div class="gallery-grid">
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=800" alt="Gym Equipment">
                <div class="gallery-overlay">
                    <h3>Premium Equipment</h3>
                    <p>Top-of-the-line machines</p>
                </div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=800" alt="Group Class">
                <div class="gallery-overlay">
                    <h3>Group Classes</h3>
                    <p>High-energy workouts</p>
                </div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=800" alt="Cardio Zone">
                <div class="gallery-overlay">
                    <h3>Cardio Zone</h3>
                    <p>Latest cardio equipment</p>
                </div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1576678927484-cc907957088c?w=800" alt="Free Weights">
                <div class="gallery-overlay">
                    <h3>Free Weights Area</h3>
                    <p>Complete range of weights</p>
                </div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800" alt="Yoga Studio">
                <div class="gallery-overlay">
                    <h3>Yoga Studio</h3>
                    <p>Peaceful practice space</p>
                </div>
            </div>
            <div class="gallery-item">
                <img src="https://images.unsplash.com/photo-1558017487-06bf9f82613a?w=800" alt="Personal Training">
                <div class="gallery-overlay">
                    <h3>Personal Training</h3>
                    <p>One-on-one coaching</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
        <h2 class="section-title">Membership Plans</h2>
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3>Basic</h3>
                <div class="price">$29<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Gym Access</li>
                    <li>✓ Cardio Equipment</li>
                    <li>✓ Locker Room</li>
                    <li>✓ Free WiFi</li>
                </ul>
                <a href="#" class="cta-btn">Get Started</a>
            </div>
            <div class="pricing-card">
                <h3>Premium</h3>
                <div class="price">$59<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Everything in Basic</li>
                    <li>✓ Group Classes</li>
                    <li>✓ Sauna Access</li>
                    <li>✓ Nutrition Guidance</li>
                    <li>✓ Guest Passes (2/month)</li>
                </ul>
                <a href="#" class="cta-btn">Get Started</a>
            </div>
            <div class="pricing-card">
                <h3>Elite</h3>
                <div class="price">$99<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Everything in Premium</li>
                    <li>✓ Personal Training (4 sessions)</li>
                    <li>✓ Priority Booking</li>
                    <li>✓ Massage Therapy</li>
                    <li>✓ Unlimited Guest Passes</li>
                    <li>✓ Exclusive Events</li>
                </ul>
                <a href="#" class="cta-btn">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>FitZone Gym</h3>
                <p>Transform your body and mind with our expert trainers and world-class facilities.</p>
                <div class="social-links">
                    <a href="#">f</a>
                    <a href="#">i</a>
 
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#classes">Classes</a></li>
                    <li><a href="#trainers">Trainers</a></li>
                    <li><a href="./dist/admin/dashboard.php">Admin Dashboard</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Services</h3>
                <ul>
                    <li><a href="#">Personal Training</a></li>
                    <li><a href="#">Group Classes</a></li>
                    <li><a href="#">Nutrition Coaching</a></li>
                    <li><a href="#">Online Training</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li>📍 123 Fitness Street, Baguio City</li>
                    <li>📞 +63 123 456 7890</li>
                    <li>✉️ info@fitzonegym.com</li>
                    <li>🕐 Open 24/7</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 FitZone Gym. All rights reserved. | Designed by Sniper 2025</p>
        </div>
    </footer>

    <!-- Required Js -->
    <script src="./dist/assets/js/plugins/simplebar.min.js"></script>
    <script src="./dist/assets/js/plugins/popper.min.js"></script>
    <script src="./dist/assets/js/icon/custom-icon.js"></script>
    <script src="./dist/assets/js/plugins/feather.min.js"></script>
    <script src="./dist/assets/js/component.js"></script>
    <script src="./dist/assets/js/theme.js"></script>
    <script src="./dist/assets/js/script.js"></script>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Header background change on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'rgba(26, 26, 26, 0.98)';
            } else {
                header.style.background = 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)';
            }
        });
    </script>
</body>
</html>

<?php } ?>