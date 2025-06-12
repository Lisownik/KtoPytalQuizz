<?php
session_start();
require_once("config/db.php");

$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Sprawdź, czy użytkownik jest zalogowany
//if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
//    $_SESSION['error'] = 'Musisz być zalogowany, aby uzyskać dostęp do tej strony.';
//    header('Location: index.php');
//    exit();
//}

// Sprawdź, czy użytkownik ma rolę 'admin' - POPRAWIONA LOGIKA
//if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//    $_SESSION['error'] = 'Nie masz uprawnień do przeglądania tej strony.';
//    header('Location: index.php');
//    exit();
//}

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
    header('Location: admin.php'); // POPRAWIONE - admin.php zamiast admin_panel.php
    exit();
}

// Obsługa usuwania użytkownika
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id_to_delete = (int)$_GET['delete_user'];

    // Upewnij się, że admin nie usuwa samego siebie
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Nie możesz usunąć własnego konta administratora.';
        header('Location: admin.php'); // POPRAWIONE
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

        // Usuń zgłoszenia użytkownika
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
    header('Location: admin.php'); // POPRAWIONE
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
    <link href="https://fonts.googleapis.com/css2?family=Just+Another+Hand&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administracyjny - Kto Pytał</title>
</head>
<body>

<!-- Auth Modal -->
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

<!-- Mobile Menu -->
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
            <li><a href="profile.php">Profile</a></li>
            <li><a href="admin.php">Admin Panel</a></li>
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

<!-- Header -->
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
            <li><a id="selected-page" href="admin.php">Admin Panel</a></li>
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
            <a href="#" id="open-login" class="signin-link">Sign In</a>
        <?php endif; ?>
    </div>
</header>

<!-- Main Content -->
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

        <!-- Sekcja Quizów -->
        <section class="admin-section">
            <h2>📝 Zarządzanie Quizami</h2>
            <p class="section-description">Wszystkie quizy w systemie (<?php echo count($quizzes); ?> quizów)</p>

            <?php if (empty($quizzes)): ?>
                <div class="empty-state">
                    <p>📭 Brak quizów do wyświetlenia.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="admin-table quizzes-table">
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
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['quiz_id']); ?></td>
                                <td class="quiz-name"><?php echo htmlspecialchars($quiz['nazwa']); ?></td>
                                <td class="quiz-description">
                                    <?php echo htmlspecialchars(substr($quiz['opis'], 0, 70)) . (strlen($quiz['opis']) > 70 ? '...' : ''); ?>
                                </td>
                                <td><?php echo htmlspecialchars($quiz['autor_nazwa']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['data_utworzenia']); ?></td>
                                <td>
                                    <a href="admin.php?delete_quiz=<?php echo $quiz['quiz_id']; ?>"
                                       onclick="return confirm('🗑️ Czy na pewno chcesz usunąć quiz \"<?php echo htmlspecialchars($quiz['nazwa']); ?>\"?\n\nSpowoduje to usunięcie:\n• Wszystkich pytań i odpowiedzi\n• Komentarzy\n• Wyników\n• Polubień\n\nTa operacja jest nieodwracalna!');"
                                    class="btn btn-delete">🗑️ Usuń</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Sekcja Użytkowników -->
        <section class="admin-section">
            <h2>👥 Zarządzanie Użytkownikami</h2>
            <p class="section-description">Wszyscy użytkownicy w systemie (<?php echo count($users); ?> użytkowników)</p>

            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <p>👤 Brak użytkowników do wyświetlenia.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="admin-table users-table">
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
                            <tr <?php echo ($user['user_id'] == $_SESSION['user_id']) ? 'class="current-admin"' : ''; ?>>
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
                                           onclick="return confirm('🗑️ Czy na pewno chcesz usunąć użytkownika \"<?php echo htmlspecialchars($user['Nazwa']); ?>\"?\n\nSpowoduje to usunięcie:\n• Wszystkich jego quizów\n• Wyników\n• Komentarzy\n• Polubień\n\nTa operacja jest nieodwracalna!');"
                                                                                                                                                                class="btn btn-delete">🗑️ Usuń</a>
                                    <?php else: ?>
                                        <span class="text-muted">🛡️ Administrator</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<!-- Footer -->
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
</body>
</html>