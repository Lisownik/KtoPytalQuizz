<?php
session_start();
require_once("config/db.php");

$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Sprawdź, czy użytkownik jest zalogowany
if (!$zalogowany) {
    $_SESSION['error'] = 'Musisz być zalogowany, aby uzyskać dostęp do tej strony.';
    header('Location: index.php');
    exit();
}

// Sprawdź, czy użytkownik ma rolę 'admin'
// Najpierw spróbuj pobrać rolę z sesji, jeśli jest dostępna
$user_role = $_SESSION['user_role'] ?? null;

// Jeśli rola nie jest w sesji (np. po świeżym logowaniu), pobierz ją z bazy danych
if ($user_role === null) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt_role = $db->prepare("SELECT rola FROM uzytkownicy WHERE user_id = ?");
        if ($stmt_role) {
            $stmt_role->bind_param("i", $user_id);
            $stmt_role->execute();
            $result_role = $stmt_role->get_result();
            if ($row_role = $result_role->fetch_assoc()) {
                $user_role = $row_role['rola'];
                $_SESSION['user_role'] = $user_role; // Zapisz rolę w sesji na przyszłość
            }
            $stmt_role->close();
        }
    }
}

// Jeśli rola użytkownika nie jest 'admin', przekieruj
if ($user_role !== 'admin') {
    $_SESSION['error'] = 'Nie masz uprawnień do przeglądania tej strony.';
    header('Location: index.php');
    exit();
}

// Obsługa usuwania quizu
if (isset($_GET['delete_quiz']) && is_numeric($_GET['delete_quiz'])) {
    $quiz_id_to_delete = (int)$_GET['delete_quiz'];

    // Rozpocznij transakcję, aby zapewnić spójność danych
    $db->begin_transaction();
    try {
        // Usuń powiązane komentarze
        $stmt = $db->prepare("DELETE FROM komentarze WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń powiązane polubienia quizu
        $stmt = $db->prepare("DELETE FROM polubione_quizy WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń powiązane wyniki quizów
        $stmt = $db->prepare("DELETE FROM wyniki_quizow WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń powiązane opcje odpowiedzi
        $stmt = $db->prepare("DELETE FROM opcje_odpowiedzi WHERE pytanie_id IN (SELECT pytanie_id FROM pytanie WHERE quiz_id = ?)");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń powiązane pytania
        $stmt = $db->prepare("DELETE FROM pytanie WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń quiz
        $stmt = $db->prepare("DELETE FROM quiz WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        $_SESSION['success_message'] = 'Quiz został pomyślnie usunięty wraz ze wszystkimi powiązanymi danymi.';
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error_message'] = 'Wystąpił błąd podczas usuwania quizu: ' . $e->getMessage();
    }
    header('Location: admin.php');
    exit();
}

// Obsługa usuwania użytkownika
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id_to_delete = (int)$_GET['delete_user'];

    // Zapobiegaj usunięciu samego siebie, jeśli jesteś adminem
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'Nie możesz usunąć własnego konta administratora z panelu admina.';
        header('Location: admin.php');
        exit();
    }

    $db->begin_transaction();
    try {
        // Usuń quizy stworzone przez użytkownika i wszystkie powiązane dane quizów
        // Najpierw pobierz quiz_id wszystkich quizów stworzonych przez tego użytkownika
        $quiz_ids_to_delete = [];
        $stmt = $db->prepare("SELECT quiz_id FROM quiz WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $quiz_ids_to_delete[] = $row['quiz_id'];
        }
        $stmt->close();

        foreach ($quiz_ids_to_delete as $q_id) {
            // Usuń powiązane komentarze
            $stmt = $db->prepare("DELETE FROM komentarze WHERE quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();

            // Usuń powiązane polubienia quizu
            $stmt = $db->prepare("DELETE FROM polubione_quizy WHERE quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();

            // Usuń powiązane wyniki quizów
            $stmt = $db->prepare("DELETE FROM wyniki_quizow WHERE quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();

            // Usuń powiązane opcje odpowiedzi
            $stmt = $db->prepare("DELETE FROM opcje_odpowiedzi WHERE pytanie_id IN (SELECT pytanie_id FROM pytanie WHERE quiz_id = ?)");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();

            // Usuń powiązane pytania
            $stmt = $db->prepare("DELETE FROM pytanie WHERE quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();
        }
        // Na koniec usuń same quizy
        if (!empty($quiz_ids_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($quiz_ids_to_delete), '?'));
            $types = str_repeat('i', count($quiz_ids_to_delete));
            $stmt = $db->prepare("DELETE FROM quiz WHERE quiz_id IN ($placeholders)");
            $stmt->bind_param($types, ...$quiz_ids_to_delete);
            $stmt->execute();
            $stmt->close();
        }

        // Usuń komentarze użytkownika
        $stmt = $db->prepare("DELETE FROM komentarze WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń polubienia użytkownika
        $stmt = $db->prepare("DELETE FROM polubione_quizy WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń wyniki quizów użytkownika
        $stmt = $db->prepare("DELETE FROM wyniki_quizow WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń samego użytkownika
        $stmt = $db->prepare("DELETE FROM uzytkownicy WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        $_SESSION['success_message'] = 'Użytkownik i wszystkie powiązane z nim dane zostały pomyślnie usunięte.';
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error_message'] = 'Wystąpił błąd podczas usuwania użytkownika: ' . $e->getMessage();
    }
    header('Location: admin.php');
    exit();
}


// Pobierz listę wszystkich quizów
$quizzes = [];
$stmt_quizzes = $db->prepare("SELECT q.quiz_id, q.nazwa, u.Nazwa AS autor_nazwa, q.data_utworzenia FROM quiz q JOIN uzytkownicy u ON q.user_id = u.user_id ORDER BY q.data_utworzenia DESC");
if ($stmt_quizzes) {
    $stmt_quizzes->execute();
    $result_quizzes = $stmt_quizzes->get_result();
    while ($row = $result_quizzes->fetch_assoc()) {
        $quizzes[] = $row;
    }
    $stmt_quizzes->close();
}

// Pobierz listę wszystkich użytkowników
$users = [];
$stmt_users = $db->prepare("SELECT user_id, Nazwa, email, rola FROM uzytkownicy ORDER BY Nazwa ASC");
if ($stmt_users) {
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_users->close();
}


mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Panel Administracyjny | Kto Pytał</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">
	<link rel="stylesheet" href="style/admin.css">
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
			<li><a href="index.php">Start</a></li>
			<li><a href="quizzCreator.php">Stwórz Quiz</a></li>
			<li><a href="explore.php">Odkryj</a></li>
			<li><a href="history.php">Moja historia</a></li>
            <li><a href="ranking.php">Ranking</a></li>
            <li><a href="profile.php">Profil</a></li>
            <?php if ($zalogowany && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
				<li><a id="selected-page" href="admin.php">Panel Admina</a></li>
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
		<a href="index.php">
			<img src="assets/logo.png" alt="logo mózgu">
			<h2>Kto Pytał</h2>
		</a>
	</div>
	<nav>
		<ul>
			<li><a href="index.php">Start</a></li>
			<li><a href="quizzCreator.php">Stwórz Quiz</a></li>
			<li><a href="explore.php">Odkryj</a></li>
			<li><a href="history.php">Moja historia</a></li>
            <li><a href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
                <li><a href="profile.php">Profil</a></li>
            <?php endif; ?>
            <?php if ($zalogowany && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
				<li><a id="selected-page" href="admin.php" style="background: rgba(255, 255, 255, 0.1);">Panel Admina</a></li>
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

<main class="admin-panel-container">
	<h1>Panel Administracyjny</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
		<div class="message success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
		</div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
		<div class="message error">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
		</div>
    <?php endif; ?>

	<div class="admin-sections">
		<section class="admin-section">
			<h2>Zarządzaj quizami</h2>
            <?php if (!empty($quizzes)): ?>
				<div class="table-responsive">
					<table>
						<thead>
						<tr>
							<th>ID Quizu</th>
							<th>Nazwa Quizu</th>
							<th>Autor</th>
							<th>Data Utworzenia</th>
							<th>Akcje</th>
						</tr>
						</thead>
						<tbody>
                        <?php foreach ($quizzes as $quiz): ?>
							<tr>
								<td><?php echo htmlspecialchars($quiz['quiz_id']); ?></td>
								<td><?php echo htmlspecialchars($quiz['nazwa']); ?></td>
								<td><?php echo htmlspecialchars($quiz['autor_nazwa']); ?></td>
								<td><?php echo htmlspecialchars($quiz['data_utworzenia']); ?></td>
								<td>
									<button class="delete-btn" data-type="quiz" data-id="<?php echo htmlspecialchars($quiz['quiz_id']); ?>">Usuń</button>
								</td>
							</tr>
                        <?php endforeach; ?>
						</tbody>
					</table>
				</div>
            <?php else: ?>
				<p>Brak quizów do wyświetlenia.</p>
            <?php endif; ?>
		</section>

		<section class="admin-section">
			<h2>Zarządzaj użytkownikami</h2>
            <?php if (!empty($users)): ?>
				<div class="table-responsive">
					<table>
						<thead>
						<tr>
							<th>ID Użytkownika</th>
							<th>Nazwa Użytkownika</th>
							<th>E-mail</th>
							<th>Rola</th>
							<th>Akcje</th>
						</tr>
						</thead>
						<tbody>
                        <?php foreach ($users as $user): ?>
							<tr>
								<td><?php echo htmlspecialchars($user['user_id']); ?></td>
								<td><?php echo htmlspecialchars($user['Nazwa']); ?></td>
								<td><?php echo htmlspecialchars($user['email']); ?></td>
								<td><?php echo htmlspecialchars($user['rola']); ?></td>
								<td>
                                    <?php if ($user['user_id'] != ($_SESSION['user_id'] ?? null)): ?>
										<button class="delete-btn" data-type="user" data-id="<?php echo htmlspecialchars($user['user_id']); ?>">Usuń</button>
                                    <?php else: ?>
										<span class="cannot-delete">Nie można usunąć (Ty)</span>
                                    <?php endif; ?>
								</td>
							</tr>
                        <?php endforeach; ?>
						</tbody>
					</table>
				</div>
            <?php else: ?>
				<p>Brak użytkowników do wyświetlenia.</p>
            <?php endif; ?>
		</section>

	</div>
</main>

<footer>
	<div class="footer-content">
		<div class="footer-section">
			<h4>Kto Pytał</h4>
			<p>Tworzenie i dzielenie się quizami jest u nas łatwiejsze niż kiedykolwiek. Rób wciągające quizy, które zachwycą Twoją publiczność.</p>
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
			<h4>Pomoc</h4>
			<ul>
				<li>Centrum pomocy</li>
				<li>Kontakt</li>
				<li>Zasady prywatności</li>
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
		<p>&copy; <?php echo date('Y'); ?> Kto Pytał. Wszystkie prawa zastrzeżone.</p>
	</div>
</footer>

<div id="auth-modal-backdrop" aria-hidden="true" style="display: none;">
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
			<h2 id="auth-heading">Zarejestruj się</h2>
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
			<a href="#" id="toggle-link">Nie masz konta? Zarejestruj się</a>
		</p>
	</div>
</div>

<script src="js/auth.js"></script>
<script src="js/mobile-menu.js"></script>
<script src="js/password-validation.js"></script>
<script src="js/requirements-visibility.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Obsługa przycisków usuwania
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.dataset.type; // 'quiz' or 'user'
                const id = this.dataset.id;
                let confirmMessage = '';
                let redirectUrl = '';

                if (type === 'quiz') {
                    confirmMessage = 'Czy na pewno chcesz usunąć ten quiz? Spowoduje to również usunięcie wszystkich powiązanych pytań, odpowiedzi, wyników i komentarzy!';
                    redirectUrl = `admin.php?delete_quiz=${id}`;
                } else if (type === 'user') {
                    confirmMessage = 'Czy na pewno chcesz usunąć tego użytkownika? Spowoduje to również usunięcie wszystkich stworzonych przez niego quizów, komentarzy i wyników!';
                    redirectUrl = `admin.php?delete_user=${id}`;
                }

                if (confirm(confirmMessage)) {
                    window.location.href = redirectUrl;
                }
            });
        });

        // Obsługa otwierania modala logowania z nagłówka
        const openLoginHeader = document.getElementById('open-login');
        if (openLoginHeader) {
            openLoginHeader.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('auth-modal-backdrop').style.display = 'block';
                document.getElementById('auth-modal').setAttribute('aria-hidden', 'false');
            });
        }

        // Logika przełączania między logowaniem a rejestracją w modalu
        const toggleLink = document.getElementById('toggle-link');
        const loginForm = document.getElementById('log_in');
        const registerForm = document.getElementById('register');
        const authHeading = document.getElementById('auth-heading');

        if (toggleLink && loginForm && registerForm && authHeading) {
            toggleLink.addEventListener('click', function(e) {
                e.preventDefault();
                if (loginForm.style.display === 'block' || loginForm.style.display === '') {
                    loginForm.style.display = 'none';
                    registerForm.style.display = 'block';
                    toggleLink.textContent = 'Masz już konto? Zaloguj się';
                    authHeading.textContent = 'Zarejestruj się';
                } else {
                    loginForm.style.display = 'block';
                    registerForm.style.display = 'none';
                    toggleLink.textContent = 'Nie masz konta? Zarejestruj się';
                    authHeading.textContent = 'Zaloguj się';
                }
            });
        }

        // Zamykanie modala po kliknięciu poza nim
        const authModalBackdrop = document.getElementById('auth-modal-backdrop');
        if (authModalBackdrop) {
            authModalBackdrop.addEventListener('click', function(e) {
                if (e.target === authModalBackdrop) {
                    authModalBackdrop.style.display = 'none';
                    authModalBackdrop.setAttribute('aria-hidden', 'true');
                    // Reset do widoku logowania
                    if (loginForm) loginForm.style.display = 'block';
                    if (registerForm) registerForm.style.display = 'none';
                    if (toggleLink) toggleLink.textContent = 'Nie masz konta? Zarejestruj się';
                    if (authHeading) authHeading.textContent = 'Zaloguj się';
                }
            });
        }
    });
</script>

</body>
</html>