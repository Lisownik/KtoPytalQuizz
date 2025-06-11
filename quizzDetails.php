<?php
session_start();
require_once("config/db.php"); // Assuming your db.php is in the config folder

// Check if a user is logged in
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
$user_id = $_SESSION['user_id'] ?? null; // Get user ID from session if logged in

// Check for database connection error
if (mysqli_connect_errno()) {
    // Optionally, set an error message and redirect if connection fails
    $_SESSION['error'] = 'Nie udało się połączyć z bazą danych: ' . mysqli_connect_error();
    header('Location: index.php'); // Redirect to a suitable error page or home
    exit();
}

$quiz_id = null;
$quiz = null; // Initialize quiz variable
$user_liked_quiz = false; // Initialize state for user's like
$total_likes = 0; // Initialize total likes count

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

        // Fetch comments for the quiz
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

        // Get total likes for this quiz
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

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!$zalogowany) {
        $_SESSION['error'] = 'Musisz być zalogowany, aby dodać komentarz.';
        header('Location: login.php'); // Redirect to login page
        exit();
    }

    $comment_text = trim($_POST['comment_text'] ?? '');

    if (empty($comment_text)) {
        $_SESSION['error'] = 'Treść komentarza nie może być pusta.';
    } else {
        $stmt_insert_comment = $db->prepare("INSERT INTO komentarze (quiz_id, user_id, treść, data_utworzenia) VALUES (?, ?, ?, NOW())");
        if ($stmt_insert_comment) {
            $stmt_insert_comment->bind_param("iis", $quiz_id, $user_id, $comment_text);
            if ($stmt_insert_comment->execute()) {
                $_SESSION['success'] = 'Komentarz został dodany.';
                // Redirect to prevent form re-submission and clear POST data
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
    // If there was an error, redirect back to the page to display the error message
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
        // Unlike the quiz
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
        // Like the quiz
        $stmt_like = $db->prepare("INSERT INTO polubione_quizy (user_id, quiz_id) VALUES (?, ?)");
        if ($stmt_like) {
            $stmt_like->bind_param("ii", $user_id, $quiz_id);
            if ($stmt_like->execute()) {
                $_SESSION['success'] = 'Quiz został polubiony!';
            } else {
                // Check if the error is due to duplicate entry (user already liked it)
                if ($db->errno == 1062) { // MySQL error code for duplicate entry for unique key
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
	<style>
        /* Basic styling for the comments section */
        .comments-section {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .comments-section h2 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
        }

        .comment-form button {
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .comment-form button:hover {
            background-color: #45a049;
        }

        .comment-list {
            margin-top: 20px;
        }

        .comment-item {
            background-color: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .comment-author {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .comment-date {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 10px;
        }

        .comment-text {
            line-height: 1.6;
            color: #333;
        }

        /* Error/Success messages */
        .message-container {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Styling for the like button */
        .like-section {
            display: flex;
            align-items: center;
            margin-left: 20px; /* Adjust spacing as needed */
        }

        .like-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            font-size: 1.2em; /* Adjust icon size */
            color: #ccc; /* Default color for unliked */
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .like-button.liked {
            color: #e0245e; /* Red color for liked state */
        }

        .like-button:hover {
            transform: scale(1.1);
        }

        .like-count {
            margin-left: 5px;
            font-weight: 600;
            color: #555;
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
				<div class="stat-item like-section">
					<form method="POST" action="quizzDetails.php?id=<?php echo $quiz_id; ?>" style="display:inline;">
						<button type="submit" name="toggle_like" class="like-button <?php echo $user_liked_quiz ? 'liked' : ''; ?>" <?php echo !$zalogowany ? 'disabled title="Zaloguj się, aby polubić quiz"' : ''; ?>>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $user_liked_quiz ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
							</svg>
						</button>
					</form>
					<span class="like-count"><?php echo $total_likes; ?></span>
					<span class="stat-label">Likes</span>
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

		<div class="comments-section">
			<h2>Comments</h2>

            <?php if ($zalogowany): /* Check if user is logged in to allow commenting*/ ?>
				<form action="quizzDetails.php?id=<?php echo $quiz_id; ?>" method="POST" class="comment-form">
					<textarea name="comment_text" placeholder="Add your comment..." required></textarea>
					<button type="submit" name="submit_comment">Submit Comment</button>
				</form>
            <?php else: ?>
				<p>You must be logged in to add a comment. <a href="#" id="open-login-comment">Sign In</a></p>
            <?php endif; ?>

			<div class="comment-list">
                <?php if (!empty($comments)): /* Display comments if any exist*/ ?>
                    <?php foreach ($comments as $comment): ?>
						<div class="comment-item">
							<div class="comment-author"><?php echo htmlspecialchars($comment['user_nazwa']); ?></div>
							<div class="comment-date"><?php echo date('F j, Y, g:i a', strtotime($comment['data_utworzenia'])); ?></div>
							<p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['treść'])); ?></p>
						</div>
                    <?php endforeach; ?>
                <?php else: ?>
					<p>No comments yet. Be the first to comment!</p>
                <?php endif; ?>
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
			<h4>Support</li>
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
        const cards = document.querySelectorAll('.quiz-info-card, .quiz-actions, .comments-section'); // Include comments section for animation

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

        // Handle the "Sign In" link for comments (if not logged in)
        const openLoginComment = document.getElementById('open-login-comment');
        if (openLoginComment) {
            openLoginComment.addEventListener('click', function(event) {
                event.preventDefault();
                // Assuming you have a modal or redirect for login
                window.location.href = 'login.php'; // Redirect to your login page
            });
        }
    });
</script>

</body>
</html>