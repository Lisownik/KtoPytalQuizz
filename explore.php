<?php
session_start();
// Sprawdź, czy użytkownik jest zalogowany
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Dołącz plik konfiguracyjny bazy danych
require_once("config/db.php");

// Sprawdź połączenie z bazą danych
if (mysqli_connect_errno()) {
    exit('Nie udało się połączyć z bazą danych :( ' . mysqli_connect_error());
}

// Pobierz wyszukiwane zapytanie
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Zapytanie SQL do pobrania quizów z opcjonalnym filtrowaniem
if (!empty($search_query)) {
    $search_term = mysqli_real_escape_string($db, $search_query);
    $quizzes_query = "
        SELECT
            q.quiz_id,
            q.nazwa AS quiz_nazwa,
            q.opis AS quiz_opis,
            q.data_utworzenia,
            COUNT(p.pytanie_id) AS total_questions,
            u.nazwa AS author_name
        FROM Quiz q
        LEFT JOIN Pytanie p ON q.quiz_id = p.quiz_id
        JOIN Uzytkownicy u ON q.user_id = u.user_id
        WHERE q.nazwa LIKE '%$search_term%' 
           OR q.opis LIKE '%$search_term%' 
           OR u.nazwa LIKE '%$search_term%'
        GROUP BY q.quiz_id
        ORDER BY q.data_utworzenia DESC";
} else {
    $quizzes_query = "
        SELECT
            q.quiz_id,
            q.nazwa AS quiz_nazwa,
            q.opis AS quiz_opis,
            q.data_utworzenia,
            COUNT(p.pytanie_id) AS total_questions,
            u.nazwa AS author_name
        FROM Quiz q
        LEFT JOIN Pytanie p ON q.quiz_id = p.quiz_id
        JOIN Uzytkownicy u ON q.user_id = u.user_id
        GROUP BY q.quiz_id
        ORDER BY q.data_utworzenia DESC";
}

$quizzes_result = mysqli_query($db, $quizzes_query);

// Sprawdzenie, czy zapytanie się powiodło
if (!$quizzes_result) {
    die('Błąd zapytania do bazy danych: ' . mysqli_error($db));
}

// Zamknij połączenie z bazą danych po pobraniu danych
mysqli_close($db);
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
    <link rel="stylesheet" href="style/explore.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Quizzes - Kto Pytał</title>
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
    <section class="explore">
        <form class="search-form" method="GET" action="">
            <div class="search-container">
                <input
                        type="text"
                        name="search"
                        id="search-input"
                        placeholder="Search quizzes by name, description, or author..."
                        value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <?php if (!empty($search_query)): ?>
                    <a href="explore.php" class="clear-search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($search_query)): ?>
            <div class="search-info">
                <p>Search results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></p>
                <p class="results-count"><?php echo mysqli_num_rows($quizzes_result); ?> quiz(es) found</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="quizzes">
        <h3><?php echo !empty($search_query) ? 'Search Results' : 'Most Recent Quizzes'; ?></h3>
        <div class="quizzes_container" id="quizzes-container">
            <?php
            if (mysqli_num_rows($quizzes_result) > 0) {
                while ($quiz = mysqli_fetch_assoc($quizzes_result)) {
                    ?>
                    <article class="quiz-card" data-quiz-name="<?php echo htmlspecialchars(strtolower($quiz['quiz_nazwa'])); ?>" data-quiz-description="<?php echo htmlspecialchars(strtolower($quiz['quiz_opis'])); ?>" data-author="<?php echo htmlspecialchars(strtolower($quiz['author_name'])); ?>">
                        <div class="quiz-card__header">
                            <h4 class="quiz-card__title"><?php echo htmlspecialchars($quiz['quiz_nazwa']); ?></h4>
                            <span class="quiz-card__questions"><?php echo $quiz['total_questions']; ?> Questions</span>
                        </div>
                        <p class="quiz-card__description"><?php echo htmlspecialchars(mb_strimwidth($quiz['quiz_opis'], 0, 100, '...')); ?></p>
                        <div class="quiz-card__footer">
                            <div class="quiz-card__author">
                                By: <span><?php echo htmlspecialchars($quiz['author_name']); ?></span>
                            </div>
                            <a href="quiz_details.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-card__button">See More</a>
                        </div>
                    </article>
                    <?php
                }
            } else {
                if (!empty($search_query)) {
                    echo '<div class="no-quizzes-found search-empty">
                            <div class="empty-icon">🔍</div>
                            <h3>No quizzes found</h3>
                            <p>We couldn\'t find any quizzes matching "<strong>' . htmlspecialchars($search_query) . '</strong>"</p>
                            <p>Try searching with different keywords or <a href="explore.php">browse all quizzes</a></p>
                          </div>';
                } else {
                    echo '<div class="no-quizzes-found">
                            <div class="empty-icon">📚</div>
                            <h3>No quizzes available</h3>
                            <p>No quizzes found yet. Check back later!</p>
                          </div>';
                }
            }
            ?>
        </div>

        <!-- Loading indicator for real-time search -->
        <div class="search-loading" id="search-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Searching quizzes...</p>
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
        <p>© <?php echo date('Y'); ?> Kto Pytał. All rights reserved.</p>
    </div>
</footer>

<script src="js/mobile-menu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const searchForm = document.querySelector('.search-form');
        const quizContainer = document.getElementById('quizzes-container');
        const searchLoading = document.getElementById('search-loading');
        const allQuizCards = document.querySelectorAll('.quiz-card');

        let searchTimeout;

        // Real-time search functionality (client-side filtering)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.toLowerCase().trim();

            // Remove any existing client-side "no results" message immediately when input changes
            const existingNoResults = document.querySelector('.client-side-no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            // Show loading indicator
            if (searchTerm.length > 0) {
                searchLoading.style.display = 'block';
            }

            searchTimeout = setTimeout(() => {
                searchLoading.style.display = 'none';

                if (searchTerm.length === 0) {
                    // Show all cards if search is empty - this removes the "no quizzes found" message
                    allQuizCards.forEach(card => {
                        card.style.display = 'block';
                        card.style.animation = 'fadeIn 0.3s ease';
                    });

                    // Make sure to remove any client-side no results message when search is cleared
                    const noResults = document.querySelector('.client-side-no-results');
                    if (noResults) {
                        noResults.remove();
                    }
                    return;
                }

                let visibleCount = 0;

                // Filter quiz cards based on search term
                allQuizCards.forEach(card => {
                    const quizName = card.dataset.quizName || '';
                    const quizDescription = card.dataset.quizDescription || '';
                    const authorName = card.dataset.author || '';

                    const isMatch = quizName.includes(searchTerm) ||
                        quizDescription.includes(searchTerm) ||
                        authorName.includes(searchTerm);

                    if (isMatch) {
                        card.style.display = 'block';
                        card.style.animation = 'fadeIn 0.3s ease';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Show "no results" message only if nothing found and search term exists
                if (visibleCount === 0 && searchTerm.length > 0) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-quizzes-found search-empty client-side-no-results';
                    noResultsDiv.innerHTML = `
                    <div class="empty-icon">🔍</div>
                    <h3>No quizzes found</h3>
                    <p>No quizzes match your search for "<strong>${searchTerm}</strong>"</p>
                    <p>Try different keywords or clear the search to see all quizzes</p>
                `;
                    quizContainer.appendChild(noResultsDiv);
                }

            }, 300); // Debounce for 300ms
        });

        // Handle form submission with Enter key
        searchForm.addEventListener('submit', function(e) {
            // Allow form to submit normally for server-side search
            // This ensures proper URL updates and browser history
        });

        // Add smooth animations
        const style = document.createElement('style');
        style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }

        .quiz-card {
            transition: all 0.3s ease;
        }

        .search-loading {
            text-align: center;
            padding: 2rem;
            color: var(--color-gray-500);
        }

        .loading-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid var(--color-gray-200);
            border-top: 3px solid var(--color-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
        document.head.appendChild(style);
    });
</script>

</body>
</html>

