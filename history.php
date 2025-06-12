<?php
session_start();
require_once("config/db.php"); // Potrzebujemy pliku do połączenia z bazą danych

$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false; // Sprawdzamy, czy użytkownik jest zalogowany
$user_id = $zalogowany ? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null) : null; // Jeśli zalogowany, pobieramy jego ID

// Sprawdzenie, czy udało się połączyć z bazą danych
if (mysqli_connect_errno()) {
    exit('Nie udało się połączyć z bazą danych. Spróbuj później. ' . mysqli_connect_error()); // Informujemy, jeśli nie ma połączenia
}

$history_records = []; // Tutaj będziemy trzymać wyniki quizów

if ($zalogowany && $user_id !== null) { // Jeśli użytkownik jest zalogowany i ma ID
    // Pobieramy historię quizów dla tego użytkownika
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

    $stmt = mysqli_prepare($db, $query); // Przygotowujemy zapytanie

    if ($stmt) { // Jeśli zapytanie jest gotowe
        mysqli_stmt_bind_param($stmt, 'i', $user_id); // Wiążemy ID użytkownika z zapytaniem
        mysqli_stmt_execute($stmt); // Wykonujemy zapytanie
        $result = mysqli_stmt_get_result($stmt); // Pobieramy wyniki

        if ($result) { // Jeśli są wyniki
            while ($row = mysqli_fetch_assoc($result)) { // Przechodzimy przez każdy wynik
                $history_records[] = $row; // Dodajemy go do naszej listy
            }
            mysqli_free_result($result); // Zwalniamy pamięć
        } else {
            error_log("Błąd pobierania wyników quizów: " . mysqli_error($db)); // Logujemy błąd
        }
        mysqli_stmt_close($stmt); // Zamykamy zapytanie
    } else {
        error_log("Błąd przygotowania zapytania SQL dla historii: " . mysqli_error($db)); // Logujemy błąd przygotowania zapytania
    }
}

mysqli_close($db); // Zamykamy połączenie z bazą danych
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="historia quizów, wyniki, statystyki"/>
	<meta name="description" content="Tutaj znajdziesz swoje dawne quizy i wyniki"/>
	<meta name="author" content="Ekipa Same sigmy"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Moje quizy - Kto Pytał</title>

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
			<h2 id="auth-heading">Zaloguj się</h2>
			<form method="post" action="php/login.php">
				<label for="lusername">Nazwa użytkownika</label>
				<input type="text" id="lusername" placeholder="Wpisz nazwę" name="username" required>
				<label for="lpassword">Hasło</label>
				<input type="password" id="lpassword" placeholder="Wpisz hasło" name="password" required>
				<button type="submit" class="btn btn-primary">Zaloguj się</button>
			</form>
		</div>

		<div class="sign" id="register">
			<h2 id="auth-heading">Załóż konto</h2>
			<form method="post" action="php/register.php" id="registerForm">
				<label for="rusername">Nazwa użytkownika</label>
				<input type="text" id="rusername" placeholder="Wpisz nazwę" name="username" required>
				<label for="rmail">E-mail</label>
				<input type="email" id="rmail" placeholder="Wpisz e-mail" name="email" required>
				<label for="rpassword">Hasło</label>
				<input type="password" id="rpassword" placeholder="Wpisz hasło" name="password" required>

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

				<label for="rpasswordconfirm">Powtórz hasło</label>
				<input type="password" id="rpasswordconfirm" placeholder="Powtórz hasło" required>
				<div class="password-match-message" id="password-match-message"></div>
				<button type="submit" class="btn btn-primary">Zarejestruj się</button>
			</form>
		</div>

		<p id="toggle-auth" aria-live="polite" role="status">
			<a href="#" id="toggle-link">Nie masz konta? Załóż je</a>
		</p>
	</div>
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
            <li><a href="index.php">Strona główna</a></li>
            <li><a href="quizzCreator.php">Stwórz Quiz</a></li>
            <li><a href="explore.php">Odkrywaj</a></li>
            <li><a href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
                <li><a  id="selected-page" href="history.php">Historia</a></li>
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
            <li><a  href="index.php">Strona główna</a></li>
            <li><a href="quizzCreator.php">Stwórz Quiz</a></li>
            <li><a href="explore.php">Odkrywaj</a></li>
            <li><a href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
                <li><a id="selected-page" href="history.php">Historia</a></li>
                <li><a href="profile.php">Profil</a></li>
            <?php endif; ?>
		</ul>
        <?php if ($zalogowany): ?>
            <div class="mobile-auth">
                <form method="post" action="php/logout.php">
                    <button type="submit">Wyloguj się</button>
                </form>
            </div>
        <?php endif; ?>
    </nav>
</div>


<main class="historia-container">
	<div class="historia-header">
		<h1 class="historia-title">Moje quizy</h1>
		<p class="historia-subtitle">Sprawdź, jak Ci poszło w quizach</p>
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
				<h3 class="empty-title">Nie masz jeszcze żadnych quizów</h3>
				<p class="empty-description">
					Zacznij rozwiązywać quizy, żeby tu coś zobaczyć!
				</p>
				<a href="explore.php" class="btn-explore">🔍 Szukaj quizów</a>
			</div>
        <?php else: ?>
			<div class="empty-state">
				<div class="empty-icon">🔒</div>
				<h3 class="empty-title">Zaloguj się, żeby zobaczyć swoje quizy</h3>
				<p class="empty-description">
					Zaloguj się, żeby śledzić swoje wyniki i postępy.
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
			<p>Robimy, że tworzenie i dzielenie się quizami jest bardzo łatwe. Rób ciekawe quizy, które się spodobają.</p>
		</div>
		<div class="footer-section">
			<h4>Szybkie linki</h4>
			<ul>
				<li>O nas</li>
				<li>Co umiemy</li>
				<li>Ceny</li>
				<li>Nasze artykuły</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Pomoc</h4>
			<ul>
				<li>Pomoc</li>
				<li>Napisz do nas</li>
				<li>Zasady prywatności</li>
				<li>Zasady korzystania</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Bądź z nami</h4>
			<ul>
				<li>Facebook</li>
				<li>Twitter</li>
				<li>Instagram</li>
				<li>LinkedIn</li>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<p>&copy; <?php echo date('Y'); ?> Kto Pytał. Wszystkie prawa zastrzeżone.</p>
	</div>
</footer>

<script src="js/auth.js"></script>
<script src="js/mobile-menu.js"></script>
<script src="js/password-validation.js"></script>
<script src="js/requirements-visibility.js"></script>
<script>
    // Dodatkowa obsługa otwierania okienka logowania dla niezalogowanych
    document.getElementById('open-login-modal').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('auth-modal-backdrop').setAttribute('aria-hidden', 'false');
        document.getElementById('auth-modal').style.display = 'block';
    });
</script>
</body>
</html>