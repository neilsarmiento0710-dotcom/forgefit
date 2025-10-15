<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔹 Include database connection with error checking
$db_path = '../database/db.php';

if (!file_exists($db_path)) {
    die("Error: Database connection file not found at: " . realpath(dirname(__FILE__) . '/' . $db_path));
}

include $db_path;

// 🔹 Verify connection exists
if (!isset($conn) || $conn === null) {
    die("Error: Database connection (\$conn) is not defined. Please check your db.php file.");
}

if (isset($_POST['submit'])) {
    // handle form submission if needed
} else {
    // Fetch trainers from database
    $sql = "SELECT * FROM trainers";
    $result = $conn->query($sql);

    if ($result === false) {
        die("Database query failed: " . $conn->error);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Gym - Our Expert Trainers" />
    <meta name="keywords" content="gym, fitness, training, workout, health, trainers" />
    <meta name="author" content="Sniper 2025" />
    <title>Schedule - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4" id="main-style-link" />
    
    <style>
        main {
    margin-top: 100px;
    padding: 40px 20px;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    min-height: calc(100vh - 300px);
    }

        /* Additional styles for trainers page */
        .trainers-hero {
        background: linear-gradient(135deg, #003366 0%, #001d3d 100%);
        padding: 60px 40px;
        border-radius: 20px;
        color: white;
        margin-bottom: 40px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 51, 102, 0.4);
    }
        
        .trainers-hero h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .trainers-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .trainer-list {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .trainer-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        
        .trainer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .trainer-card h3 {
            font-size: 1.8rem;
            color: #0f172a;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .trainer-card p {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 20px;
        }
        
        .trainer-card .cta-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .trainer-card .cta-btn:hover {
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
            transform: scale(1.05);
        }
        
        .no-trainers {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="clients.php">My Clients</a></li>
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
            </ul>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
    <main>
    <!-- Trainers Hero Section -->
    <div class="trainers-hero">
            <h1>Schedule</h1>
            <p></p>
    </div>

    <!-- Trainers List Section -->
    <section>
        <div class="trainer-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($trainer = $result->fetch_assoc()): ?>
                    <div class="trainer-card">
                        <div class="feature-icon">💪</div>
                        <h3><?php echo htmlspecialchars($trainer['name']); ?></h3>
                        <p><strong>Specialty:</strong> <?php echo htmlspecialchars($trainer['specialty']); ?></p>
                        <a href="book.php?trainer_id=<?php echo $trainer['id']; ?>" class="cta-btn">Book Session</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-trainers">
                    <p>No trainers available at the moment. Please check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    </main>

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
    <script src="../assets/js/plugins/simplebar.min.js"></script>
    <script src="../assets/js/plugins/popper.min.js"></script>
    <script src="../assets/js/icon/custom-icon.js"></script>
    <script src="../assets/js/plugins/feather.min.js"></script>
    <script src="../assets/js/component.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/script.js"></script>

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
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
            }
        });
    </script>
</body>
</html>
<?php
    // Close connection
    $conn->close();
} // end else
?>