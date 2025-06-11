<?php
session_start();
require_once("../config/db.php"); // Adjust path if necessary

if (mysqli_connect_errno()) {
    $_SESSION['error'] = 'Nie udało się połączyć z bazą danych: ' . mysqli_connect_error();
    header('Location: ../quizzCreator.php');
    exit();
}

// Function to sanitize input data
function sanitize_data($data) {
    return htmlspecialchars(trim($data));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store all submitted form data in session for repopulation
    $_SESSION['form_data'] = $_POST;

    // Basic validation: Check if quiz title and description are set
    if (empty($_POST['quiz_title'])) {
        $_SESSION['error'] = 'Tytuł quizu jest wymagany.';
        header('Location: ../quizzCreator.php');
        exit();
    }
    if (empty($_POST['quiz_description'])) {
        $_SESSION['error'] = 'Opis quizu jest wymagany.';
        header('Location: ../quizzCreator.php');
        exit();
    }

    // Check if at least one question exists
    if (!isset($_POST['questions']) || empty($_POST['questions'])) {
        $_SESSION['error'] = 'Quiz musi zawierać przynajmniej jedno pytanie.';
        header('Location: ../quizzCreator.php');
        exit();
    }

    $quiz_title = sanitize_data($_POST['quiz_title']);
    $quiz_description = sanitize_data($_POST['quiz_description']);
    $questions = $_POST['questions']; // Array of questions

    // Validate each question and its options
    foreach ($questions as $q_index => $question) {
        if (empty($question['text'])) {
            $_SESSION['error'] = 'Pytanie ' . ($q_index + 1) . ' nie może być puste.';
            header('Location: ../quizzCreator.php');
            exit();
        }

        if (!isset($question['options']) || count(array_filter($question['options'])) < 2) { // At least 2 non-empty options
            $_SESSION['error'] = 'Pytanie ' . ($q_index + 1) . ' musi mieć przynajmniej dwie niepuste opcje.';
            header('Location: ../quizzCreator.php');
            exit();
        }

        foreach ($question['options'] as $o_index => $option_text) {
            if ($o_index < 2 && empty($option_text)) { // First two options are required
                $_SESSION['error'] = 'Opcje 1 i 2 dla pytania ' . ($q_index + 1) . ' są wymagane.';
                header('Location: ../quizzCreator.php');
                exit();
            }
        }

        if (!isset($question['correct']) || empty($question['correct'])) {
            $_SESSION['error'] = 'Pytanie ' . ($q_index + 1) . ' musi mieć wybraną przynajmniej jedną poprawną odpowiedź.';
            header('Location: ../quizzCreator.php');
            exit();
        }
    }

    // Assuming user_id is available in the session after login
    $user_id = $_SESSION['user_id'] ?? null; // You need to set $_SESSION['user_id'] on login

    if (!$user_id) {
        $_SESSION['error'] = 'Musisz być zalogowany, aby utworzyć quiz.';
        header('Location: ../index.php'); // Redirect to login page if not logged in
        exit();
    }

    // Determine quiz draft status
    // If 'publish_quiz' button was clicked, it's not a draft (0). Otherwise, it's a draft (1).
    $is_draft = isset($_POST['publish_quiz']) ? 0 : 1; // 0 for published, 1 for draft

    // Start a transaction
    $db->begin_transaction();

    try {
        // Insert quiz into 'quiz' table
        $default_difficulty = 1; // Domyślny poziom trudności
        $stmt_quiz = $db->prepare("INSERT INTO quiz (user_id, nazwa, opis, poziom_trudnosci, data_utworzenia, draft) VALUES (?, ?, ?, ?, NOW(), ?)");
        if (!$stmt_quiz) {
            throw new Exception($db->error);
        }
        // POPRAWKA: Zmieniono typ parametru dla 'opis' z 'i' na 's'
        $stmt_quiz->bind_param("issii", $user_id, $quiz_title, $quiz_description, $default_difficulty, $is_draft);
        $stmt_quiz->execute();
        $quiz_id = $db->insert_id;
        $stmt_quiz->close();

        // Insert questions and answers
        foreach ($questions as $q_index => $question) {
            $question_text = sanitize_data($question['text']);
            $stmt_question = $db->prepare("INSERT INTO pytanie (quiz_id, Treść) VALUES (?, ?)");
            if (!$stmt_question) {
                throw new Exception($db->error);
            }
            $stmt_question->bind_param("is", $quiz_id, $question_text);
            $stmt_question->execute();
            $pytanie_id = $db->insert_id;
            $stmt_question->close();

            foreach ($question['options'] as $o_index => $option_text) {
                $option_text_sanitized = sanitize_data($option_text);
                $is_correct = in_array($o_index, $question['correct'] ?? []) ? 1 : 0;

                // Only insert non-empty options
                if (empty($option_text_sanitized)) {
                    continue;
                }

                $stmt_answer = $db->prepare("INSERT INTO odpowiedzi (pytanie_id, treść_odpowiedzi, czy_poprawna) VALUES (?, ?, ?)");
                if (!$stmt_answer) {
                    throw new Exception($db->error);
                }
                $stmt_answer->bind_param("isi", $pytanie_id, $option_text_sanitized, $is_correct);
                $stmt_answer->execute();
                $stmt_answer->close();
            }
        }

        $db->commit();
        unset($_SESSION['form_data']); // Clear form data on successful submission
        $_SESSION['success'] = 'Quiz "' . $quiz_title . '" został ' . ($is_draft === 0 ? 'opublikowany!' : 'zapisany jako szkic!');
        header('Location: ../profile.php'); // Redirect to profile or quiz details page
        exit();

    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = 'Wystąpił błąd podczas zapisywania quizu: ' . $e->getMessage();
        header('Location: ../quizzCreator.php');
        exit();
    }
} else {
    // If accessed directly without POST
    header('Location: ../quizzCreator.php');
    exit();
}
?>