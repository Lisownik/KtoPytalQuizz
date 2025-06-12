<?php
session_start();
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="przeglądaj, quizy, szukaj"/>
	<meta name="description" content="Przeglądaj i odkrywaj niesamowite quizy"/>
	<meta name="author" content="Zespół Same sigmy"/>
	<meta name="robots" content="none"/>
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Just+Another+Hand&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kto Pytał - Platforma Quizowa</title>
</head>
<body>

<div id="auth-modal-backdrop" aria-hidden="true">
	<div id="auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-heading">
		<div class="sign" id="log_in">
			<h2 id="auth-heading">Zaloguj się</h2>
			<form method="post" action="php/login.php">
				<label for="lusername">Nazwa użytkownika</label>
				<input type="text" id="lusername" placeholder="Wprowadź nazwę użytkownika" name="username" required>
				<label for="lpassword">Hasło</label>
				<input type="password" id="lpassword" placeholder="Wprowadź hasło" name="password" required>
				<button type="submit" class="btn btn-primary">Zaloguj się</button>
			</form>
		</div>

		<div class="sign" id="register">
			<h2 id="auth-heading">Zarejestruj się</h2>
			<form method="post" action="php/register.php" id="registerForm">
				<label for="rusername">Nazwa użytkownika</label>
				<input type="text" id="rusername" placeholder="Wprowadź nazwę użytkownika" name="username" required>
				<label for="rmail">E-mail</label>
				<input type="email" id="rmail" placeholder="Wprowadź adres e-mail" name="email" required>
				<label for="rpassword">Hasło</label>
				<input type="password" id="rpassword" placeholder="Wprowadź hasło" name="password" required>

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
			<a href="#" id="toggle-link">Nie masz konta? Zarejestruj się</a>
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
			<li><a href="profile.php">Profil</a></li>
			<li><a href="history.php">Historia</a></li>
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
			<li><a href="profile.php">Profil</a></li>
			<li><a href="history.php">Historia</a></li>
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

<main>
	<section id="first">
		<div>
			<h1>Twórz i rozwiązuj interaktywne quizy</h1>
			<p>Uczyń naukę zabawną i wciągającą dzięki naszej łatwej w użyciu platformie quizowej. Idealna dla edukatorów, trenerów i entuzjastów quizów.</p>
			<div>
				<button>Stwórz Quiz</button>
				<button>Rozwiąż Quiz</button>
			</div>
		</div>
		<img src="assets/Szescian.png" alt="Sześcian z quizem">
	</section>
	<section id="second">
		<h3>Popularne dzisiaj</h3>
		<article></article>
	</section>
</main>

<footer>
	<div class="footer-content">
		<div class="footer-section">
			<h4>Kto Pytał</h4>
			<p>Ułatwiamy tworzenie i udostępnianie quizów jak nigdy dotąd. Twórz wciągające quizy, które zafascynują Twoich odbiorców.</p>
		</div>
		<div class="footer-section">
			<h4>Szybkie linki</h4>
			<ul>
				<li>O nas</li>
				<li>Funkcje</li>
				<li>Cennik</li>
				<li>Blog</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Wsparcie</h4>
			<ul>
				<li>Centrum pomocy</li>
				<li>Kontakt</li>
				<li>Polityka prywatności</li>
				<li>Warunki korzystania z usługi</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Obserwuj nas</h4>
			<ul>
				<li>Facebook</li>
				<li>Twitter</li>
				<li>Instagram</li>
				<li>LinkedIn</li>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<p>&copy; <?php echo date('Y'); ?> Kto Pytał. Wszelkie prawa zastrzeżone.</p>
	</div>
</footer>

<script src="js/auth.js"></script>
<script src="js/password-validation.js"></script>
<script src="js/mobile-menu.js"></script>
<script src="js/requirements-visibility.js"></script>
</body>
</html>