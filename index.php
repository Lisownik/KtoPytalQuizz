<?php
session_start();
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="keywords" content="explore, quizzes, search"/>
        <meta name="description" content="Explore and discover amazing quizzes"/>
        <meta name="author" content="Same sigmy team"/>
        <meta name="robots" content="none"/>
        <link rel="stylesheet" href="style/universal.css">
        <link rel="stylesheet" href="style/style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Just+Another+Hand&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kto Pytał - Quiz Platform</title>
    </head>
    <body>

    <div id="toast-container"></div>

    <?php /*
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error" style="background-color: #f8d7da; color: #721c24; padding: 12px; margin: 20px 0; border-radius: 4px; border: 1px solid #f5c6cb;">
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; margin: 20px 0; border-radius: 4px; border: 1px solid #c3e6cb;">
        <?php
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>
*/ ?>


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
                <li><a href="history.php">History</a></li>
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
                <li><a href="history.php">History</a></li>
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

    <main>
        <section id="first">
            <div>
                <h1>Create and Share Interactive Quizzes</h1>
                <p>Make learning fun and engaging with our easy-to-use quiz platform. Perfect for educators, trainers, and quiz enthusiasts.</p>
                <div>
                    <button>Create Quiz</button>
                    <button>Take Quiz</button>
                </div>
            </div>
            <img src="assets/Szescian.png" alt="">
        </section>
        <section id="second">
            <h3>Popular Today</h3>
            <article></article>
        </section>
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
            <p>&copy; <?php echo date('Y'); ?> Kto Pytał. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/auth.js"></script>
    <script src="js/password-validation.js"></script>
    <script src="js/mobile-menu.js"></script>
    <script src="js/requirements-visibility.js"></script>
    <script>
        // Funkcje toastów (wklej to na początku swojego głównego skryptu JS lub w oddzielnym pliku, jeśli preferujesz)
        function showToast(message, type = 'info', duration = 5000) {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                console.error('Toast container not found!');
                return;
            }

            const toast = document.createElement('div');
            toast.classList.add('toast', type);
            toast.innerHTML = `
            <div class="toast-message">${message}</div>
            <button class="toast-close-btn">&times;</button>
        `;

            toastContainer.appendChild(toast);

            // Force reflow to enable transition
            void toast.offsetWidth;

            toast.classList.add('show');

            const closeBtn = toast.querySelector('.toast-close-btn');
            closeBtn.addEventListener('click', () => {
                hideToast(toast);
            });

            if (duration > 0) {
                setTimeout(() => {
                    hideToast(toast);
                }, duration);
            }
        }

        function hideToast(toast) {
            toast.classList.remove('show');
            toast.classList.add('hide');

            toast.addEventListener('transitionend', () => {
                toast.remove();
            }, { once: true }); // Remove listener after it fires once
        }


        // Sprawdzanie i wyświetlanie toastów po załadowaniu strony
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['error'])): ?>
            showToast('<?php echo addslashes($_SESSION['error']); ?>', 'error');
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success');
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        });
    </script>

    </body>
    </html>