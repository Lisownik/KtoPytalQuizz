<?php
session_start();
require_once("../config/db.php");

if (mysqli_connect_errno()) {
    $_SESSION['error'] = 'Nie udało się połączyć z bazą danych :( ' . mysqli_connect_error();
    header('Location: ../index.php'); // Redirect back to index.php
    exit();
}

function sanitizuj_dane($dane)
{
    return htmlspecialchars(trim($dane));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $nazwa_uzytkownika = sanitizuj_dane($_POST['username']);
    $haslo = $_POST['password'];

    // Zmienione zapytanie, aby pobrać również rolę
    $zapytanie = "SELECT user_id, hasło, rola FROM uzytkownicy WHERE nazwa = ?";
    $stmt = mysqli_prepare($db, $zapytanie);
    mysqli_stmt_bind_param($stmt, "s", $nazwa_uzytkownika);
    mysqli_stmt_execute($stmt);
    $wynik = mysqli_stmt_get_result($stmt);


    if ($wynik && mysqli_num_rows($wynik) > 0) {
        $uzytkownik = mysqli_fetch_assoc($wynik);
        $haslo_hash = $uzytkownik['hasło'];

        if (password_verify($haslo, $haslo_hash)) {
            $_SESSION['zalogowany'] = true;
            $_SESSION['username'] = $nazwa_uzytkownika;
            $_SESSION['user_id'] = $uzytkownik['user_id']; // Upewnij się, że user_id jest również zapisywane
            $_SESSION['user_role'] = $uzytkownik['rola']; // Zapisz rolę użytkownika w sesji

            header('Location: ../profile.php');
            exit;
        } else {
            $_SESSION['error'] = 'Nieprawidłowa nazwa użytkownika lub hasło.'; // Set error message
            header('Location: ../index.php'); // Redirect back to index.php
            exit();
        }
    } else {
        $_SESSION['error'] = 'Nieprawidłowa nazwa użytkownika lub hasło.'; // Set error message
        header('Location: ../index.php'); // Redirect back to index.php
        exit();
    }
} else {
    // Jeśli ktoś próbuje uzyskać dostęp bezpośrednio lub bez wymaganych danych POST
    header('Location: ../index.php');
    exit();
}
?>