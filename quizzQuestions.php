<?php
session_start();
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="quiz, pytania, odpowiedzi"/>
    <meta name="description" content="Rozwiąż quiz i sprawdź swoją wiedzę"/>
    <meta name="author" content="Same sigmy team"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - Historia Polski - Kto Pytał</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/universal.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/quizzQuestions.css">
</head>
<body>

<div id="auth-modal-backdrop" aria-hidden="true">
    <div id="auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-heading">
        <div class="sign" id="log_in">
            <h2 id="auth-heading">Log in</h2>
            <form method="post" action="php/login.php">
                <label for="lusername">Username</label>
                <input type="text" id="lusername" placeholder="Enter username" name="username" required>
                <label for="lpassword">Password</label>
                <input type="password" id="lpassword" placeholder="Enter password" name="password" required>
                <button type="submit" class="btn btn-primary">Log in</button>
            </form>
        </div>

        <div class="sign" id="register">
            <h2 id="auth-heading">Sign up</h2>
            <form method="post" action="php/register.php" id="registerForm">
                <label for="rusername">Username</label>
                <input type="text" id="rusername" placeholder="Enter username" name="username" required>
                <label for="rmail">E-mail</label>
                <input type="email" id="rmail" placeholder="Enter email" name="email" required>
                <label for="rpassword">Password</label>
                <input type="password" id="rpassword" placeholder="Enter password" name="password" required>

                <div class="password-requirements" id="passwordRequirements">
                    <div class="requirement invalid" id="req-length">
                        <span class="requirement-icon">✗</span>
                        <span>Minimum 8 znaków</span>
                    </div>
                    <div class="requirement invalid" id="req-uppercase">
                        <span class="requirement-icon">✗</span>
                        <span>Co najmniej jedna duża litera (A-Z)</span>
                    </div>
                    <div class="requirement invalid" id="req-lowercase">
                        <span class="requirement-icon">✗</span>
                        <span>Co najmniej jedna mała litera (a-z)</span>
                    </div>
                    <div class="requirement invalid" id="req-digit">
                        <span class="requirement-icon">✗</span>
                        <span>Co najmniej 3 cyfry (0-9)</span>
                    </div>
                    <div class="requirement invalid" id="req-special">
                        <span class="requirement-icon">✗</span>
                        <span>Co najmniej jeden znak specjalny (!@#$%^&*)</span>
                    </div>
                </div>

                <label for="rpasswordconfirm">Repeat Password</label>
                <input type="password" id="rpasswordconfirm" placeholder="Repeat password" required>
                <div class="password-match-message" id="password-match-message"></div>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
        </div>

        <p id="toggle-auth" aria-live="polite" role="status">
            <a href="#" id="toggle-link">Don't have an account? Sign up</a>
        </p>
    </div>
</div>

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
            <li><a href="history.php">Profile</a></li>
        </ul>
        <div class="mobile-auth">
            <?php if ($zalogowany): ?>
                <form method="post" action="php/logout.php">
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="#" class="mobile-login-btn">Sign In</a>
            <?php endif; ?>
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
            <li><a href="history.php">Hisotry</a></li>

            <li><a href="quizzQuestions.php">quizz</a></li>
        </ul>
    </nav>
    <div class="header-auth">
        <?php if ($zalogowany): ?>
            <form method="post" action="php/logout.php" class="logout-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        <?php else: ?>
            <a href="#" id="open-login" class="signin-link">Sign In</a>
        <?php endif; ?>
    </div>
</header>

<main class="quiz-container">
    <div class="quiz-header">
        <div class="quiz-info">
            <h1 class="quiz-title">Historia Polski - Średniowiecze</h1>
            <div class="quiz-meta">
                <span class="quiz-progress">Pytanie 3 z 10</span>
            </div>
        </div>
    </div>

    <div class="question-section">
        <div class="question-card">
            <h2 class="question-text">
                Kto był pierwszym królem Polski koronowanym w 1025 roku?
            </h2>
        </div>

        <form class="answers-form">
            <div class="answer-option">
                <input type="radio" id="answer-a" name="quiz-answer" value="a">
                <label for="answer-a" class="answer-label">
                    <span class="answer-letter">A</span>
                    <span class="answer-text">Mieszko I</span>
                </label>
            </div>

            <div class="answer-option">
                <input type="radio" id="answer-b" name="quiz-answer" value="b">
                <label for="answer-b" class="answer-label">
                    <span class="answer-letter">B</span>
                    <span class="answer-text">Bolesław Chrobry</span>
                </label>
            </div>

            <div class="answer-option">
                <input type="radio" id="answer-c" name="quiz-answer" value="c">
                <label for="answer-c" class="answer-label">
                    <span class="answer-letter">C</span>
                    <span class="answer-text">Kazimierz Wielki</span>
                </label>
            </div>

            <div class="answer-option">
                <input type="radio" id="answer-d" name="quiz-answer" value="d">
                <label for="answer-d" class="answer-label">
                    <span class="answer-letter">D</span>
                    <span class="answer-text">Władysław Jagiełło</span>
                </label>
            </div>
        </form>

        <div class="quiz-actions">
            <button type="button" class="btn-previous">← Poprzednie</button>
            <button type="button" class="btn-next">Następne →</button>
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
        <p>&copy; 2024 Kto Pytał. All rights reserved.</p>
    </div>
</footer>

</body>
</html>