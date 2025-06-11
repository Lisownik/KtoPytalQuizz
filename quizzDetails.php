<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="quiz details, quiz preview"/>
    <meta name="description" content="Quiz Details - Kto Pytał"/>
    <meta name="author" content="Same sigmy team"/>
    <meta name="robots" content="none"/>
    <link rel="stylesheet" href="style/universal.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/quizzDetails.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Details - Kto Pytał</title>
</head>
<body>

<div class="hamburger">
    <input type="checkbox" id="mobile-menu-toggle">
    <label for="mobile-menu-toggle" class="hamburger-btn">
        <span></span>
        <span></span>
        <span></span>
    </label>
    <div class="mobile-nav-overlay"></div>
    <nav class="mobile-nav">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="quizzCreator.php">Create Quizz</a></li>
            <li><a href="explore.php">Explore</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="history.php">History</a></li>
        </ul>
        <div class="mobile-auth">
            <form method="post" action="php/logout.php">
                <button type="submit">Logout</button>
            </form>
        </div>
    </nav>
</div>

<header>
    <div>
        <a href="index.php"><img src="assets/logo.png" alt="logo mózgu"></a>
        <h2>Kto Pytał</h2>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="quizzCreator.php">Create Quizz</a></li>
            <li><a href="explore.php">Explore</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="history.php">History</a></li>
        </ul>
    </nav>
    <div class="header-auth">
        <form method="post" action="php/logout.php" class="logout-form">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</header>

<main class="quiz-details-main">
    <div class="quiz-details-container">

        <div class="quiz-info-card">
            <div class="quiz-header">
                <h1 class="quiz-title">Web Development Fundamentals</h1>
                <div class="quiz-meta">
                    <span class="quiz-author">By: CodeMaster123</span>
                </div>
            </div>

            <div class="quiz-description">
                <h2>About This Quiz</h2>
                <p>Test your knowledge of web development fundamentals! This comprehensive quiz covers HTML, CSS, JavaScript, and essential web technologies. Perfect for beginners and intermediate developers who want to assess their understanding of core web development concepts.</p>

                <p>Whether you're just starting your journey in web development or looking to refresh your knowledge, this quiz will help you identify areas where you excel and topics that might need more study. The quiz includes practical questions about markup, styling, programming, and best practices.</p>
            </div>

            <div class="quiz-stats">
                <div class="stat-item">
                    <span class="stat-number">15</span>
                    <span class="stat-label">Questions</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">12</span>
                    <span class="stat-label">Minutes</span>
                </div>
            </div>
        </div>

        <div class="quiz-actions">
            <div class="action-buttons">
                <a href="quiz.php?id=1" class="btn btn-primary btn-large">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5,3 19,12 5,21"/>
                    </svg>
                    Rozwiąż Quiz
                </a>

                <a href="explore.php" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m12 19-7-7 7-7"/>
                        <path d="M19 12H5"/>
                    </svg>
                    Cofnij
                </a>
            </div>
        </div>

    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h4>Kto Pytał</h4>
            <p>Making quiz creation and sharing easier than ever. Build engaging quizzes that captivate your audience.</p>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li>About Us</li>
                <li>Features</li>
                <li>Pricing</li>
                <li>Blog</li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Support</h4>
            <ul>
                <li>Help Center</li>
                <li>Contact Us</li>
                <li>Privacy Policy</li>
                <li>Terms of Service</li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Follow Us</h4>
            <ul>
                <li>Facebook</li>
                <li>Twitter</li>
                <li>Instagram</li>
                <li>LinkedIn</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2025 Kto Pytał. All rights reserved.</p>
    </div>
</footer>

<script src="js/mobile-menu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add smooth animations
        const cards = document.querySelectorAll('.quiz-info-card, .quiz-actions'); // Usunięto .additional-info

        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 200);
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });
</script>

</body>
</html>