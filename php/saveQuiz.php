<?php
session_start();

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['zalogowany']) || !$_SESSION['zalogowany']) {
    header('Location: ../index.php');
    exit();
}

// Konfiguracja bazy danych - dostosuj do swoich ustawień
$host = 'localhost';
$username = 'root'; // Zmień na swój username
$password = ''; // Zmień na swoje hasło
$database = 'ktopytalquizz'; // Nazwa bazy danych z SQL

// Połączenie z bazą danych
$db = mysqli_connect($host, $username, $password, $database);

// Sprawdzenie połączenia
if (!$db) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ustawienie kodowania
mysqli_set_charset($db, "utf8mb4");

// Sprawdzenie czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pobieranie danych z formularza
    $quiz_nazwa = mysqli_real_escape_string($db, trim($_POST['quiz_title'] ?? ''));
    $quiz_opis = mysqli_real_escape_string($db, trim($_POST['quiz_description'] ?? ''));
    $questions = $_POST['questions'] ?? [];

    // Sprawdzenie czy typ akcji (publish czy draft)
    $is_draft = isset($_POST['save_draft']) ? 1 : 0;

    // Walidacja podstawowych danych
    if (empty($quiz_nazwa)) {
        $_SESSION['error'] = 'Nazwa quizu jest wymagana!';
        header('Location: ../quizzCreator.php');
        exit();
    }

    if (empty($questions) || count($questions) == 0) {
        $_SESSION['error'] = 'Quiz musi zawierać przynajmniej jedno pytanie!';
        header('Location: ../quizzCreator.php');
        exit();
    }

    // Walidacja pytań
    foreach ($questions as $index => $question) {
        if (empty(trim($question['text'] ?? ''))) {
            $_SESSION['error'] = 'Wszystkie pytania muszą mieć treść!';
            header('Location: ../quizzCreator.php');
            exit();
        }

        $options = $question['options'] ?? [];
        $valid_options = array_filter($options, function($option) {
            return !empty(trim($option));
        });

        if (count($valid_options) < 2) {
            $_SESSION['error'] = 'Każde pytanie musi mieć przynajmniej 2 opcje odpowiedzi!';
            header('Location: ../quizzCreator.php');
            exit();
        }

        // Sprawdzenie czy wybrano przynajmniej jedną poprawną odpowiedź
        $correct_answers = $question['correct'] ?? [];
        if (empty($correct_answers)) {
            $_SESSION['error'] = 'Każde pytanie musi mieć przynajmniej jedną poprawną odpowiedź!';
            header('Location: ../quizzCreator.php');
            exit();
        }
    }

    // Rozpoczęcie transakcji
    mysqli_autocommit($db, FALSE);

    try {
        // 1. Zapisanie quizu do tabeli 'quiz'
        $user_id = $_SESSION['user_id'] ?? 1; // Zakładam że masz user_id w sesji
        $current_date = date('Y-m-d H:i:s');
        $poziom_trudnosci = 1; // Domyślny poziom trudności - można rozszerzyć w przyszłości

        $quiz_sql = "INSERT INTO quiz (nazwa, opis, poziom_trudnosci, data_utworzenia, user_id, draft) VALUES (?, ?, ?, ?, ?, ?)";
        $quiz_stmt = mysqli_prepare($db, $quiz_sql);

        if (!$quiz_stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($db));
        }

        mysqli_stmt_bind_param($quiz_stmt, "ssissi", $quiz_nazwa, $quiz_opis, $poziom_trudnosci, $current_date, $user_id, $is_draft);

        if (!mysqli_stmt_execute($quiz_stmt)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($quiz_stmt));
        }

        $quiz_id = mysqli_insert_id($db);
        mysqli_stmt_close($quiz_stmt);

        if (!$quiz_id) {
            throw new Exception("Nie udało się utworzyć quizu");
        }

        // 2. Zapisanie pytań do tabeli 'pytanie'
        foreach ($questions as $question_order => $question_data) {
            $question_text = mysqli_real_escape_string($db, trim($question_data['text']));
            $wyjasnienie = null; // Można rozszerzyć w przyszłości o wyjaśnienia

            $pytanie_sql = "INSERT INTO pytanie (Treść, Wyjaśnienie, quiz_id) VALUES (?, ?, ?)";
            $pytanie_stmt = mysqli_prepare($db, $pytanie_sql);

            if (!$pytanie_stmt) {
                throw new Exception("Prepare failed for pytanie: " . mysqli_error($db));
            }

            mysqli_stmt_bind_param($pytanie_stmt, "ssi", $question_text, $wyjasnienie, $quiz_id);

            if (!mysqli_stmt_execute($pytanie_stmt)) {
                throw new Exception("Execute failed for pytanie: " . mysqli_stmt_error($pytanie_stmt));
            }

            $pytanie_id = mysqli_insert_id($db);
            mysqli_stmt_close($pytanie_stmt);

            if (!$pytanie_id) {
                throw new Exception("Nie udało się utworzyć pytania");
            }

            // 3. Zapisanie opcji odpowiedzi do tabeli 'odpowiedzi'
            $options = $question_data['options'] ?? [];
            $correct_answers = $question_data['correct'] ?? [];

            foreach ($options as $option_index => $option_text) {
                $option_text = trim($option_text);

                // Pomijamy puste opcje
                if (empty($option_text)) {
                    continue;
                }

                $option_text = mysqli_real_escape_string($db, $option_text);

                // Sprawdzenie czy ta opcja jest poprawna
                $czy_poprawna = in_array((string)$option_index, $correct_answers) ? 1 : 0;

                $odpowiedzi_sql = "INSERT INTO odpowiedzi (treść_odpowiedzi, czy_poprawna, pytanie_id) VALUES (?, ?, ?)";
                $odpowiedzi_stmt = mysqli_prepare($db, $odpowiedzi_sql);

                if (!$odpowiedzi_stmt) {
                    throw new Exception("Prepare failed for odpowiedzi: " . mysqli_error($db));
                }

                mysqli_stmt_bind_param($odpowiedzi_stmt, "sii", $option_text, $czy_poprawna, $pytanie_id);

                if (!mysqli_stmt_execute($odpowiedzi_stmt)) {
                    throw new Exception("Execute failed for odpowiedzi: " . mysqli_stmt_error($odpowiedzi_stmt));
                }

                mysqli_stmt_close($odpowiedzi_stmt);
            }
        }

        // Zatwierdzenie transakcji
        mysqli_commit($db);

        // Ustawienie komunikatu sukcesu
        if ($is_draft) {
            $_SESSION['success'] = 'Quiz został zapisany jako szkic!';
        } else {
            $_SESSION['success'] = 'Quiz został pomyślnie opublikowany!';
        }

        // Przekierowanie do strony z quizami lub profilu
        header('Location: ../quizzCreator.php');

    } catch (Exception $e) {
        // Wycofanie transakcji w przypadku błędu
        mysqli_rollback($db);

        $_SESSION['error'] = 'Błąd podczas zapisywania quizu: ' . $e->getMessage();
        header('Location: ../quizzCreator.php');
    }

} else {
    // Jeśli nie jest to POST request
    header('Location: ../quizzCreator.php');
}

// Zamknięcie połączenia
mysqli_close($db);
exit();
?>
