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
            <li><a href="index.php">Home</a></li>
            <li><a href="quizzCreator.php">Create Quizz</a></li>
            <li><a href="explore.php">Explore</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
        <div class="mobile-auth">
            <?php if ($zalogowany): ?>
                <form method="post" action="php/logout.php">
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="index.php" class="mobile-login-btn">Sign In</a>
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
            <li><a href="quizzCreator.php" style="background: rgba(255, 255, 255, 0.1);">Create Quizz</a></li>
            <li><a href="explore.php">Explore</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>
    <div class="header-auth">
        <?php if ($zalogowany): ?>
            <form method="post" action="php/logout.php" class="logout-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        <?php else: ?>
            <a href="index.php" class="signin-link">Sign In</a>
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
        <h2 class="create-quiz-title">Create New Quiz</h2>

        <section class="form-group">
            <label for="quiz-title">Quiz Title</label>
            <input type="text" id="quiz-title" name="quiz_title" class="form-control" placeholder="Enter quiz title" required>
        </section>

        <section class="form-group">
            <label for="quiz-description">Description</label>
            <textarea id="quiz-description" name="quiz_description" class="form-control" placeholder="Enter quiz description"></textarea>
        </section>

        <hr>

        <div id="questions-container">
            <section class="question-container" data-question-number="1">
                <div class="question-header">
                    <h3 class="question-title">Question 1</h3>
                    <button type="button" class="delete-btn">×</button>
                </div>

                <section class="form-group">
                    <input type="text" name="questions[0][text]" class="form-control question-input" placeholder="Enter your question" required>
                </section>

                <div class="correct-answer-header">
                    <h4>Select the correct answer(s)</h4>
                    <p>Choose which of the options below are correct answers for this question. You can select multiple options.</p>
                </div>

                <section class="options-container">
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="0">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Option 1" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="1">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Option 2" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="2">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Option 3">
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[0][correct][]" value="3">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[0][options][]" class="form-control option-input" placeholder="Option 4">
                        </div>
                    </div>
                </section>
            </section>
        </div>

        <button type="button" class="add-question-btn" id="add-question-btn">+ Add Question</button>

        <section class="button-container">
            <button type="submit" name="save_draft" class="save-btn">Save Draft</button>
            <button type="submit" name="publish_quiz" class="publish-btn">Publish Quiz</button>
        </section>
    </form>
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
            <h4>Support</h4>
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
        <p>© <?php echo date('Y'); ?> Kto Pytał. All rights reserved.</p>
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
                    <h3 class="question-title">Question ${questionCounter}</h3>
                    <button type="button" class="delete-btn">×</button>
                </div>

                <section class="form-group">
                    <input type="text" name="questions[${questionCounter - 1}][text]" class="form-control question-input" placeholder="Enter your question" required>
                </section>

                <div class="correct-answer-header">
                    <h4>Select the correct answer(s)</h4>
                    <p>Choose which of the options below are correct answers for this question. You can select multiple options.</p>
                </div>

                <section class="options-container">
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="0">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Option 1" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="1">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Option 2" required>
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="2">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Option 3">
                        </div>
                    </div>
                    <div class="option-wrapper">
                        <label class="correct-checkbox">
                            <input type="checkbox" name="questions[${questionCounter - 1}][correct][]" value="3">
                            <span class="checkbox-custom"></span>
                        </label>
                        <span class="correct-label">Correct</span>
                        <div class="option-input-wrapper">
                            <input type="text" name="questions[${questionCounter - 1}][options][]" class="form-control option-input" placeholder="Option 4">
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