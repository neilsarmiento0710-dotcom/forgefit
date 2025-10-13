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
    <title>ForgeFit - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/material.css" />

    <link rel="stylesheet" href="./dist/assets/css/home.css?v=4" id="main-style-link" />
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">About Us</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="cta-btn">Member Login</a></li>
                <li><a href="trainer_login.php" class="cta-btn">Trainer Login</a></li>
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
            <a href="register.php" class="hero-btn">Start Your Journey</a>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
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

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
        <h2 class="section-title">Membership Plans</h2>
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3>Basic</h3>
                <div class="price">600<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Gym Access</li>
                    <li>✓ Cardio Equipment</li>
                    <li>✓ Locker Room</li>
                    <li>✓ Free WiFi</li>
                </ul>
                <a href="register.php" class="cta-btn">Get Started</a>
            </div>
            <div class="pricing-card">
                <h3>Premium</h3>
                <div class="price">1000<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Everything in Basic</li>
                    <li>✓ Group Classes</li>
                    <li>✓ Sauna Access</li>
                    <li>✓ Nutrition Guidance</li>
                    <li>✓ Guest Passes (2/month)</li>
                </ul>
                <a href="register.php" class="cta-btn">Get Started</a>
            </div>
            <div class="pricing-card">
                <h3>Elite</h3>
                <div class="price">1250<span style="font-size: 1rem;">/mo</span></div>
                <ul class="pricing-features">
                    <li>✓ Everything in Premium</li>
                    <li>✓ Personal Training (4 sessions)</li>
                    <li>✓ Priority Booking</li>
                    <li>✓ Massage Therapy</li>
                    <li>✓ Unlimited Guest Passes</li>
                    <li>✓ Exclusive Events</li>
                </ul>
                <a href="register.php" class="cta-btn">Get Started</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>ForgeFit Gym</h3>
                <p>Transform your body and mind with our expert trainers and world-class facilities.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/koen725/">f</a>
                    <a href="https://www.instagram.com/oddkoen/">i</a>
 
                </div>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li>📍 Arellano Street, Dagupan City</li>
                    <li>📞 +63 946 540 3747</li>
                    <li>✉️ forgefit@gmail.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
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

</script>
</body>
</html>

<?php } ?>