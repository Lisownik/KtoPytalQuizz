<?php
session_start();
require_once("config/db.php"); // Załóż, że masz plik db.php z połączeniem do bazy

$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
$user_id = $zalogowany ? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null) : null;

// Sprawdzenie połączenia z bazą danych
if (mysqli_connect_errno()) {
    exit('Nie udało się połączyć z bazą danych :( ' . mysqli_connect_error());
}

$history_records = []; // Tablica do przechowywania wyników quizów

if ($zalogowany && $user_id !== null) {
    // Zapytanie SQL do pobrania historii quizów dla danego użytkownika
    // Łączymy wyniki_quizow z tabelą quiz, aby uzyskać nazwę quizu
    $query = "
        SELECT
            wq.wynik_id,
            q.nazwa AS quiz_nazwa,
            wq.wynik_liczbowy,
            wq.maksymalny_wynik
        FROM
            wyniki_quizow wq
        JOIN
            quiz q ON wq.quiz_id = q.quiz_id
        WHERE
            wq.user_id = ?
        ORDER BY
            wq.data_rozwiazania DESC;
    ";

    $stmt = mysqli_prepare($db, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $history_records[] = $row;
            }
            mysqli_free_result($result);
        } else {
            error_log("Błąd pobierania wyników quizów: " . mysqli_error($db));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Błąd przygotowania zapytania SQL dla historii: " . mysqli_error($db));
    }
}

mysqli_close($db); // Zamknij połączenie z bazą danych na końcu skryptu
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="historia quizów, wyniki, statystyki"/>
	<meta name="description" content="Historia rozwiązanych quizów i wyniki użytkownika"/>
	<meta name="author" content="Same sigmy team"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Historia Quizów - Kto Pytał</title>

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">
	<link rel="stylesheet" href="style/history.css">
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
            <?php if ($zalogowany): ?>
				<li><a href="profile.php">Profile</a></li>
				<li><a href="history.php">History</a></li>
            <?php else: ?>
				<li><a href="#" class="mobile-login-btn">Sign In</a></li>
            <?php endif; ?>
		</ul>
        <?php if ($zalogowany): ?>
			<div class="mobile-auth">
				<form method="post" action="php/logout.php">
					<button type="submit">Logout</button>
				</form>
			</div>
        <?php endif; ?>
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
            <?php if ($zalogowany): ?>
				<li><a href="profile.php">Profile</a></li>
				<li><a href="history.php">History</a></li>
            <?php endif; ?>
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

<main class="historia-container">
	<div class="historia-header">
		<h1 class="historia-title">Historia Quizów</h1>
		<p class="historia-subtitle">Twoje wyniki i postępy w rozwiązywaniu quizów</p>
	</div>

	<div class="quiz-history">
        <?php if ($zalogowany && !empty($history_records)): ?>
            <?php foreach ($history_records as $record): ?>
				<div class="quiz-item">
					<div class="quiz-name"><?php echo htmlspecialchars($record['quiz_nazwa']); ?></div>
					<div class="quiz-score"><?php echo htmlspecialchars($record['wynik_liczbowy']) . '/' . htmlspecialchars($record['maksymalny_wynik']); ?></div>
				</div>
            <?php endforeach; ?>
        <?php elseif ($zalogowany && empty($history_records)): ?>
			<div class="empty-state">
				<div class="empty-icon">📚</div>
				<h3 class="empty-title">Brak historii quizów</h3>
				<p class="empty-description">
					Nie rozwiązałeś jeszcze żadnego quizu. Zacznij swoją przygodę z nauką już teraz!
				</p>
				<a href="explore.php" class="btn-explore">🔍 Przeglądaj Quizy</a>
			</div>
        <?php else: ?>
			<div class="empty-state">
				<div class="empty-icon">🔒</div>
				<h3 class="empty-title">Zaloguj się, aby zobaczyć historię quizów</h3>
				<p class="empty-description">
					Zaloguj się na swoje konto, aby śledzić swoje postępy i wyniki.
				</p>
				<a href="#" id="open-login-modal" class="btn-explore mobile-login-btn">Zaloguj się</a>
			</div>
        <?php endif; ?>
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
		<p>&copy; <?php echo date('Y'); ?> Kto Pytał. All rights reserved.</p>
	</div>
</footer>

<script src="js/auth.js"></script>
<script src="js/mobile-menu.js"></script>
<script src="js/password-validation.js"></script>
<script src="js/requirements-visibility.js"></script>
<script>
    // Dodatkowa obsługa otwierania modala logowania z sekcji historii dla niezalogowanych
    document.getElementById('open-login-modal').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('auth-modal-backdrop').setAttribute('aria-hidden', 'false');
        document.getElementById('auth-modal').style.display = 'block';
    });
</script>
</body>
</html>