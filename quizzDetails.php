<?php
session_start();
require_once("config/db.php"); // Assuming your db.php is in the config folder

// Check if a user is logged in
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
$user_id = $_SESSION['user_id'] ?? null; // Get user ID from session if logged in

// Check for database connection error
if (mysqli_connect_errno()) {
    $_SESSION['error'] = 'Nie udało się połączyć z bazą danych: ' . mysqli_connect_error();
    header('Location: index.php');
    exit();
}

$quiz_id = null;
$quiz = null;
$user_liked_quiz = false;
$total_likes = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $quiz_id = (int)$_GET['id'];

    // Fetch quiz details
    $stmt = $db->prepare("SELECT q.nazwa AS tytul, q.opis, u.Nazwa AS autor_nazwa FROM quiz q JOIN uzytkownicy u ON q.user_id = u.user_id WHERE q.quiz_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quiz = $result->fetch_assoc();
        $stmt->close();
    }

    // Fetch number of questions
    $num_questions = 0;
    if ($quiz) {
        $stmt_questions = $db->prepare("SELECT COUNT(*) AS total_questions FROM pytanie WHERE quiz_id = ?");
        if ($stmt_questions) {
            $stmt_questions->bind_param("i", $quiz_id);
            $stmt_questions->execute();
            $result_questions = $stmt_questions->get_result();
            $row_questions = $result_questions->fetch_assoc();
            $num_questions = $row_questions['total_questions'];
            $stmt_questions->close();
        }

        // Fetch comments
        $comments = [];
        $stmt_comments = $db->prepare("SELECT k.treść, k.data_utworzenia, u.Nazwa AS user_nazwa FROM komentarze k JOIN uzytkownicy u ON k.user_id = u.user_id WHERE k.quiz_id = ? ORDER BY k.data_utworzenia DESC");
        if ($stmt_comments) {
            $stmt_comments->bind_param("i", $quiz_id);
            $stmt_comments->execute();
            $result_comments = $stmt_comments->get_result();
            while ($row = $result_comments->fetch_assoc()) {
                $comments[] = $row;
            }
            $stmt_comments->close();
        }

        // Check if the current user has liked this quiz
        if ($zalogowany && $user_id) {
            $stmt_check_like = $db->prepare("SELECT COUNT(*) FROM polubione_quizy WHERE user_id = ? AND quiz_id = ?");
            if ($stmt_check_like) {
                $stmt_check_like->bind_param("ii", $user_id, $quiz_id);
                $stmt_check_like->execute();
                $stmt_check_like->bind_result($count);
                $stmt_check_like->fetch();
                $user_liked_quiz = ($count > 0);
                $stmt_check_like->close();
            }
        }

        // Get total likes
        $stmt_total_likes = $db->prepare("SELECT COUNT(*) FROM polubione_quizy WHERE quiz_id = ?");
        if ($stmt_total_likes) {
            $stmt_total_likes->bind_param("i", $quiz_id);
            $stmt_total_likes->execute();
            $stmt_total_likes->bind_result($count);
            $stmt_total_likes->fetch();
            $total_likes = $count;
            $stmt_total_likes->close();
        }
    }

    // Calculate estimated time (1.5 minutes per question)
    $estimated_minutes = $num_questions * 1.5;

} else {
    $_SESSION['error'] = 'Nieprawidłowy identyfikator quizu.';
    header('Location: explore.php');
    exit();
}

// If quiz not found, redirect
if (!$quiz) {
    $_SESSION['error'] = 'Quiz o podanym ID nie istnieje.';
    header('Location: explore.php');
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!$zalogowany) {
        $_SESSION['error'] = 'Musisz być zalogowany, aby dodać komentarz.';
        header('Location: login.php');
        exit();
    }

    $comment_text = trim($_POST['comment_text'] ?? '');

    if (empty($comment_text)) {
        $_SESSION['error'] = 'Treść komentarza nie może być pusta.';
    } else {
        $stmt_insert_comment = $db->prepare("INSERT INTO komentarze (treść, data_utworzenia, user_id, quiz_id) VALUES (?, NOW(), ?, ?)");
        if ($stmt_insert_comment) {
            $stmt_insert_comment->bind_param("sii", $comment_text, $user_id, $quiz_id);
            if ($stmt_insert_comment->execute()) {
                $_SESSION['success'] = 'Komentarz został dodany.';
                header('Location: quizzDetails.php?id=' . $quiz_id);
                exit();
            } else {
                $_SESSION['error'] = 'Wystąpił błąd podczas dodawania komentarza: ' . $stmt_insert_comment->error;
            }
            $stmt_insert_comment->close();
        } else {
            $_SESSION['error'] = 'Wystąpił błąd podczas przygotowywania zapytania: ' . $db->error;
        }
    }
    header('Location: quizzDetails.php?id=' . $quiz_id);
    exit();
}

// Handle like/unlike submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    if (!$zalogowany) {
        $_SESSION['error'] = 'Musisz być zalogowany, aby polubić quiz.';
        header('Location: login.php');
        exit();
    }

    if ($user_liked_quiz) {
        $stmt_unlike = $db->prepare("DELETE FROM polubione_quizy WHERE user_id = ? AND quiz_id = ?");
        if ($stmt_unlike) {
            $stmt_unlike->bind_param("ii", $user_id, $quiz_id);
            if ($stmt_unlike->execute()) {
                $_SESSION['success'] = 'Usunięto polubienie quizu.';
            } else {
                $_SESSION['error'] = 'Błąd podczas usuwania polubienia: ' . $stmt_unlike->error;
            }
            $stmt_unlike->close();
        } else {
            $_SESSION['error'] = 'Błąd przygotowania zapytania (usuwanie polubienia): ' . $db->error;
        }
    } else {
        $stmt_like = $db->prepare("INSERT INTO polubione_quizy (user_id, quiz_id) VALUES (?, ?)");
        if ($stmt_like) {
            $stmt_like->bind_param("ii", $user_id, $quiz_id);
            if ($stmt_like->execute()) {
                $_SESSION['success'] = 'Quiz został polubiony!';
            } else {
                if ($db->errno == 1062) {
                    $_SESSION['error'] = 'Już polubiłeś ten quiz.';
                } else {
                    $_SESSION['error'] = 'Błąd podczas polubienia quizu: ' . $stmt_like->error;
                }
            }
            $stmt_like->close();
        } else {
            $_SESSION['error'] = 'Błąd przygotowania zapytania (dodawanie polubienia): ' . $db->error;
        }
    }
    header('Location: quizzDetails.php?id=' . $quiz_id);
    exit();
}

// ----------- ZGŁOSZENIE (REPORT) SYSTEM DOPASOWANY DO BAZY --------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    if (!$zalogowany) {
        $_SESSION['error'] = 'Musisz być zalogowany, aby zgłosić quiz.';
        header('Location: login.php');
        exit();
    }

    $report_text = trim($_POST['report_text'] ?? '');

    if (empty($report_text)) {
        $_SESSION['error'] = 'Treść zgłoszenia nie może być pusta.';
    } else {
        // ZGODNIE Z TWOJĄ BAZĄ: tabela "zgłoszenie", kolumny "Treść", "data_utworzenia", "user_id", "quiz_id"
        $stmt_insert_report = $db->prepare("INSERT INTO `zgłoszenie` (`Treść`, `data_utworzenia`, `user_id`, `quiz_id`) VALUES (?, NOW(), ?, ?)");
        if ($stmt_insert_report) {
            $stmt_insert_report->bind_param("sii", $report_text, $user_id, $quiz_id);
            if ($stmt_insert_report->execute()) {
                $_SESSION['success'] = 'Zgłoszenie zostało wysłane.';
            } else {
                $_SESSION['error'] = 'Wystąpił błąd podczas wysyłania zgłoszenia: ' . $stmt_insert_report->error;
            }
            $stmt_insert_report->close();
        } else {
            $_SESSION['error'] = 'Wystąpił błąd podczas przygotowywania zapytania: ' . $db->error;
        }
    }
    header('Location: quizzDetails.php?id=' . $quiz_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="szczegóły quizu, podgląd quizu"/>
	<meta name="description" content="Szczegóły Quizu - Kto Pytał"/>
	<meta name="author" content="Same sigmy team"/>
	<meta name="robots" content="none"/>
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">
	<link rel="stylesheet" href="style/quizzDetails.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Szczegóły Quizu - Kto Pytał</title>
	<style>
        .report-modal { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,.5); backdrop-filter: blur(2px); justify-content:center; align-items: center; z-index: 9000;}
        .report-modal-content { background: #fff; border-radius: 16px; max-width:400px; width:90%; padding:32px 20px 24px 20px; box-shadow:0 10px 32px rgba(0,0,0,0.16);}
        .report-modal-content h2 { font-size: 1.3rem; margin-bottom: 12px; color:#d32f2f;}
        .report-modal-content textarea { width:100%; min-height:100px; border-radius:8px; padding:10px; border:1px solid #eee; margin-bottom:20px; resize:vertical; font-family:inherit;}
        .report-modal-content .btn-container { display:flex;gap:16px;justify-content:flex-end;}
        .close-report-btn { background:#eee; color:#222; border:none; padding:8px 22px; border-radius:8px; cursor:pointer;}
        .close-report-btn:hover {background: #ddd;}
        .submit-report-btn { background: #d32f2f; color:#fff; border:none; padding:8px 22px; border-radius:8px; cursor:pointer;}
        .submit-report-btn:hover {background: #b71c1c;}
        .report-link {display: flex;align-items: center;gap:6px;color:#d32f2f;text-decoration:none;font-weight:500;transition:all 0.2s;position:absolute; right:1rem; top:1rem;}
        .report-link:hover {color: #b71c1c; transform:translateY(-2px);}
        .report-link svg {width:21px; height:21px;}
        @media(max-width: 640px) { .report-modal-content {max-width:96vw;} }
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
            <li><a href="index.php">Strona główna</a></li>
            <li><a id="selected-page" href="quizzCreator.php">Stwórz Quiz</a></li>
            <li><a href="explore.php">Odkrywaj</a></li>
            <li><a href="ranking.php">Ranking</a></li>
            <?php if ($zalogowany): ?>
                <li><a href="history.php">Historia</a></li>
                <li><a href="profile.php">Profil</a></li>
            <?php endif; ?>
		</ul>
		<div class="mobile-auth">
            <?php if ($zalogowany): ?>
				<form method="post" action="php/logout.php">
					<button type="submit">Wyloguj</button>
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
        <li><a href="index.php">Strona główna</a></li>
        <li><a id="selected-page" href="quizzCreator.php">Stwórz Quiz</a></li>
        <li><a href="explore.php">Odkrywaj</a></li>
        <li><a href="ranking.php">Ranking</a></li>
        <?php if ($zalogowany): ?>
            <li><a href="history.php">Historia</a></li>
            <li><a href="profile.php">Profil</a></li>
        <?php endif; ?>
	</nav>
	<div class="header-auth">
        <?php if ($zalogowany): ?>
			<form method="post" action="php/logout.php" class="logout-form">
				<button type="submit" class="logout-btn">Wyloguj</button>
			</form>
        <?php else: ?>
			<a href="#" id="open-login" class="signin-link">Zaloguj się</a>
        <?php endif; ?>
	</div>
</header>

<main class="quiz-details-main">
	<div class="quiz-details-container">

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="message-container error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="message-container success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>

		<div class="quiz-info-card">
			<div class="quiz-header">
				<h1 class="quiz-title"><?php echo htmlspecialchars($quiz['tytul']); ?></h1>
				<div class="quiz-meta">
					<span class="quiz-author">Autor: <?php echo htmlspecialchars($quiz['autor_nazwa']); ?></span>
				</div>
			</div>
            <?php if ($zalogowany): ?>
				<a href="#" class="report-link" id="open-report-modal" title="Zgłoś quiz">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M18 8V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v3"/>
						<path d="M2 12h20"/>
						<path d="M18 16v5a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-5"/>
						<circle cx="12" cy="16" r="2"/>
					</svg>
					Zgłoś Quiz
				</a>
            <?php endif; ?>
			<div class="quiz-description">
				<h2>O tym quizie</h2>
				<p><?php echo nl2br(htmlspecialchars($quiz['opis'])); ?></p>
			</div>
			<div class="quiz-stats">
				<div class="stat-item">
					<span class="stat-number"><?php echo $num_questions; ?></span>
					<span class="stat-label">Pytania</span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo $estimated_minutes; ?></span>
					<span class="stat-label">Minuty</span>
				</div>
				<div class="stat-item like-section">
					<form method="POST" action="quizzDetails.php?id=<?php echo $quiz_id; ?>">
						<button type="submit" name="toggle_like" class="like-button <?php echo $user_liked_quiz ? 'liked' : ''; ?>" <?php echo !$zalogowany ? 'disabled title="Zaloguj się, aby polubić quiz"' : ''; ?>>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $user_liked_quiz ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
							</svg>
						</button>
					</form>
					<span class="like-count"><?php echo $total_likes; ?></span>
					<span class="stat-label">Polubienia</span>
				</div>
			</div>
		</div>

		<div class="quiz-actions">
			<div class="action-buttons">
				<a href="quizzQuestions.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polygon points="5,3 19,12 5,21"/>
					</svg>
					Rozpocznij quiz
				</a>

				<a href="explore.php" class="btn btn-secondary">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="m12 19-7-7 7-7"/>
						<path d="M19 12H5"/>
					</svg>
					Wróć
				</a>
			</div>
		</div>

		<div class="comments-section">
			<h2>Komentarze</h2>

            <?php if ($zalogowany): ?>
				<form action="quizzDetails.php?id=<?php echo $quiz_id; ?>" method="POST" class="comment-form">
					<textarea name="comment_text" placeholder="Podziel się swoimi przemyśleniami o tym quizie..." required></textarea>
					<button type="submit" name="submit_comment">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="m22 2-7 20-4-9-9-4z"/>
							<path d="M22 2L11 13"/>
						</svg>
						Dodaj komentarz
					</button>
				</form>
            <?php else: ?>
				<div class="login-prompt">
					<p>Musisz być <a href="#" id="open-login-comment">zalogowany</a>, aby dodać komentarz.</p>
				</div>
            <?php endif; ?>

			<div class="comment-list">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
						<div class="comment-item">
							<div class="comment-header">
								<div class="comment-author"><?php echo htmlspecialchars($comment['user_nazwa']); ?></div>
								<div class="comment-date"><?php echo date('j F Y • G:i', strtotime($comment['data_utworzenia'])); ?></div>
							</div>
							<p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['treść'])); ?></p>
						</div>
                    <?php endforeach; ?>
                <?php else: ?>
					<div class="no-comments">
						<div class="empty-icon">💭</div>
						<p>Brak komentarzy. Bądź pierwszy i podziel się swoimi przemyśleniami!</p>
					</div>
                <?php endif; ?>
			</div>
		</div>

	</div>
</main>

<?php if ($zalogowany): ?>
	<!-- Report Modal -->
	<div class="report-modal" id="report-modal">
		<div class="report-modal-content">
			<h2>Zgłoś Quiz</h2>
			<form action="quizzDetails.php?id=<?php echo $quiz_id; ?>" method="POST">
				<textarea name="report_text" placeholder="Opisz, dlaczego zgłaszasz ten quiz..." required></textarea>
				<div class="btn-container">
					<button type="button" class="close-report-btn" id="close-report-modal">Anuluj</button>
					<button type="submit" name="submit_report" class="submit-report-btn">Wyślij Zgłoszenie</button>
				</div>
			</form>
		</div>
	</div>
<?php endif; ?>

<footer>
	<div class="footer-content">
		<div class="footer-section">
			<h4>Kto Pytał</h4>
			<p>Ułatwiamy tworzenie i udostępnianie quizów. Twórz angażujące quizy, które zachwycą Twoją publiczność.</p>
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
				<li>Skontaktuj się z nami</li>
				<li>Polityka prywatności</li>
				<li>Regulamin</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Śledź nas</h4>
			<ul>
				<li>Facebook</li>
				<li>Twitter</li>
				<li>Instagram</li>
				<li>LinkedIn</li>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<p>© 2025 Kto Pytał. Wszelkie prawa zastrzeżone.</p>
	</div>
</footer>

<script src="js/mobile-menu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animacje (przeniesione bez zmian)
        const cards = document.querySelectorAll('.quiz-info-card, .quiz-actions, .comments-section');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 200);
        });

        // Hover effects for buttons
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Comment form enhancements
        const commentForm = document.querySelector('.comment-form');
        if (commentForm) {
            const textarea = commentForm.querySelector('textarea');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
            textarea.addEventListener('input', function() {
                const remaining = 500 - this.value.length;
                if (remaining < 50) {
                    this.style.borderColor = remaining < 0 ? 'var(--color-danger)' : 'var(--color-warning)';
                } else {
                    this.style.borderColor = 'var(--color-gray-200)';
                }
            });
        }

        // Like button animation
        const likeButton = document.querySelector('.like-button');
        if (likeButton && !likeButton.disabled) {
            likeButton.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        }

        // Handle the "Sign In" link for comments (if not logged in)
        const openLoginComment = document.getElementById('open-login-comment');
        if (openLoginComment) {
            openLoginComment.addEventListener('click', function(event) {
                event.preventDefault();
                window.location.href = 'login.php';
            });
        }

        // Report Modal
        const openReportModalBtn = document.getElementById('open-report-modal');
        const closeReportModalBtn = document.getElementById('close-report-modal');
        const reportModal = document.getElementById('report-modal');

        if (openReportModalBtn && reportModal) {
            openReportModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                reportModal.style.display = 'flex';
            });
        }
        if (closeReportModalBtn && reportModal) {
            closeReportModalBtn.addEventListener('click', function() {
                reportModal.style.display = 'none';
            });
        }
        if (reportModal) {
            reportModal.addEventListener('click', function(e) {
                if (e.target === reportModal) {
                    reportModal.style.display = 'none';
                }
            });
        }

        // Animate comments on scroll
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const commentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateX(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.comment-item').forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = 'all 0.6s ease';
            commentObserver.observe(item);
        });
    });
</script>

</body>
</html>

