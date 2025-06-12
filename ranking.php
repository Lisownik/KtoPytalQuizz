<?php
session_start();
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Symulacja danych rankingowych
// W rzeczywistości te dane pochodziłyby z bazy danych
$ranking_data = [
    ['position' => 1, 'username' => 'ProPlayer77', 'score' => 1500, 'quizzes_completed' => 120],
    ['position' => 2, 'username' => 'QuizMasterBen', 'score' => 1450, 'quizzes_completed' => 115],
    ['position' => 3, 'username' => 'SmartLearner', 'score' => 1380, 'quizzes_completed' => 100],
    ['position' => 4, 'username' => 'CodeWhiz', 'score' => 1320, 'quizzes_completed' => 95],
    ['position' => 5, 'username' => 'BrainyBee', 'score' => 1280, 'quizzes_completed' => 90],
    ['position' => 6, 'username' => 'KnowledgeKing', 'score' => 1200, 'quizzes_completed' => 85],
    ['position' => 7, 'username' => 'QuizzyRider', 'score' => 1150, 'quizzes_completed' => 80],
    ['position' => 8, 'username' => 'FactFinder', 'score' => 1080, 'quizzes_completed' => 75],
    ['position' => 9, 'username' => 'MindBender', 'score' => 1020, 'quizzes_completed' => 70],
    ['position' => 10, 'username' => 'TriviaGuru', 'score' => 950, 'quizzes_completed' => 65],
];

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="ranking, leaderboard, quiz scores"/>
    <meta name="description" content="Global leaderboard for Kto Pytał quizzes"/>
    <meta name="author" content="Same sigmy team"/>
    <meta name="robots" content="none"/>
    <link rel="stylesheet" href="style/universal.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/ranking.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Just+Another+Hand&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - Kto Pytał</title>
</head>
<body>

<div id="toast-container"></div>

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
            <li><a href="index.php">Strona główna</a></li>
            <li><a href="quizzCreator.php">Stwórz Quiz</a></li>
            <li><a href="explore.php">Odkrywaj</a></li>
            <li><a id="selected-page" href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
                <li><a href="history.php">Historia</a></li>
                <li><a href="profile.php">Profil</a></li>
            <?php endif; ?>
        </ul>
        <div class="mobile-auth">
            <?php if ($zalogowany): ?>
                <form method="post" action="php/logout.php">
                    <button type="submit">Wyloguj się</button>
                </form>
            <?php else: ?>
                <a href="#" class="mobile-login-btn">Zaloguj się</a>
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
            <li><a href="index.php">Strona główna</a></li>
            <li><a href="quizzCreator.php">Stwórz Quiz</a></li>
            <li><a href="explore.php">Odkrywaj</a></li>
            <li><a id="selected-page" href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
                <li><a href="history.php">Historia</a></li>
                <li><a href="profile.php">Profil</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="header-auth">
        <?php if ($zalogowany): ?>
            <form method="post" action="php/logout.php" class="logout-form">
                <button type="submit" class="logout-btn">Wyloguj się</button>
            </form>
        <?php else: ?>
            <a href="#" id="open-login" class="signin-link">Zaloguj się</a>
        <?php endif; ?>
    </div>
</header>

<main class="ranking-main">
    <section class="ranking-section">
        <h1>Globalny Ranking Graczy</h1>
        <p class="ranking-description">Sprawdź, kto jest najlepszy w quizach "Kto Pytał"!</p>

        <div class="ranking-table-container">
            <table class="ranking-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nazwa Użytkownika</th>
                    <th>Wynik</th>
                    <th>Ukończone Quizy</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($ranking_data)): ?>
                    <?php foreach ($ranking_data as $player): ?>
                        <tr class="<?php echo ($zalogowany && $_SESSION['username'] === $player['username']) ? 'current-user-row' : ''; ?>">
                            <td class="ranking-position"><?php echo htmlspecialchars($player['position']); ?></td>
                            <td class="ranking-username">
                                <span class="username-text"><?php echo htmlspecialchars($player['username']); ?></span>
                                <?php if ($zalogowany && $_SESSION['username'] === $player['username']): ?>
                                    <span class="you-label">(Ty)</span>
                                <?php endif; ?>
                            </td>
                            <td class="ranking-score"><?php echo htmlspecialchars($player['score']); ?></td>
                            <td class="ranking-completed"><?php echo htmlspecialchars($player['quizzes_completed']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Brak danych w rankingu.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
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
    // Funkcje toastów (wklej to na początku swojego głównego skryptu JS lub w common.js, jeśli go masz)
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