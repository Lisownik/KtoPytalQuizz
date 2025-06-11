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

// Zapytanie SQL do pobrania quizów
// Pobieramy quizy, ich opis, liczbę pytań, nazwę autora i ID quizu
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
    ORDER BY q.data_utworzenia DESC"; // Sortuj od najnowszych

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
            <?php if ($zalogowany): ?>
                <li><a href="profile.php">Profile</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
        <div class="mobile-auth">
            <?php if ($zalogowany): ?>
                <form method="post" action="php/logout.php">
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn">Register</a>
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
            <li><a href="quizzCreator.php">Create Quiz</a></li>
            <li><a href="explore.php" style="background: rgba(255, 255, 255, 0.1);">Explore</a></li>
            <?php if ($zalogowany): ?>
                <li><a href="profile.php">Profile</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="header-auth">
        <?php if ($zalogowany): ?>
            <form method="post" action="php/logout.php" class="logout-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        <?php else: ?>
            <a href="login.php" class="btn">Login</a>
            <a href="register.php" class="btn">Register</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <section class="explore">
        <input type="text" placeholder="Search quizzes...">
        <button type="submit">Search</button>
    </section>

    <section class="quizzes">
        <h3>Most Recent Quizzes</h3>
        <div class="quizzes_container">
            <?php
            if (mysqli_num_rows($quizzes_result) > 0) {
                while ($quiz = mysqli_fetch_assoc($quizzes_result)) {
                    ?>
                    <article class="quiz-card">
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
                echo '<p class="no-quizzes-found">No quizzes found yet. Check back later!</p>';
            }
            ?>
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

<script src="js/mobile-menu.js"></script>

</body>
</html>