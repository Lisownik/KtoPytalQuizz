<?php
session_start();
require_once("config/db.php"); // Upewnij się, że ścieżka do db.php jest prawidłowa

// Przekierowanie, jeśli użytkownik nie jest zalogowany
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    header('Location: index.php');
    exit();
}

// Sprawdzenie połączenia z bazą danych
if (mysqli_connect_errno()) {
    exit('Nie udało się połączyć z bazą danych :( ' . mysqli_connect_error());
}

$username = $_SESSION['username'];
$zapytanie = "SELECT user_id, nazwa, email FROM uzytkownicy WHERE nazwa = '$username'";
$wynik = mysqli_query($db, $zapytanie);

$user_data = null;
if ($wynik && mysqli_num_rows($wynik) > 0) {
    $user_data = mysqli_fetch_assoc($wynik);
    // WAŻNE: Zapisz user_id do sesji, aby inne skrypty mogły go używać
    $_SESSION['user_id'] = $user_data['user_id'];
} else {
    // Jeśli danych użytkownika nie ma (np. usunięty), wyloguj
    session_destroy();
    header('Location: index.php');
    exit();
}

// Pobieranie quizów stworzonych przez zalogowanego użytkownika
$user_id = $user_data['user_id']; // Użyj user_id pobranego z bazy danych
$quizzes_query = "
    SELECT
        q.quiz_id,
        q.nazwa,
        q.opis,
        COUNT(p.pytanie_id) AS total_questions
    FROM Quiz q
    LEFT JOIN Pytanie p ON q.quiz_id = p.quiz_id
    WHERE q.user_id = '$user_id'
    GROUP BY q.quiz_id
    ORDER BY q.data_utworzenia DESC";

$quizzes_result = mysqli_query($db, $quizzes_query);

// Liczba quizów stworzonych (do statystyk profilu)
$quizzes_created_count = ($quizzes_result) ? mysqli_num_rows($quizzes_result) : 0;

// Przykładowe statystyki (jeśli nie masz ich w bazie lub chcesz losowe)
$quizzes_created = $quizzes_created_count > 0 ? $quizzes_created_count : rand(8, 25);
$total_plays = rand(500, 2500);

mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="profile, user, quiz platform"/>
    <meta name="description" content="User profile page for Kto Pytał quiz platform"/>
    <meta name="author" content="Same sigmy team"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user_data['nazwa']); ?> | Kto Pytał</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/universal.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/profile.css">
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
            <li><a href="quizzCreator.php">Create Quiz</a></li>
            <li><a href="explore.php">Explore</a></li>
            <li><a href="profile.php" style="background: rgba(255, 255, 255, 0.1);">Profile</a></li>
            <li><a href="history.php">History</a></li>
        </ul>
    </nav>
    <div class="header-auth">
        <form method="post" action="php/logout.php" class="logout-form">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</header>

<main>
    <section class="profile-header">
        <div class="profile-content">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user_data['nazwa'], 0, 2)); ?>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user_data['nazwa']); ?></h1>
                <p class="profile-subtitle">Quiz Creator since <?php echo date('Y'); ?> • <?php echo htmlspecialchars($user_data['email']); ?></p>

                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($quizzes_created); ?></div>
                        <div class="stat-label">Quizzes Created</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($total_plays); ?></div>
                        <div class="stat-label">Total Plays</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="quizzes-section">
        <div class="section-header">
            <h2 class="section-title">My Quizzes</h2>
            <a href="quizzCreator.php" class="create-quiz-btn">
                <span>+</span>
                Create New Quiz
            </a>
        </div>

        <div class="quiz-list">
            <?php if ($quizzes_result && mysqli_num_rows($quizzes_result) > 0) { ?>
                <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)) { ?>
                    <div class="quiz-card">
                        <h3><?php echo htmlspecialchars($quiz['nazwa']); ?></h3>
                        <p><?php echo htmlspecialchars($quiz['opis']); ?></p>
                        <div class="quiz-card-stats">
                            <span>Questions: <?php echo $quiz['total_questions']; ?></span>
                        </div>
                        <div class="quiz-card-actions">
                            <a href="edit_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="edit-quiz-btn">Edit Quiz</a>
                            <button class="delete-quiz-btn" data-quiz-id="<?php echo $quiz['quiz_id']; ?>">Delete Quiz</button>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3 class="empty-title">No quizzes yet</h3>
                    <p class="empty-description">
                        You haven't created any quizzes yet. Start building your first quiz to engage your audience!
                    </p>
                    <a href="quizzCreator.php" class="create-quiz-btn">
                        Create Your First Quiz
                    </a>
                </div>
            <?php } ?>
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
        const deleteButtons = document.querySelectorAll('.delete-quiz-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const quizId = this.dataset.quizId;
                if (confirm('Are you sure you want to delete this quiz? This action cannot be undone.')) {
                    fetch('php/delete_quiz.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'quiz_id=' + quizId
                    })
                        .then(response => {
                            // Sprawdź, czy odpowiedź jest OK (status 200) i czy typ treści to JSON
                            const contentType = response.headers.get("content-type");
                            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                                return response.json();
                            } else {
                                // Jeśli nie jest JSON, zwróć tekst odpowiedzi do debugowania
                                // To pomoże zdiagnozować błędy JSON.parse
                                return response.text().then(text => {
                                    throw new Error('Server response was not JSON or bad status: ' + response.status + ' ' + response.statusText + ' - Response text: ' + text);
                                });
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                alert('Quiz deleted successfully!');
                                // Usuń kartę quizu z DOM po sukcesie
                                this.closest('.quiz-card').remove();
                                // Opcjonalnie: zaktualizuj liczbę quizów w statystykach profilu
                                // Możesz wysłać kolejne zapytanie AJAX po aktualną liczbę lub po prostu zmniejszyć ją na froncie
                            } else {
                                alert('Error deleting quiz: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while trying to delete the quiz. Check console for details: ' + error.message);
                        });
                }
            });
        });
    });
</script>

</body>
</html>