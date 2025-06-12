<?php
session_start();
require_once("../config/db.php");

if (mysqli_connect_errno()) {
    exit('Nie udało się połączyć z bazą danych :( ' . mysqli_connect_error());
}

function sanitizuj_dane($dane) {
    return htmlspecialchars(trim($dane));
}

function hash_password($haslo) {
    return password_hash($haslo, PASSWORD_DEFAULT);
}

function sprawdz_sile_hasla($haslo) {
    // ... (pozostały kod funkcji sprawdz_sile_hasla - BEZ ZMIAN)
    if (strlen($haslo) < 8) {
        return 'Hasło musi mieć co najmniej 8 znaków!';
    }
    if (!preg_match('/[A-Z]/', $haslo)) {
        return 'Hasło musi zawierać co najmniej jedną dużą literę!';
    }
    if (!preg_match('/[a-z]/', $haslo)) {
        return 'Hasło musi zawierać co najmniej jedną małą literę!';
    }
    if (!preg_match('/[!@#$%^&*()\\-_=+{};:,<.>]/', $haslo)) {
        return 'Hasło musi zawierać co najmniej jeden znak specjalny!';
    }
    if (preg_match_all('/[0-9]/', $haslo) < 3) {
        return 'Hasło musi zawierać co najmniej 3 cyfry!';
    }
    return true;
}

function sprawdz_hasla($haslo, $haslo2) {
    return $haslo === $haslo2;
}

function zarejestruj($db, $nazwa_uzytkownika, $email, $haslo) {
    $nazwa_uzytkownika = sanitizuj_dane($nazwa_uzytkownika);
    $email = sanitizuj_dane($email);

    // Sprawdzenie, czy użytkownik lub email już istnieje
    $stmt_check = $db->prepare("SELECT user_id FROM uzytkownicy WHERE nazwa = ? OR email = ?");
    $stmt_check->bind_param("ss", $nazwa_uzytkownika, $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check && $result_check->num_rows > 0) {
        $_SESSION['error'] = 'Użytkownik o podanej nazwie lub adresie e-mail już istnieje.';
        return false;
    }
    $stmt_check->close();

    $haslo_hash = hash_password($haslo);
    $domyslna_rola = 'user'; // Ustawienie domyślnej roli dla nowego użytkownika

    // Zmienione zapytanie, aby dodać również rolę
    $stmt_insert = $db->prepare("INSERT INTO uzytkownicy (nazwa, email, hasło, rola) VALUES (?, ?, ?, ?)");
    if ($stmt_insert) {
        $stmt_insert->bind_param("ssss", $nazwa_uzytkownika, $email, $haslo_hash, $domyslna_rola);
        if ($stmt_insert->execute()) {
            $_SESSION["username"] = $nazwa_uzytkownika;
            $_SESSION['zalogowany'] = true;
            $_SESSION['user_id'] = mysqli_insert_id($db); // Pobierz ID nowo wstawionego użytkownika
            $_SESSION['user_role'] = $domyslna_rola; // Zapisz domyślną rolę w sesji

            header('Location: ../profile.php');
            exit();
        } else {
            $_SESSION['error'] = 'Błąd rejestracji użytkownika: ' . $stmt_insert->error;
            return false;
        }
        $stmt_insert->close();
    } else {
        $_SESSION['error'] = 'Błąd przygotowania zapytania rejestracji: ' . $db->error;
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa_uzytkownika = isset($_POST['username']) ? $_POST['username'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $haslo = isset($_POST['password']) ? $_POST['password'] : '';
    $haslo2 = isset($_POST['passwordconfirm']) ? $_POST['passwordconfirm'] : ''; // Dodano passwordconfirm

    $walidacja_hasla = sprawdz_sile_hasla($haslo);

    if ($walidacja_hasla !== true) {
        $_SESSION['error'] = $walidacja_hasla;
        header('Location: ../index.php'); // Powrót na stronę rejestracji
        exit();
    }

    if (!sprawdz_hasla($haslo, $haslo2)) {
        $_SESSION['error'] = 'Hasła nie pasują do siebie.';
        header('Location: ../index.php'); // Powrót na stronę rejestracji
        exit();
    }

    if (zarejestruj($db, $nazwa_uzytkownika, $email, $haslo)) {
        // Przekierowanie odbywa się już w funkcji zarejestruj()
    } else {
        // Jeśli zarejestruj zwróci false, błąd jest już w sesji, przekieruj
        header('Location: ../index.php');
        exit();
    }
} else {
    // Jeśli ktoś próbuje uzyskać dostęp bezpośrednio
    header('Location: ../index.php');
    exit();
}
?>