<?php
session_start();
$zalogowany = isset($_SESSION['zalogowany']) ? $_SESSION['zalogowany'] : false;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="create quiz, quiz creator"/>
    <meta name="description" content="Create amazing quizzes with our easy-to-use quiz creator"/>
    <meta name="author" content="Same sigmy team"/>
    <meta name="robots" content="none"/>
    <link rel="stylesheet" href="style/universal.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/quizzCreator.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - Kto Pytał</title>
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
			<li><a href="index.php">Strona główna</a></li>
			<li><a href="quizzCreator.php">Stwórz Quiz</a></li>
			<li><a href="explore.php">Odkryj</a></li>
			<li><a href="profile.php">Profil</a></li>
			<li><a href="history.php">Historia</a></li>
		</ul>
		<div class="mobile-auth">
            <?php if ($zalogowany): ?>
				<form method="post" action="php/logout.php">
					<button type="submit">Wyloguj się</button>
				</form>
            <?php else: ?>
				<a href="quizzCreator.php" class="mobile-login-btn">Zaloguj się</a>
            <?php endif; ?>
		</div>
	</nav>
</div>

<header>
	<div>
		<a href="index.php">
			<img src="assets/logo.png" alt="logo mózgu">
			<h2>Kto Pytał</h2>
		</a>
	</div>
	<nav>
		<ul>
			<li><a href="index.php">Strona główna</a></li>
			<li><a id="selected-page" href="quizzCreator.php">Stwórz Quiz</a></li>
			<li><a href="explore.php">Odkryj</a></li>
				<li><a href="profile.php">Profil</a></li>
			<li><a href="history.php">Historia</a></li>
		</ul>
	</nav>
	<div class="header-auth">
        <?php if ($zalogowany): ?>
			<form method="post" action="php/logout.php" class="logout-form">
				<button type="submit" class="logout-btn">Wyloguj się</button>
			</form>
        <?php else: ?>
			<a href="quizzCreator.php" class="signin-link">Zaloguj się</a>
        <?php endif; ?>
	</div>
</header>
<main class="container">
    <!-- Komunikaty o błędach i sukcesie -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="background-color: #f8d7da; color: #721c24; padding: 12px; margin: 20px 0; border-radius: 4px; border: 1px solid #f5c6cb;">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; margin: 20px 0; border-radius: 4px; border: 1px solid #c3e6cb;">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <form id="quiz-form" method="post" action="php/saveQuiz.php">
        <h2 class="create-quiz-title">Stwórz nowy quiz</h2>

        <section class="form-group">
            <label for="quiz-title">Tytuł quizu</label>
            <input type="text" id="quiz-title" name="quiz_title" class="form-control" placeholder="Wpisz tytuł quizu" required>
        </section>

        <section class="form-group">
            <label for="quiz-description">Opis</label>
            <textarea id="quiz-description" name="quiz_description" class="form-control" placeholder="Wpisz opis quizu"></textarea>
        </section>

        <hr>

        <div id="questions-container">
            <section class="question-container" data-question-number="1">
                <div class="question-header">
                    <h3 class="question-title">Pytanie 1</h3>
                    <button type="button" class="delete-btn">×</button>
                </div>

                <section class="form-group">
                    <input type="text" name="questions[0][text]" class="form-control question-input" placeholder="Treść pytania" required>
                </section>

                <div class="correct-answer-header">
                    <h4>Zaznacz poprawne odpowiedzi</h4>
                    <p>Wybierz, które odpowiedzi z poniższych mają być uznawane jako prawidłowe</p>
                </div>

                <section class="options-container">
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="0">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Odpowiedź 1" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="1">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Odpowiedź 2" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="2">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Odpowiedź 3">
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="3">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Odpowiedź 4">
                        </div>
                    </div>
                </section>
            </section>
        </div>

        <button type="button" class="add-question-btn" id="add-question-btn">+ Dodaj pytanie</button>

        <section class="button-container">
            <button type="submit" name="publish_quiz" class="publish-btn">Opublikuj quiz</button>
        </section>
    </form>
</main>

<footer>
	<div class="footer-content">
		<div class="footer-section">
			<h4>Kto Pytał</h4>
			<p>Robimy, że tworzenie i dzielenie się quizami jest bardzo łatwe. Rób ciekawe quizy, które się spodobają.</p>
		</div>
		<div class="footer-section">
			<h4>Szybkie linki</h4>
			<ul>
				<li>O nas</li>
				<li>Co umiemy</li>
				<li>Ceny</li>
				<li>Nasze artykuły</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Pomoc</h4>
			<ul>
				<li>Pomoc</li>
				<li>Napisz do nas</li>
				<li>Zasady prywatności</li>
				<li>Zasady korzystania</li>
			</ul>
		</div>
		<div class="footer-section">
			<h4>Bądź z nami</h4>
			<ul>
				<li>Facebook</li>
				<li>Twitter</li>
				<li>Instagram</li>
				<li>LinkedIn</li>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<p>&copy; <?php echo date('Y'); ?> Kto Pytał. Wszystkie prawa zastrzeżone.</p>
	</div>
</footer>

<script src="js/mobile-menu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let questionCounter = 1;

        const addQuestionBtn = document.getElementById('add-question-btn');
        const questionsContainer = document.getElementById('questions-container');

        // Funkcja dodawania nowego pytania
        addQuestionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            questionCounter++;

            // Tworzenie nowego kontenera pytania
            const newQuestionHTML = `
            <section class="question-container" data-question-number="${questionCounter}">
                <div class="question-header">
                    <h3 class="question-title">Pytanie ${questionCounter}</h3>
                    <button type="button" class="delete-btn">×</button>
                </div>

                <section class="form-group">
                    <input type="text" name="questions[${questionCounter - 1}][text]" class="form-control question-input" placeholder="Treść pytania" required>
                </section>

                <div class="correct-answer-header">
                    <h4>Zaznacz poprawne odpowiedzi</h4>
                    <p>Wybierz, które odpowiedzi z poniższych mają być uznawane jako prawidłowe</p>
                </div>

                <section class="options-container">
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="0">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Odpowiedź 1" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="1">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Odpowiedź 2" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="2">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Odpowiedź 3">
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="3">
                            <span class="checkbox-custom"></span>
                        </label>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Odpowiedź 4">
                        </div>
                    </div>
                </section>
            </section>
        `;

            // Dodawanie nowego pytania do kontenera
            questionsContainer.insertAdjacentHTML('beforeend', newQuestionHTML);

            // Dodanie event listenerów dla nowego pytania
            addCheckboxListeners();
        });

        // Funkcja dodawania event listenerów do checkboxów
        function addCheckboxListeners() {
            const checkboxInputs = document.querySelectorAll('input[type="checkbox"]');
            checkboxInputs.forEach(checkbox => {
                // Usuwamy istniejące listenery żeby uniknąć duplikacji
                checkbox.removeEventListener('change', handleCheckboxChange);
                checkbox.addEventListener('change', handleCheckboxChange);
            });
        }

        function handleCheckboxChange() {
            const optionWrapper = this.closest('.option-wrapper');
            if (this.checked) {
                optionWrapper.classList.add('correct-selected');
            } else {
                optionWrapper.classList.remove('correct-selected');
            }
        }

        // Dodanie event listenerów dla pierwszego pytania
        addCheckboxListeners();

        // Obsługa usuwania pytań (delegacja zdarzeń)
        questionsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-btn')) {
                e.preventDefault();
                e.stopPropagation();

                const questionContainers = questionsContainer.querySelectorAll('.question-container');

                // Sprawdzenie czy to nie ostatnie pytanie
                if (questionContainers.length > 1) {
                    const questionToDelete = e.target.closest('.question-container');
                    questionToDelete.remove();

                    // Aktualizacja numeracji pytań
                    updateQuestionNumbers();
                } else {
                    alert('Musisz mieć przynajmniej jedno pytanie!');
                }
            }
        });

        // Funkcja aktualizacji numeracji pytań
        function updateQuestionNumbers() {
            const questionContainers = questionsContainer.querySelectorAll('.question-container');

            questionContainers.forEach((container, index) => {
                const questionNumber = index + 1;

                // Aktualizacja tytułu pytania
                const questionTitle = container.querySelector('.question-title');
                questionTitle.textContent = `Question ${questionNumber}`;

                // Aktualizacja data-attribute
                container.setAttribute('data-question-number', questionNumber);

                // Aktualizacja name attributes dla inputów
                const questionInput = container.querySelector('.question-input');
                questionInput.name = `questions[${index}][text]`;

                const optionInputs = container.querySelectorAll('.option-input');
                optionInputs.forEach(optionInput => {
                    optionInput.name = `questions[${index}][options][]`;
                });

                // Aktualizacja checkboxów
                const checkboxInputs = container.querySelectorAll('input[type="checkbox"]');
                checkboxInputs.forEach((checkbox, checkboxIndex) => {
                    checkbox.name = `questions[${index}][correct][]`;
                    checkbox.value = checkboxIndex;
                });
            });

            // Aktualizacja globalnego licznika
            questionCounter = questionContainers.length;

            // Ponowne dodanie event listenerów
            addCheckboxListeners();
        }
    });
</script>

</body>
</html>