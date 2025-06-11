<?php
session_start();
// Włącz wyświetlanie błędów PHP na potrzeby debugowania (TYLKO W TRAKCIE ROZWOJU, NIE NA PRODUKCJI!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("config/db.php");

$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;

// Pobieramy user_id z sesji, jeśli użytkownik jest zalogowany
$user_id = $zalogowany ? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null) : null;
// Zmiana: Dodano isset($_SESSION['user_id']) na wypadek, gdyby sesja była zalogowana, ale user_id nie było ustawione

if (mysqli_connect_errno()) {
    exit('Nie udało się połączyć z bazą danych :( ' . mysqli_connect_error());
}

$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// === Inicjalizacja/Resetowanie Quizu ===
// Sprawdź, czy quiz_id się zmieniło lub czy quiz nie jest zainicjalizowany w sesji
if (!isset($_SESSION['quiz_id']) || $_SESSION['quiz_id'] !== $quiz_id || isset($_POST['restart_quiz'])) {
    $_SESSION['quiz_id'] = $quiz_id;
    $_SESSION['quiz_current_question_index'] = 0;
    $_SESSION['quiz_user_answers'] = []; // { question_id => ['selected_ids' => [], 'is_correct' => false, 'is_checked' => false] }
    $_SESSION['quiz_score'] = 0;
    $_SESSION['quiz_feedback_message'] = '';
    $_SESSION['quiz_completed_and_saved'] = false; // Nowa flaga do śledzenia, czy wynik został zapisany
}

$quiz_data = null;
$questions = [];

if ($quiz_id > 0) {
    // Fetch quiz title
    $quiz_query = "SELECT nazwa FROM Quiz WHERE quiz_id = ?";
    $stmt = mysqli_prepare($db, $quiz_query);
    if ($stmt) { // Sprawdź, czy przygotowanie zapytania się powiodło
        mysqli_stmt_bind_param($stmt, 'i', $quiz_id);
        mysqli_stmt_execute($stmt);
        $quiz_result = mysqli_stmt_get_result($stmt);
        if ($quiz_result && mysqli_num_rows($quiz_result) > 0) {
            $quiz_data = mysqli_fetch_assoc($quiz_result);
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Błąd przygotowania zapytania quiz_query: " . mysqli_error($db));
    }


    // Fetch questions and answers
    $questions_query = "
        SELECT p.pytanie_id, p.Treść AS question_text, o.odpowiedzi_id, o.treść_odpowiedzi AS answer_text, o.czy_poprawna
        FROM Pytanie p
        JOIN Odpowiedzi o ON p.pytanie_id = o.pytanie_id
        WHERE p.quiz_id = ?
        ORDER BY p.pytanie_id, o.odpowiedzi_id;
    ";
    $stmt = mysqli_prepare($db, $questions_query);
    if ($stmt) { // Sprawdź, czy przygotowanie zapytania się powiodło
        mysqli_stmt_bind_param($stmt, 'i', $quiz_id);
        mysqli_stmt_execute($stmt);
        $questions_result = mysqli_stmt_get_result($stmt);

        if ($questions_result) {
            $current_pytanie_id = null;
            $question_item = null;
            while ($row = mysqli_fetch_assoc($questions_result)) {
                if ($row['pytanie_id'] !== $current_pytanie_id) {
                    if ($question_item !== null) {
                        $questions[] = $question_item;
                    }
                    $current_pytanie_id = $row['pytanie_id'];
                    $question_item = [
                        'id' => $row['pytanie_id'],
                        'text' => $row['question_text'],
                        'answers' => []
                    ];
                }
                $question_item['answers'][] = [
                    'id' => $row['odpowiedzi_id'],
                    'text' => $row['answer_text'],
                    'is_correct' => (bool)$row['czy_poprawna']
                ];
            }
            if ($question_item !== null) { // Add the last question
                $questions[] = $question_item;
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Błąd przygotowania zapytania questions_query: " . mysqli_error($db));
    }

    // Ensure $_SESSION['quiz_user_answers'] has entries for all questions
    // This makes sure array indexes match question indexes
    if (empty($_SESSION['quiz_user_answers']) || count($_SESSION['quiz_user_answers']) !== count($questions)) {
        $_SESSION['quiz_user_answers'] = array_fill(0, count($questions), ['selected_ids' => [], 'is_correct' => false, 'is_checked' => false]);
    }

}

$total_questions = count($questions);

// === Obsługa formularza (POST) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_idx = &$_SESSION['quiz_current_question_index'];
    $user_answers = &$_SESSION['quiz_user_answers'];

    // Obsługa przycisku "Sprawdź"
    if (isset($_POST['check_answer'])) {
        // Upewnij się, że index pytania jest prawidłowy
        if ($current_idx < $total_questions) {
            $current_question = $questions[$current_idx];
            $selected_answer_ids = [];
            if (isset($_POST['answer'])) {
                // Check if it's a single answer (radio) or multiple (checkbox)
                if (is_array($_POST['answer'])) {
                    $selected_answer_ids = array_map('intval', $_POST['answer']);
                } else {
                    $selected_answer_ids = [(int)$_POST['answer']];
                }
            }

            $all_correct_answer_ids = array_map(function($a) {
                return $a['id'];
            }, array_filter($current_question['answers'], function($a) {
                return $a['is_correct'];
            }));

            $is_correct_submission = true;
            if (count($selected_answer_ids) !== count($all_correct_answer_ids)) {
                $is_correct_submission = false;
            } else {
                foreach ($selected_answer_ids as $selected_id) {
                    if (!in_array($selected_id, $all_correct_answer_ids)) {
                        $is_correct_submission = false;
                        break;
                    }
                }
            }

            // Update state for the current question
            $user_answers[$current_idx]['selected_ids'] = $selected_answer_ids;
            $user_answers[$current_idx]['is_correct'] = $is_correct_submission;
            $user_answers[$current_idx]['is_checked'] = true;

            $_SESSION['quiz_feedback_message'] = $is_correct_submission ? 'Poprawna odpowiedź!' : 'Niepoprawna odpowiedź.';

            // Recalculate score after checking
            $_SESSION['quiz_score'] = 0;
            foreach ($user_answers as $answer_data) {
                if ($answer_data['is_correct']) {
                    $_SESSION['quiz_score']++;
                }
            }
        }
    }
    // Obsługa przycisku "Następne" / "Zakończ Quiz"
	elseif (isset($_POST['next_question'])) {
        if ($current_idx < $total_questions - 1) {
            $current_idx++;
            $_SESSION['quiz_feedback_message'] = ''; // Clear feedback for new question
        } else {
            // Jesteśmy na ostatnim pytaniu i kliknięto "Następne" / "Zakończ Quiz"
            $current_idx++; // Przejdź za ostatnie pytanie, aby aktywować widok wyników
            $_SESSION['quiz_feedback_message'] = ''; // Clear feedback for results view

            // === Zapisz wynik quizu do bazy danych, jeśli użytkownik jest zalogowany i wynik nie został jeszcze zapisany ===
            // Dodatkowe sprawdzenia przed zapisem
            error_log("Próba zapisu wyniku: zalogowany=" . ($zalogowany ? 'tak' : 'nie') . ", user_id=" . ($user_id ?? 'null') . ", total_questions=" . $total_questions . ", saved_flag=" . ($_SESSION['quiz_completed_and_saved'] ? 'true' : 'false'));

            if ($zalogowany && $user_id !== null && $total_questions > 0 && !$_SESSION['quiz_completed_and_saved']) {
                $wynik_liczbowy = $_SESSION['quiz_score'];
                $maksymalny_wynik = $total_questions;
                $current_quiz_id = $_SESSION['quiz_id']; // Upewnij się, że używasz ID aktualnego quizu

                $insert_query = "INSERT INTO wyniki_quizow (user_id, quiz_id, wynik_liczbowy, maksymalny_wynik, data_rozwiazania) VALUES (?, ?, ?, ?, NOW())";
                $stmt_insert = mysqli_prepare($db, $insert_query);
                if ($stmt_insert) {
                    mysqli_stmt_bind_param($stmt_insert, 'iiii', $user_id, $current_quiz_id, $wynik_liczbowy, $maksymalny_wynik);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $_SESSION['quiz_completed_and_saved'] = true; // Ustaw flagę, że wynik został zapisany
                        error_log("Sukces: Wynik quizu zapisany do bazy danych. user_id: $user_id, quiz_id: $current_quiz_id, wynik: $wynik_liczbowy/$maksymalny_wynik");
                    } else {
                        // Logowanie błędu zapisu
                        error_log("Błąd EXECUTE zapytania INSERT: " . mysqli_stmt_error($stmt_insert));
                    }
                    mysqli_stmt_close($stmt_insert);
                } else {
                    error_log("Błąd PREPARE zapytania INSERT: " . mysqli_error($db));
                }
            } else {
                error_log("Wynik nie został zapisany: Zalogowany: " . ($zalogowany ? 'tak' : 'nie') . ", User ID: " . ($user_id ?? 'null') . ", Total Questions: " . $total_questions . ", Zapisano już: " . ($_SESSION['quiz_completed_and_saved'] ? 'tak' : 'nie'));
            }
        }
    }
    // Obsługa przycisku "Poprzednie"
	elseif (isset($_POST['previous_question'])) {
        if ($current_idx > 0) {
            $current_idx--;
            $_SESSION['quiz_feedback_message'] = ''; // Clear feedback when moving back
        }
    }

    // Redirect to self to prevent form resubmission on refresh
    header("Location: quizzQuestions.php?id=" . $quiz_id);
    exit();
}

// === Ustawienie zmiennych dla widoku ===
$current_question_index = $_SESSION['quiz_current_question_index'];
$user_answers = $_SESSION['quiz_user_answers'];
$current_score = $_SESSION['quiz_score'];
$feedback_message = $_SESSION['quiz_feedback_message'];

$display_quiz_section = true;
$display_results_section = false;

// Sprawdź, czy quiz jest zakończony (indeks bieżącego pytania poza zakresem)
if ($total_questions > 0 && $current_question_index >= $total_questions) {
    $display_quiz_section = false;
    $display_results_section = true;
} else if ($total_questions === 0) { // Obsługa quizu bez pytań
    $display_quiz_section = false;
    $display_results_section = false; // Albo specjalna wiadomość
}

// Zamykamy połączenie z bazą danych na końcu skryptu
mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="keywords" content="quiz, pytania, odpowiedzi"/>
	<meta name="description" content="Rozwiąż quiz i sprawdź swoją wiedzę"/>
	<meta name="author" content="Same sigmy team"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $quiz_data ? htmlspecialchars($quiz_data['nazwa']) : 'Quiz'; ?> - Kto Pytał</title>

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="style/universal.css">
	<link rel="stylesheet" href="style/style.css">
	<link rel="stylesheet" href="style/quizzQuestions.css">
	<style>
        .answer-option label.correct {
            background-color: #d1e7dd !important; /* Light green for correct */
            border-color: #a3cfbb !important;
        }
        .answer-option label.incorrect {
            background-color: #f8d7da !important; /* Light red for incorrect */
            border-color: #f1aebe !important;
        }
        .answer-option input[type="radio"]:disabled + label,
        .answer-option input[type="checkbox"]:disabled + label {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .feedback-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: var(--border-radius);
            font-weight: 500;
        }
        .feedback-message.correct {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .feedback-message.incorrect {
            background-color: #f8d7da;
            color: #842029;
        }
        .quiz-results {
            text-align: center;
            padding: 2rem;
            background-color: var(--color-gray-50);
            border-radius: var(--border-radius-lg);
        }
        .quiz-results h2 {
            color: var(--color-primary);
            margin-bottom: 1rem;
        }
        .quiz-results p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .quiz-results .btn-restart {
            background: var(--color-secondary);
            color: white;
        }
	</style>
</head>
<body>

<div id="auth-modal-backdrop" aria-hidden="true"></div>

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

<main class="quiz-container">
    <?php if ($quiz_id > 0 && $quiz_data && $total_questions > 0): ?>
		<div class="quiz-header">
			<div class="quiz-info">
				<h1 class="quiz-title"><?php echo htmlspecialchars($quiz_data['nazwa']); ?></h1>
				<div class="quiz-meta">
                    <?php if ($display_quiz_section): ?>
						<span class="quiz-progress" id="quiz-progress">
                            Pytanie <?php echo $current_question_index + 1; ?> z <?php echo $total_questions; ?>
                        </span>
                    <?php else: ?>
						<span class="quiz-progress" style="display: none;"></span>
                    <?php endif; ?>
				</div>
			</div>
		</div>

        <?php if ($display_quiz_section): ?>
            <?php
            // Pobierz aktualne pytanie i jego stan z sesji
            $current_question = $questions[$current_question_index];
            $current_user_answer_state = $user_answers[$current_question_index];
            $correct_answers_count = count(array_filter($current_question['answers'], function($a) { return $a['is_correct']; }));
            $input_type = $correct_answers_count > 1 ? 'checkbox' : 'radio';
            ?>
			<div class="question-section" id="question-section">
				<div class="question-card">
					<h2 class="question-text"><?php echo htmlspecialchars($current_question['text']); ?></h2>
				</div>
				<form class="answers-form" method="POST" action="quizzQuestions.php?id=<?php echo $quiz_id; ?>">
                    <?php foreach ($current_question['answers'] as $i => $answer): ?>
                        <?php
                        $is_selected = in_array($answer['id'], $current_user_answer_state['selected_ids']);
                        $is_disabled = $current_user_answer_state['is_checked'] ? 'disabled' : '';
                        $label_class = 'answer-label';
                        if ($current_user_answer_state['is_checked']) {
                            if ($answer['is_correct']) {
                                $label_class .= ' correct';
                            } elseif ($is_selected && !$answer['is_correct']) {
                                $label_class .= ' incorrect';
                            }
                        }
                        ?>
						<div class="answer-option">
							<input type="<?php echo $input_type; ?>" id="answer-<?php echo $answer['id']; ?>"
							       name="answer<?php echo ($input_type === 'checkbox' ? '[]' : ''); ?>"
							       value="<?php echo $answer['id']; ?>"
                                <?php echo $is_selected ? 'checked' : ''; ?>
                                <?php echo $is_disabled; ?>>
							<label for="answer-<?php echo $answer['id']; ?>" class="<?php echo $label_class; ?>">
								<span class="answer-letter"><?php echo chr(65 + $i); ?></span>
								<span class="answer-text"><?php echo htmlspecialchars($answer['text']); ?></span>
							</label>
						</div>
                    <?php endforeach; ?>

					<div id="feedback-area">
                        <?php if (!empty($feedback_message)): ?>
                            <?php
                            // Użyj is_correct z bieżącego stanu pytania, aby określić klasę
                            $feedback_class = $user_answers[$current_question_index]['is_correct'] ? 'correct' : 'incorrect';
                            ?>
							<div class="feedback-message <?php echo $feedback_class; ?>">
                                <?php echo htmlspecialchars($feedback_message); ?>
							</div>
                        <?php endif; ?>
					</div>

					<div class="quiz-actions">
						<button type="submit" name="previous_question" class="btn-previous"
                            <?php echo $current_question_index === 0 ? 'disabled' : ''; ?>>
							<-- Poprzednie
						</button>

                        <?php if (!$current_user_answer_state['is_checked']): ?>
							<button type="submit" name="check_answer" class="btn-next">Sprawdź</button>
                        <?php else: ?>
							<button type="submit" name="next_question" class="btn-next">
                                <?php echo ($current_question_index === $total_questions - 1) ? 'Zakończ Quiz' : 'Następne -->'; ?>
							</button>
                        <?php endif; ?>
					</div>
				</form>
			</div>
        <?php elseif ($display_results_section): ?>
			<div class="quiz-results" id="quiz-results">
				<h2>Quiz Completed!</h2>
				<p id="results-score">Twój wynik: <?php echo $current_score; ?> / <?php echo $total_questions; ?></p>
                <?php
                // Generowanie podsumowania tekstowego
                $summaryText = "Odpowiedziałeś poprawnie na {$current_score} z {$total_questions} pytań. ";
                if ($current_score === $total_questions) {
                    $summaryText .= "Gratulacje, perfekcyjny wynik!";
                } elseif ($current_score >= $total_questions * 0.7) {
                    $summaryText .= "Świetna robota!";
                } elseif ($current_score >= $total_questions * 0.4) {
                    $summaryText .= "Całkiem nieźle, potrenuj jeszcze trochę.";
                } else {
                    $summaryText .= "Spróbuj ponownie, na pewno pójdzie lepiej!";
                }
                ?>
				<p id="results-summary"><?php echo $summaryText; ?></p>
				<form method="POST" action="quizzQuestions.php?id=<?php echo $quiz_id; ?>">
					<button type="submit" name="restart_quiz" class="btn-next btn-restart">Restart Quiz</button>
				</form>
				<a href="explore.php" class="btn-next" style="margin-left: 10px; background-color: var(--color-primary);">Explore More Quizzes</a>
			</div>
        <?php endif; ?>

    <?php elseif ($quiz_id > 0 && !$quiz_data): ?>
		<div class="quiz-results">
			<h2>Quiz Not Found</h2>
			<p>The quiz you are looking for does not exist or has been removed.</p>
			<a href="explore.php" class="btn-next" style="background-color: var(--color-primary);">Browse Quizzes</a>
		</div>
    <?php else: ?>
		<div class="quiz-results">
			<h2>No Quiz Selected</h2>
			<p>Please select a quiz to start.</p>
			<a href="explore.php" class="btn-next" style="background-color: var(--color-primary);">Browse Quizzes</a>
		</div>
    <?php endif; ?>
</main>

<footer>
	<div class="footer-content">
	</div>
	<div class="footer-bottom">
		<p>© <?php echo date('Y'); ?> Kto Pytał. All rights reserved.</p>
	</div>
</footer>
<script src="js/auth.js"></script>
<script src="js/mobile-menu.js"></script>
</body>
</html>