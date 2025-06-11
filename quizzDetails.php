<?php
session_start();
require_once("config/db.php"); // Assuming your db.php is in the config folder

// Check if a user is logged in
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Check for database connection error
if (mysqli_connect_errno()) {
    // Optionally, set an error message and redirect if connection fails
    $_SESSION['error'] = 'Nie udało się połączyć z bazą danych: ' . mysqli_connect_error();
    header('Location: index.php'); // Redirect to a suitable error page or home
    exit();
}

$quiz_id = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $quiz_id = (int)$_GET['id'];

    // Fetch quiz details
    // Table 'quiz' has 'nazwa' for title and 'opis' for description.
    // Table 'uzytkownicy' has 'Nazwa' for username.
    $stmt = $db->prepare("SELECT q.nazwa AS tytul, q.opis, u.Nazwa AS autor_nazwa FROM quiz q JOIN uzytkownicy u ON q.user_id = u.user_id WHERE q.quiz_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quiz = $result->fetch_assoc();
        $stmt->close();
    }

    // Fetch number of questions from the 'pytanie' table
    $num_questions = 0;
    if ($quiz) { // Only proceed if quiz details were found
        $stmt_questions = $db->prepare("SELECT COUNT(*) AS total_questions FROM pytanie WHERE quiz_id = ?");
        if ($stmt_questions) {
            $stmt_questions->bind_param("i", $quiz_id);
            $stmt_questions->execute();
            $result_questions = $stmt_questions->get_result();
            $row_questions = $result_questions->fetch_assoc();
            $num_questions = $row_questions['total_questions'];
            $stmt_questions->close();
        }
    }

    // Calculate estimated time (1.5 minutes per question)
    $estimated_minutes = $num_questions * 1.5;

} else {
    // Redirect or display an error if no valid quiz ID is provided
    $_SESSION['error'] = 'Nieprawidłowy identyfikator quizu.';
    header('Location: explore.php'); // Or a generic error page
    exit();
}

// If quiz not found, redirect
if (!$quiz) {
    $_SESSION['error'] = 'Quiz o podanym ID nie istnieje.';
    header('Location: explore.php'); // Or a generic error page
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="quiz details, quiz preview"/>
	<meta name="description" content="Quiz Details - Kto Pytał"/>
	<meta name="author" content="Same sigmy team"/>
	<meta name="robots" content="none"/>
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">
	<link rel="stylesheet" href="style/quizzDetails.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Quiz Details - Kto Pytał</title>
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
            <?php if ($zalogowany): /* Check if user is logged in*/ ?>
				<form method="post" action="php/logout.php">
					<button type="submit">Logout</button>
				</form>
            <?php else: ?>
				<a href="#" class="mobile-login-btn">Sign In</a>
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
			<li><a href="quizzCreator.php">Create Quizz</a></li>
			<li><a href="explore.php">Explore</a></li>
			<li><a href="profile.php">Profile</a></li>
			<li><a href="history.php">History</a></li>
		</ul>
	</nav>
	<div class="header-auth">
        <?php if ($zalogowany): /* Check if user is logged in*/ ?>
			<form method="post" action="php/logout.php" class="logout-form">
				<button type="submit" class="logout-btn">Logout</button>
			</form>
        <?php else: ?>
			<a href="#" id="open-login" class="signin-link">Sign In</a>
        <?php endif; ?>
	</div>
</header>

<main class="quiz-details-main">
	<div class="quiz-details-container">

		<div class="quiz-info-card">
			<div class="quiz-header">
				<h1 class="quiz-title"><?php echo htmlspecialchars($quiz['tytul']); /* Display quiz title*/ ?></h1>
				<div class="quiz-meta">
					<span class="quiz-author">By: <?php echo htmlspecialchars($quiz['autor_nazwa']); /* Display quiz author*/ ?></span>
				</div>
			</div>

			<div class="quiz-description">
				<h2>About This Quiz</h2>
				<p><?php echo nl2br(htmlspecialchars($quiz['opis'])); /* Display quiz description*/ ?></p>
			</div>

			<div class="quiz-stats">
				<div class="stat-item">
					<span class="stat-number"><?php echo $num_questions; /* Display number of questions*/ ?></span>
					<span class="stat-label">Questions</span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo $estimated_minutes; /* Display estimated minutes*/ ?></span>
					<span class="stat-label">Minutes</span>
				</div>
			</div>
		</div>

		<div class="quiz-actions">
			<div class="action-buttons">
				<a href="quizzQuestions.php?id=<?php echo $quiz_id; /* Pass quiz ID to quiz.php*/ ?>" class="btn btn-primary btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polygon points="5,3 19,12 5,21"/>
					</svg>
					Rozwiąż Quiz
				</a>

				<a href="explore.php" class="btn btn-secondary">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="m12 19-7-7 7-7"/>
						<path d="M19 12H5"/>
					</svg>
					Cofnij
				</a>
			</div>
		</div>

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
		<p>© 2025 Kto Pytał. All rights reserved.</p>
	</div>
</footer>

<script src="js/mobile-menu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add smooth animations
        const cards = document.querySelectorAll('.quiz-info-card, .quiz-actions');

        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 200);
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });
</script>

</body>
</html>