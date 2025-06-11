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

    if (strlen($haslo) < 8) {
        return 'Hasło musi mieć co najmniej 8 znaków!';
    }
    

    if (!preg_match('/[A-Z]/', $haslo)) {
        return 'Hasło musi zawierać co najmniej jedną dużą literę!';
    }
    

    if (!preg_match('/[a-z]/', $haslo)) {
        return 'Hasło musi zawierać co najmniej jedną małą literę!';
    }

    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $haslo)) {
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


    $zapytanie = "SELECT user_id FROM uzytkownicy WHERE nazwa = '$nazwa_uzytkownika' OR email = '$email'";
    $wynik = mysqli_query($db, $zapytanie);

    if ($wynik && mysqli_num_rows($wynik) > 0) {
        echo 'Użytkownik lub email już istnieje.';
        return false;
    }


    $haslo_hash = hash_password($haslo);


    $zapytanie = "INSERT INTO uzytkownicy (nazwa, email, hasło) VALUES ('$nazwa_uzytkownika', '$email', '$haslo_hash')";
    if (mysqli_query($db, $zapytanie)) {
        $_SESSION["username"] = $nazwa_uzytkownika;
        $_SESSION['zalogowany'] = true;
        header('Location: ../profile.php');
    } else {
        echo 'Błąd podczas rejestracji: ' . mysqli_error($db);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['email'], $_POST['password'])) {
    $nazwa_uzytkownika = $_POST['username'];
    $email = $_POST['email'];
    $haslo = $_POST['password'];


    $walidacja = sprawdz_sile_hasla($haslo);
    if ($walidacja === true) {
        zarejestruj($db, $nazwa_uzytkownika, $email, $haslo);
    } else {
        echo 'Błąd: ' . $walidacja;
    }
}

?>