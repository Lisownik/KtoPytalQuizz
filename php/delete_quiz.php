<?php
session_start();
// Włączanie raportowania błędów TYLKO DLA DEBUGOWANIA
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("../config/db.php"); // Upewnij się, że ścieżka jest prawidłowa

header('Content-Type: application/json'); // Ustawiamy nagłówek, że odpowiadamy JSONem

$response = ['success' => false, 'message' => ''];

// 1. Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

// 2. Sprawdzenie, czy user_id jest dostępne w sesji
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User ID not found in session. Please log in again.';
    echo json_encode($response);
    exit();
}

// 3. Sprawdzenie, czy quiz_id zostało przesłane
if (!isset($_POST['quiz_id'])) {
    $response['message'] = 'Quiz ID not provided.';
    echo json_encode($response);
    exit();
}

$quiz_id = mysqli_real_escape_string($db, $_POST['quiz_id']);
$user_id = $_SESSION['user_id']; // Pobieramy user_id z sesji

// 4. Sprawdzenie połączenia z bazą danych (jeśli nie jest obsługiwane wcześniej w db.php)
if (mysqli_connect_errno()) {
    $response['message'] = 'Could not connect to database: ' . mysqli_connect_error();
    echo json_encode($response);
    exit();
}

// Rozpoczynamy transakcję dla bezpieczeństwa danych
mysqli_begin_transaction($db);

try {
    // 5. Sprawdzenie, czy quiz należy do zalogowanego użytkownika
    $check_ownership_query = "SELECT quiz_id FROM Quiz WHERE quiz_id = '$quiz_id' AND user_id = '$user_id'";
    $ownership_result = mysqli_query($db, $check_ownership_query);

    if (!$ownership_result || mysqli_num_rows($ownership_result) == 0) {
        throw new Exception("Quiz not found or you are not authorized to delete this quiz.");
    }

    // Ze względu na brak ON DELETE CASCADE, musimy usunąć zależności ręcznie:
    // WAŻNE: Kolejność usuwania jest kluczowa z powodu kluczy obcych!
    // Usuwamy od "najgłębszych" zależności (dzieci) do "najpłytszych" (rodziców).

    // Usuń odpowiedzi powiązane z pytaniami tego quizu
    // Używamy JOIN, żeby upewnić się, że usuwamy tylko odpowiedzi z PYTAŃ tego konkretnego quizu
    $delete_answers_query = "
        DELETE Odpowiedzi
        FROM Odpowiedzi
        JOIN Pytanie ON Odpowiedzi.pytanie_id = Pytanie.pytanie_id
        WHERE Pytanie.quiz_id = '$quiz_id'";
    if (!mysqli_query($db, $delete_answers_query)) {
        throw new Exception("Error deleting answers: " . mysqli_error($db));
    }

    // Usuń komentarze dla tego quizu
    $delete_comments_query = "DELETE FROM Komentarze WHERE quiz_id = '$quiz_id'";
    if (!mysqli_query($db, $delete_comments_query)) {
        throw new Exception("Error deleting comments: " . mysqli_error($db));
    }

    // Usuń tagi powiązane z tym quizem
    $delete_quiz_tags_query = "DELETE FROM quiz_tag WHERE quiz_id = '$quiz_id'";
    if (!mysqli_query($db, $delete_quiz_tags_query)) {
        throw new Exception("Error deleting quiz tags: " . mysqli_error($db));
    }

    // Usuń zgłoszenia dla tego quizu
    $delete_reports_query = "DELETE FROM Zgłoszenie WHERE quiz_id = '$quiz_id'";
    if (!mysqli_query($db, $delete_reports_query)) {
        throw new Exception("Error deleting reports: " . mysqli_error($db));
    }

    // Usuń pytania powiązane z tym quizem
    $delete_questions_query = "DELETE FROM Pytanie WHERE quiz_id = '$quiz_id'";
    if (!mysqli_query($db, $delete_questions_query)) {
        throw new Exception("Error deleting questions: " . mysqli_error($db));
    }

    // Na końcu usuń sam quiz
    $delete_quiz_query = "DELETE FROM Quiz WHERE quiz_id = '$quiz_id'";
    if (!mysqli_query($db, $delete_quiz_query)) {
        throw new Exception("Error deleting quiz: " . mysqli_error($db));
    }

    // Jeśli wszystko poszło dobrze, zatwierdź transakcję
    mysqli_commit($db);
    $response['success'] = true;
    $response['message'] = 'Quiz and all related data deleted successfully.';

} catch (Exception $e) {
    // W przypadku błędu, wycofaj transakcję
    mysqli_rollback($db);
    $response['message'] = $e->getMessage();
}

mysqli_close($db);
echo json_encode($response);
exit();
?>