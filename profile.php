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
        COUNT(p.pytanie_id) AS total_questions,
        (SELECT COUNT(*) FROM polubione_quizy WHERE quiz_id = q.quiz_id) AS likes_count
    FROM Quiz q
    LEFT JOIN Pytanie p ON q.quiz_id = p.quiz_id
    WHERE q.user_id = '$user_id'
    GROUP BY q.quiz_id, q.nazwa, q.opis
    ORDER BY q.data_utworzenia DESC";

$quizzes_result = mysqli_query($db, $quizzes_query);

// Liczba quizów stworzonych (do statystyk profilu)
$quizzes_created_count = ($quizzes_result) ? mysqli_num_rows($quizzes_result) : 0;


// NOWOŚĆ: Obliczanie sumy wszystkich polubień dla quizów stworzonych przez tego użytkownika
$total_likes_on_my_quizzes = 0;
// Musimy zresetować wskaźnik wyniku lub wykonać nowe zapytanie, jeśli $quizzes_result był już przetworzony
// Dla prostoty, jeśli potrzebujemy sumy po przetworzeniu $quizzes_result, możemy to zrobić w pętli.
// Alternatywnie, wykonamy oddzielne zapytanie SUM()
$stmt_total_likes_my_quizzes = $db->prepare("
    SELECT SUM(likes_count_sub.count) AS total_likes_on_my_quizzes
    FROM (
        SELECT (SELECT COUNT(*) FROM polubione_quizy WHERE quiz_id = q.quiz_id) AS count
        FROM Quiz q
        WHERE q.user_id = ?
    ) AS likes_count_sub
");
if ($stmt_total_likes_my_quizzes) {
    $stmt_total_likes_my_quizzes->bind_param("i", $user_id);
    $stmt_total_likes_my_quizzes->execute();
    $stmt_total_likes_my_quizzes->bind_result($sum_likes);
    $stmt_total_likes_my_quizzes->fetch();
    $total_likes_on_my_quizzes = $sum_likes ?: 0; // Użyj 0 jeśli suma jest NULL (brak polubień)
    $stmt_total_likes_my_quizzes->close();
}


// Pobieranie quizów polubionych przez zalogowanego użytkownika
$liked_quizzes_query = "
    SELECT
        q.quiz_id,
        q.nazwa,
        q.opis,
        COUNT(p.pytanie_id) AS total_questions,
        u_author.Nazwa AS author_name,
        (SELECT COUNT(*) FROM polubione_quizy WHERE quiz_id = q.quiz_id) AS likes_count
    FROM polubione_quizy lq
    JOIN quiz q ON lq.quiz_id = q.quiz_id
    LEFT JOIN pytanie p ON q.quiz_id = p.quiz_id
    JOIN uzytkownicy u_author ON q.user_id = u_author.user_id
    WHERE lq.user_id = '$user_id'
    GROUP BY q.quiz_id, q.nazwa, q.opis, u_author.Nazwa
    ORDER BY lq.data_polubienia DESC";

$liked_quizzes_result = mysqli_query($db, $liked_quizzes_query);

// Liczba polubionych quizów (do statystyk profilu)
$liked_quizzes_count = ($liked_quizzes_result) ? mysqli_num_rows($liked_quizzes_result) : 0;


// Przykładowe statystyki (jeśli nie masz ich w bazie lub chcesz losowe)
$quizzes_created = $quizzes_created_count > 0 ? $quizzes_created_count : rand(8, 25);
// Usunięto $total_plays

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
	<style>
        /* Styl dla klikalnego tytułu quizu */
        .quiz-card h3 a {
            text-decoration: none; /* Usuwa podkreślenie linku */
            color: inherit; /* Dziedziczy kolor tekstu z rodzica */
            transition: color 0.2s ease;
        }

        .quiz-card h3 a:hover {
            color: #007bff; /* Subtelna zmiana koloru przy najechaniu myszką */
        }

        /* Styl dla sekcji statystyk quizu w karcie */
        .quiz-card-stats {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap; /* Pozwala elementom zawijać się na mniejszych ekranach */
            gap: 15px; /* Odstęp między elementami statystyk */
            align-items: center;
            font-size: 0.9em;
            color: #555;
        }

        /* Styl dla licznika polubień */
        .likes-count {
            display: flex;
            align-items: center;
            gap: 5px; /* Odstęp między ikoną serca a liczbą */
        }

        .likes-count svg {
            width: 16px; /* Rozmiar ikony serca */
            height: 16px;
            fill: #e0245e; /* Czerwony kolor serca */
            stroke: #e0245e;
            vertical-align: middle;
        }

        /* Upewnij się, że przyciski akcji zachowują swój styl */
        .quiz-card-actions {
            margin-top: 15px; /* Dodaje trochę przestrzeni nad przyciskami akcji */
            display: flex;
            gap: 10px; /* Odstęp między przyciskami */
            flex-wrap: wrap; /* Pozwala przyciskom zawijać się na mniejszych ekranach */
        }

	</style>
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
            <li><a href="history.php">History</a></li>
            <li><a id="selected-page" href="profile.php">Profile</a></li>
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
        <a href="index.php">
            <img src="assets/logo.png" alt="logo mózgu">
            <h2>Kto Pytał</h2>
        </a>
	</div>
	<nav>
		<ul>
			<li><a href="index.php">Home</a></li>
			<li><a href="quizzCreator.php">Create Quizz</a></li>
			<li><a href="explore.php">Explore</a></li>
            <li><a href="history.php">History</a></li>
            <li><a id="selected-page" href="profile.php">Profile</a></li>
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
						<div class="stat-number"><?php echo number_format($total_likes_on_my_quizzes); ?></div>
						<div class="stat-label">Total Likes on My Quizzes</div>
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
            <?php
            // Ważne: Przewiń wynik zapytania, jeśli był już użyty (np. do count)
            // Jeśli quizzes_result został już przetworzony przez mysqli_num_rows,
            // to jego wskaźnik jest na końcu. Musimy go zresetować.
            if ($quizzes_result && mysqli_num_rows($quizzes_result) > 0) {
                mysqli_data_seek($quizzes_result, 0); // Resetuje wskaźnik na początek
                while ($quiz = mysqli_fetch_assoc($quizzes_result)) { ?>
					<div class="quiz-card">
						<h3><a href="quizzDetails.php?id=<?php echo $quiz['quiz_id']; ?>"><?php echo htmlspecialchars($quiz['nazwa']); ?></a></h3>
						<p><?php echo htmlspecialchars($quiz['opis']); ?></p>
						<div class="quiz-card-stats">
							<span>Questions: <?php echo $quiz['total_questions']; ?></span>
							<span class="likes-count">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                <?php echo $quiz['likes_count']; ?>
                            </span>
						</div>
						<div class="quiz-card-actions">
							<a href="edit_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="edit-quiz-btn">Edit Quiz</a>
							<button class="delete-quiz-btn" data-quiz-id="<?php echo $quiz['quiz_id']; ?>">Delete Quiz</button>
						</div>
					</div>
                <?php }
            } else { ?>
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

	<section class="quizzes-section">
		<div class="section-header">
			<h2 class="section-title">Liked Quizzes</h2>
		</div>

		<div class="quiz-list">
            <?php if ($liked_quizzes_result && mysqli_num_rows($liked_quizzes_result) > 0) { ?>
                <?php while ($liked_quiz = mysqli_fetch_assoc($liked_quizzes_result)) { ?>
					<div class="quiz-card">
						<h3><a href="quizzDetails.php?id=<?php echo $liked_quiz['quiz_id']; ?>"><?php echo htmlspecialchars($liked_quiz['nazwa']); ?></a></h3>
						<p><?php echo htmlspecialchars($liked_quiz['opis']); ?></p>
						<div class="quiz-card-stats">
							<span>Questions: <?php echo $liked_quiz['total_questions']; ?></span>
							<span>Author: <?php echo htmlspecialchars($liked_quiz['author_name']); ?></span>
							<span class="likes-count">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                <?php echo $liked_quiz['likes_count']; ?>
                            </span>
						</div>
						<div class="quiz-card-actions">
						</div>
					</div>
                <?php } ?>
            <?php } else { ?>
				<div class="empty-state">
					<div class="empty-icon">❤️</div>
					<h3 class="empty-title">No liked quizzes yet</h3>
					<p class="empty-description">
						You haven't liked any quizzes yet. Explore quizzes and find some you love!
					</p>
					<a href="explore.php" class="create-quiz-btn">
						Explore Quizzes
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
                            const contentType = response.headers.get("content-type");
                            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                                return response.json();
                            } else {
                                return response.text().then(text => {
                                    throw new Error('Server response was not JSON or bad status: ' + response.status + ' ' + response.statusText + ' - Response text: ' + text);
                                });
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                alert('Quiz deleted successfully!');
                                this.closest('.quiz-card').remove();
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