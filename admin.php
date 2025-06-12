<?php
session_start();
require_once("config/db.php");

$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Sprawdź, czy użytkownik ma rolę 'admin'
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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

        // Usuń powiązane polubienia
        $stmt = $db->prepare("DELETE FROM polubione_quizy WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń powiązane wyniki quizów
        $stmt = $db->prepare("DELETE FROM wyniki_quizow WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń powiązane zgłoszenia
        $stmt = $db->prepare("DELETE FROM zgłoszenie WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń odpowiedzi powiązane z pytaniami tego quizu
        $stmt = $db->prepare("DELETE odpowiedzi FROM odpowiedzi JOIN pytanie ON odpowiedzi.pytanie_id = pytanie.pytanie_id WHERE pytanie.quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń pytania powiązane z quizem
        $stmt = $db->prepare("DELETE FROM pytanie WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Na koniec usuń sam quiz
        $stmt = $db->prepare("DELETE FROM quiz WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id_to_delete);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        $_SESSION['success'] = 'Quiz został pomyślnie usunięty.';
    } catch (mysqli_sql_exception $exception) {
        $db->rollback();
        $_SESSION['error'] = 'Błąd podczas usuwania quizu: ' . $exception->getMessage();
    }
    header('Location: admin.php');
    exit();
}

// Obsługa usuwania użytkownika
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id_to_delete = (int)$_GET['delete_user'];

    // Upewnij się, że admin nie usuwa samego siebie
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Nie możesz usunąć własnego konta administratora.';
        header('Location: admin.php');
        exit();
    }

    $db->begin_transaction();
    try {
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

        // Usuń zgłoszenia użytkownika (jeśli zgłaszał coś)
        $stmt = $db->prepare("DELETE FROM zgłoszenie WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();

        // Usuń quizy stworzone przez użytkownika
        $stmt = $db->prepare("SELECT quiz_id FROM quiz WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $result_quizzes = $stmt->get_result();
        $quizzes_to_delete = [];
        while ($row = $result_quizzes->fetch_assoc()) {
            $quizzes_to_delete[] = $row['quiz_id'];
        }
        $stmt->close();

        foreach ($quizzes_to_delete as $q_id) {
            // Usuń odpowiedzi powiązane z pytaniami tego quizu
            $stmt = $db->prepare("DELETE odpowiedzi FROM odpowiedzi JOIN pytanie ON odpowiedzi.pytanie_id = pytanie.pytanie_id WHERE pytanie.quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();

            // Usuń pytania powiązane z quizem
            $stmt = $db->prepare("DELETE FROM pytanie WHERE quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();

            // Na koniec usuń sam quiz
            $stmt = $db->prepare("DELETE FROM quiz WHERE quiz_id = ?");
            $stmt->bind_param("i", $q_id);
            $stmt->execute();
            $stmt->close();
        }

        // Na koniec usuń samego użytkownika
        $stmt = $db->prepare("DELETE FROM uzytkownicy WHERE user_id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        $_SESSION['success'] = 'Użytkownik został pomyślnie usunięty.';
    } catch (mysqli_sql_exception $exception) {
        $db->rollback();
        $_SESSION['error'] = 'Błąd podczas usuwania użytkownika: ' . $exception->getMessage();
    }
    header('Location: admin.php');
    exit();
}

// Obsługa usuwania pojedynczego zgłoszenia
if (isset($_GET['delete_report']) && is_numeric($_GET['delete_report'])) {
    $report_id_to_delete = (int)$_GET['delete_report'];

    $stmt = $db->prepare("DELETE FROM zgłoszenie WHERE zgłoszenie_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $report_id_to_delete);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Zgłoszenie zostało pomyślnie usunięte.';
        } else {
            $_SESSION['error'] = 'Błąd podczas usuwania zgłoszenia: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Błąd przygotowania zapytania do usunięcia zgłoszenia: ' . $db->error;
    }
    header('Location: admin.php');
    exit();
}


// Pobierz wszystkie quizy
$quizzes = [];
$stmt = $db->prepare("SELECT q.quiz_id, q.nazwa, q.opis, u.Nazwa AS autor_nazwa, q.data_utworzenia FROM quiz q JOIN uzytkownicy u ON q.user_id = u.user_id ORDER BY q.data_utworzenia DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = 'Błąd podczas pobierania quizów: ' . $db->error;
}

// Pobierz wszystkich użytkowników
$users = [];
$stmt = $db->prepare("SELECT user_id, Nazwa, email, rola, streak FROM uzytkownicy ORDER BY Nazwa ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = 'Błąd podczas pobierania użytkowników: ' . $db->error;
}

// Pobierz wszystkie zgłoszenia
$reports = [];
$stmt = $db->prepare("SELECT z.zgłoszenie_id, z.Treść, z.data_utworzenia, q.nazwa AS quiz_nazwa, q.quiz_id, u.Nazwa AS zglaszajacy_nazwa FROM zgłoszenie z JOIN quiz q ON z.quiz_id = q.quiz_id JOIN uzytkownicy u ON z.user_id = u.user_id ORDER BY z.data_utworzenia DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = 'Błąd podczas pobierania zgłoszeń: ' . $db->error;
}


$db->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="admin, panel, zarządzanie"/>
	<meta name="description" content="Panel administracyjny - Kto Pytał"/>
	<meta name="author" content="Same sigmy team"/>
	<meta name="robots" content="noindex, nofollow"/>
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">
	<link rel="stylesheet" href="style/admin.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Just+Another+Hand&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
	      rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Panel Administracyjny - Kto Pytał</title>
	<style>
        .search-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #007bff;
        }

        .search-stats {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
        }

        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        /* Dodatkowe style dla skróconej treści zgłoszenia */
        .report-content-cell {
            max-width: 300px; /* Adjust as needed */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: help; /* Wskazuje, że można najechać myszką */
        }

        .report-content-cell:hover {
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            max-width: none; /* Pozwól na pełną szerokość po najechaniu */
            background-color: #f0f0f0; /* Lekkie podświetlenie po najechaniu */
            position: relative; /* Umożliwia rozszerzenie treści bez wpływu na inne komórki */
            z-index: 10; /* Upewnia się, że treść nie jest obcinana przez inne elementy */
        }

	</style>
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
			<li><a href="index.php">Strona główna</a></li>
			<li><a href="quizzCreator.php">Stwórz Quiz</a></li>
			<li><a href="explore.php">Odkrywaj</a></li>
			<li><a href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
				<li><a href="history.php">Historia</a></li>
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


<main class="admin-panel-main">
	<div class="admin-panel-container">
		<h1>🛡️ Panel Administracyjny</h1>

		<div class="admin-welcome">
			<p>Witaj, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?></strong>!</p>
			<p>Zarządzaj quizami i użytkownikami platformy Kto Pytał.</p>
		</div>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="message error">❌ ' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="message success">✅ ' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>

		<section class="admin-section">
			<h2>📝 Zarządzanie Quizami</h2>
			<p class="section-description">Wszystkie quizy w systemie (<span
						id="quizzes-total"><?php echo count($quizzes); ?></span> quizów)</p>

			<div class="search-container">
				<div class="search-box">
					<input type="text" id="quiz-search" class="search-input"
					       placeholder="🔍 Wyszukaj quizy (nazwa, opis, autor)...">
				</div>
				<div class="search-stats" id="quiz-search-stats"></div>
			</div>

            <?php if (empty($quizzes)): ?>
				<div class="empty-state">
					<p>📭 Brak quizów do wyświetlenia.</p>
				</div>
            <?php else: ?>
				<div class="table-container">
					<table class="admin-table quizzes-table" id="quizzes-table">
						<thead>
						<tr>
							<th>ID</th>
							<th>Nazwa</th>
							<th>Opis</th>
							<th>Autor</th>
							<th>Data utworzenia</th>
							<th>Akcje</th>
						</tr>
						</thead>
						<tbody>
                        <?php foreach ($quizzes as $quiz): ?>
							<tr class="quiz-row"
							    data-quiz-name="<?php echo htmlspecialchars(strtolower($quiz['nazwa'])); ?>"
							    data-quiz-description="<?php echo htmlspecialchars(strtolower($quiz['opis'])); ?>"
							    data-quiz-author="<?php echo htmlspecialchars(strtolower($quiz['autor_nazwa'])); ?>">
								<td><?php echo htmlspecialchars($quiz['quiz_id']); ?></td>
								<td class="quiz-name"><?php echo htmlspecialchars($quiz['nazwa']); ?></td>
								<td class="quiz-description">
                                    <?php echo htmlspecialchars(substr($quiz['opis'], 0, 70)) . (strlen($quiz['opis']) > 70 ? '...' : ''); ?>
								</td>
								<td><?php echo htmlspecialchars($quiz['autor_nazwa']); ?></td>
								<td><?php echo htmlspecialchars($quiz['data_utworzenia']); ?></td>
								<td>
									<a href="admin.php?delete_quiz=<?php echo $quiz['quiz_id']; ?>"
									   onclick="return confirm('🗑️ Czy na pewno chcesz usunąć quiz \"
                                       <?php echo htmlspecialchars($quiz['nazwa']); ?>\"?\n\nSpowoduje to usunięcie:\n•
									Wszystkich pytań i odpowiedzi\n• Komentarzy\n• Wyników\n• Polubień\n• Zgłoszeń\n\nTa operacja
									jest nieodwracalna!');"
									class="btn btn-delete">🗑️ Usuń</a>
								</td>
							</tr>
                        <?php endforeach; ?>
						</tbody>
					</table>
					<div class="no-results" id="quiz-no-results" style="display: none;">
						<p>🔍 Nie znaleziono quizów pasujących do wyszukiwania.</p>
					</div>
				</div>
            <?php endif; ?>
		</section>

		<section class="admin-section">
			<h2>👥 Zarządzanie Użytkownikami</h2>
			<p class="section-description">Wszyscy użytkownicy w systemie (<span
						id="users-total"><?php echo count($users); ?></span> użytkowników)</p>

			<div class="search-container">
				<div class="search-box">
					<input type="text" id="user-search" class="search-input"
					       placeholder="🔍 Wyszukaj użytkowników (nazwa, email, rola)...">
				</div>
				<div class="search-stats" id="user-search-stats"></div>
			</div>

            <?php if (empty($users)): ?>
				<div class="empty-state">
					<p>👤 Brak użytkowników do wyświetlenia.</p>
				</div>
            <?php else: ?>
				<div class="table-container">
					<table class="admin-table users-table" id="users-table">
						<thead>
						<tr>
							<th>ID</th>
							<th>Nazwa</th>
							<th>Email</th>
							<th>Rola</th>
							<th>Streak</th>
							<th>Akcje</th>
						</tr>
						</thead>
						<tbody>
                        <?php foreach ($users as $user): ?>
							<tr class="user-row <?php echo ($user['user_id'] == $_SESSION['user_id']) ? 'current-admin' : ''; ?>"
							    data-user-name="<?php echo htmlspecialchars(strtolower($user['Nazwa'])); ?>"
							    data-user-email="<?php echo htmlspecialchars(strtolower($user['email'])); ?>"
							    data-user-role="<?php echo htmlspecialchars(strtolower($user['rola'])); ?>">
								<td><?php echo htmlspecialchars($user['user_id']); ?></td>
								<td class="user-name">
                                    <?php echo htmlspecialchars($user['Nazwa']); ?>
                                    <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
										<span class="self-indicator">👑 (Ty)</span>
                                    <?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($user['email']); ?></td>
								<td>
                                    <span class="role-badge role-<?php echo htmlspecialchars($user['rola']); ?>">
                                        <?php echo htmlspecialchars($user['rola']); ?>
                                    </span>
								</td>
								<td><?php echo htmlspecialchars($user['streak']); ?></td>
								<td>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
										<a href="admin.php?delete_user=<?php echo $user['user_id']; ?>"
										   onclick="return confirm('🗑️ Czy na pewno chcesz usunąć użytkownika \"
                                           <?php echo htmlspecialchars($user['Nazwa']); ?>\"?\n\nSpowoduje to usunięcie:\n• Wszystkich jego quizów\n• Wyników\n• Komentarzy\n• Polubień\n• Zgłoszeń (jeśli zgłaszał)\n\nTa operacja jest nieodwracalna!');"
										                                                   class="btn btn-delete">🗑️ Usuń</a>
                                    <?php endif; ?>
								</td>
							</tr>
                        <?php endforeach; ?>
						</tbody>
					</table>
					<div class="no-results" id="user-no-results" style="display: none;">
						<p>🔍 Nie znaleziono użytkowników pasujących do wyszukiwania.</p>
					</div>
				</div>
            <?php endif; ?>
		</section>

		<section class="admin-section">
			<h2>🚨 Zarządzanie Zgłoszeniami</h2>
			<p class="section-description">Wszystkie zgłoszenia quizów (<span
						id="reports-total"><?php echo count($reports); ?></span> zgłoszeń)</p>

			<div class="search-container">
				<div class="search-box">
					<input type="text" id="report-search" class="search-input"
					       placeholder="🔍 Wyszukaj zgłoszenia (nazwa quizu, treść, zgłaszający)...">
				</div>
				<div class="search-stats" id="report-search-stats"></div>
			</div>

            <?php if (empty($reports)): ?>
				<div class="empty-state">
					<p>✅ Brak nowych zgłoszeń.</p>
				</div>
            <?php else: ?>
				<div class="table-container">
					<table class="admin-table reports-table" id="reports-table">
						<thead>
						<tr>
							<th>ID Zgłoszenia</th>
							<th>Nazwa Quizu</th>
							<th>Treść Zgłoszenia</th>
							<th>Data Zgłoszenia</th>
							<th>Zgłaszający</th>
							<th>Akcje</th>
						</tr>
						</thead>
						<tbody>
                        <?php foreach ($reports as $report): ?>
							<tr class="report-row"
							    data-report-quiz-name="<?php echo htmlspecialchars(strtolower($report['quiz_nazwa'])); ?>"
							    data-report-content="<?php echo htmlspecialchars(strtolower($report['Treść'])); ?>"
							    data-report-reporter-name="<?php echo htmlspecialchars(strtolower($report['zglaszajacy_nazwa'])); ?>">
								<td><?php echo htmlspecialchars($report['zgłoszenie_id']); ?></td>
								<td class="report-quiz-name"><?php echo htmlspecialchars($report['quiz_nazwa']); ?></td>
								<td class="report-content-cell" title="<?php echo htmlspecialchars($report['Treść']); ?>">
                                    <?php echo htmlspecialchars(substr($report['Treść'], 0, 70)) . (strlen($report['Treść']) > 70 ? '...' : ''); ?>
								</td>
								<td><?php echo htmlspecialchars($report['data_utworzenia']); ?></td>
								<td><?php echo htmlspecialchars($report['zglaszajacy_nazwa']); ?></td>
								<td>
									<a href="admin.php?delete_quiz=<?php echo $report['quiz_id']; ?>"
									   onclick="return confirm('🗑️ Czy na pewno chcesz USUNĄĆ TEN QUIZ (ID: <?php echo $report['quiz_id']; ?>) powiązany ze zgłoszeniem (ID: <?php echo $report['zgłoszenie_id']; ?>)?\n\nSpowoduje to usunięcie:\n• Wszystkich pytań i odpowiedzi\n• Komentarzy\n• Wyników\n• Polubień\n• POWIĄZANYCH ZGŁOSZEŃ (W TYM TEGO)\n\nTa operacja jest nieodwracalna!');"
									   class="btn btn-delete">🗑️ Usuń Quiz</a>
									<a href="admin.php?delete_report=<?php echo $report['zgłoszenie_id']; ?>"
									   onclick="return confirm('Czy na pewno chcesz usunąć to pojedyncze zgłoszenie (ID: <?php echo $report['zgłoszenie_id']; ?>)?\n\nTO NIE USUNIE QUIZU!');"
									   class="btn btn-secondary">Usuń Zgłoszenie</a>
								</td>
							</tr>
                        <?php endforeach; ?>
						</tbody>
					</table>
					<div class="no-results" id="report-no-results" style="display: none;">
						<p>🔍 Nie znaleziono zgłoszeń pasujących do wyszukiwania.</p>
					</div>
				</div>
            <?php endif; ?>
		</section>

	</div>
</main>

<footer>
	<div class="footer-content">
		<div class="footer-section">
			<h4>Kto Pytał</h4>
			<p>Making quiz creation and sharing easier than ever. Build engaging quizzes that captivate your
				audience.</p>
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
    // Funkcja podświetlania tekstu
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlightText(row, searchTerm, cellSelectors) {
        cellSelectors.forEach(selector => {
            const cell = row.querySelector(selector);
            if (cell) {
                // Sprawdź, czy komórka już posiada data-original-text
                let originalText = cell.getAttribute('data-original-text');

                // Jeśli nie ma, zapisz jej aktualną zawartość jako oryginalną
                if (!originalText) {
                    originalText = cell.innerHTML;
                    cell.setAttribute('data-original-text', originalText);
                }

                if (searchTerm === '') {
                    // Jeśli wyszukiwanie jest puste, przywróć oryginalny tekst
                    cell.innerHTML = originalText;
                } else {
                    // W przeciwnym razie, podświetl tekst
                    const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                    // Użyj oryginalnego tekstu do podświetlenia, aby uniknąć wielokrotnego podświetlania
                    const highlightedText = originalText.replace(regex, '<span class="highlight">$1</span>');
                    cell.innerHTML = highlightedText;
                }
            }
        });
    }

    // Funkcja wyszukiwania quizów
    document.getElementById('quiz-search').addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase().trim();
        const quizRows = document.querySelectorAll('.quiz-row');
        const quizTable = document.getElementById('quizzes-table');
        const noResultsDiv = document.getElementById('quiz-no-results');
        const statsDiv = document.getElementById('quiz-search-stats');
        const totalSpan = document.getElementById('quizzes-total');

        let visibleCount = 0;
        let totalCount = quizRows.length;

        quizRows.forEach(function (row) {
            const quizName = row.getAttribute('data-quiz-name');
            const quizDescription = row.getAttribute('data-quiz-description');
            const quizAuthor = row.getAttribute('data-quiz-author');

            const isVisible = searchTerm === '' ||
                quizName.includes(searchTerm) ||
                quizDescription.includes(searchTerm) ||
                quizAuthor.includes(searchTerm);

            if (isVisible) {
                row.style.display = '';
                visibleCount++;
                highlightText(row, searchTerm, ['.quiz-name', '.quiz-description', 'td:nth-child(4)']); // Apply highlighting to name, description, author
            } else {
                row.style.display = 'none';
            }
        });

        // Pokaż/ukryj komunikat o braku wyników
        if (searchTerm !== '' && visibleCount === 0) {
            quizTable.style.display = 'none';
            noResultsDiv.style.display = 'block';
        } else {
            quizTable.style.display = '';
            noResultsDiv.style.display = 'none';
        }

        // Aktualizuj statystyki
        if (searchTerm !== '') {
            statsDiv.textContent = `Pokazano ${visibleCount} z ${totalCount} quizów`;
            totalSpan.textContent = visibleCount; // Update visible count in total span
        } else {
            statsDiv.textContent = '';
            totalSpan.textContent = totalCount; // Restore total count
            // Clear highlights when search is empty
            quizRows.forEach(function(row) {
                highlightText(row, '', ['.quiz-name', '.quiz-description', 'td:nth-child(4)']);
            });
        }
    });

    // Funkcja wyszukiwania użytkowników
    document.getElementById('user-search').addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase().trim();
        const userRows = document.querySelectorAll('.user-row');
        const userTable = document.getElementById('users-table');
        const noResultsDiv = document.getElementById('user-no-results');
        const statsDiv = document.getElementById('user-search-stats');
        const totalSpan = document.getElementById('users-total');

        let visibleCount = 0;
        let totalCount = userRows.length;

        userRows.forEach(function (row) {
            const userName = row.getAttribute('data-user-name');
            const userEmail = row.getAttribute('data-user-email');
            const userRole = row.getAttribute('data-user-role');

            const isVisible = searchTerm === '' ||
                userName.includes(searchTerm) ||
                userEmail.includes(searchTerm) ||
                userRole.includes(searchTerm);

            if (isVisible) {
                row.style.display = '';
                visibleCount++;
                highlightText(row, searchTerm, ['.user-name', 'td:nth-child(3)', 'td:nth-child(4)']); // Apply highlighting to name, email, role
            } else {
                row.style.display = 'none';
            }
        });

        // Pokaż/ukryj komunikat o braku wyników
        if (searchTerm !== '' && visibleCount === 0) {
            userTable.style.display = 'none';
            noResultsDiv.style.display = 'block';
        } else {
            userTable.style.display = '';
            noResultsDiv.style.display = 'none';
        }

        // Aktualizuj statystyki
        if (searchTerm !== '') {
            statsDiv.textContent = `Pokazano ${visibleCount} z ${totalCount} użytkowników`;
            totalSpan.textContent = visibleCount; // Update visible count in total span
        } else {
            statsDiv.textContent = '';
            totalSpan.textContent = totalCount; // Restore total count
            // Clear highlights when search is empty
            userRows.forEach(function(row) {
                highlightText(row, '', ['.user-name', 'td:nth-child(3)', 'td:nth-child(4)']);
            });
        }
    });

    // Funkcja wyszukiwania zgłoszeń
    document.getElementById('report-search').addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase().trim();
        const reportRows = document.querySelectorAll('.report-row');
        const reportTable = document.getElementById('reports-table');
        const noResultsDiv = document.getElementById('report-no-results');
        const statsDiv = document.getElementById('report-search-stats');
        const totalSpan = document.getElementById('reports-total');

        let visibleCount = 0;
        let totalCount = reportRows.length;

        reportRows.forEach(function (row) {
            const quizName = row.getAttribute('data-report-quiz-name');
            const reportContent = row.getAttribute('data-report-content');
            const reporterName = row.getAttribute('data-report-reporter-name');

            const isVisible = searchTerm === '' ||
                quizName.includes(searchTerm) ||
                reportContent.includes(searchTerm) ||
                reporterName.includes(searchTerm);

            if (isVisible) {
                row.style.display = '';
                visibleCount++;
                highlightText(row, searchTerm, ['.report-quiz-name', '.report-content-cell', 'td:nth-child(5)']); // Apply highlighting
            } else {
                row.style.display = 'none';
            }
        });

        // Pokaż/ukryj komunikat o braku wyników
        if (searchTerm !== '' && visibleCount === 0) {
            reportTable.style.display = 'none';
            noResultsDiv.style.display = 'block';
        } else {
            reportTable.style.display = '';
            noResultsDiv.style.display = 'none';
        }

        // Aktualizuj statystyki
        if (searchTerm !== '') {
            statsDiv.textContent = `Pokazano ${visibleCount} z ${totalCount} zgłoszeń`;
            totalSpan.textContent = visibleCount; // Update visible count in total span
        } else {
            statsDiv.textContent = '';
            totalSpan.textContent = totalCount; // Restore total count
            // Clear highlights when search is empty
            reportRows.forEach(function(row) {
                highlightText(row, '', ['.report-quiz-name', '.report-content-cell', 'td:nth-child(5)']);
            });
        }
    });
</script>

</body>
</html>