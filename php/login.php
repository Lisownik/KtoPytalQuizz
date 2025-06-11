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

    $zapytanie = "SELECT user_id, hasło FROM uzytkownicy WHERE nazwa = '$nazwa_uzytkownika'";
    $wynik = mysqli_query($db, $zapytanie);

    if ($wynik && mysqli_num_rows($wynik) > 0) {
        $uzytkownik = mysqli_fetch_assoc($wynik);
        $haslo_hash = $uzytkownik['hasło'];

        if (password_verify($haslo, $haslo_hash)) {
            $_SESSION['zalogowany'] = true;
            $_SESSION['username'] = $nazwa_uzytkownika;

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
}
// Remove the closing brace that was incorrectly placed here
?>