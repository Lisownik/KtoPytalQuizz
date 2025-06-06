<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="koty, kotki"/> 
    <meta name="destription" content="ta strona pokaże ci ciekawy i intrygujący świat kotów"/>
    <meta name="author" content="Same sigmy team"/>
    <meta name="robots" content="none"/>
    <link rel="stylesheet" href="style/universal.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Just+Another+Hand&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<body>
    <div class="sign" id="log_in">
        <h2>Sign in</h2>
        <form>
            <label for="rusername">Username</label>
            <input type="text" id="rusername" placeholder="Enter username" required>

            <label for="rmail">E-mail</label>
                <input type="email" id="rmail" placeholder="Enter password" required>

            <label for="rpassword">Password</label>
            <input type="password" id="rpassword" placeholder="Enter password" required>

            <label for="rpasswordconfirm">Repeat Password</label>
            <input type="password" id="rpasswordconfirm" placeholder="Repeat password" required>
            
            <button type="submit" class="btn btn-primary">Log in</button>
            <button type="button" class="btn btn-secondary">Sign in</button>
        </form>
    </div>
    <div class="sign" id="register">
        <h2>Log in</h2>
        <form>
            <label for="lusername">Username</label>
            <input type="text" id="lusername" placeholder="Enter username">

            <label for="lpassword">Password</label>
            <input type="password" id="lpassword" placeholder="Enter password">

            <button type="submit" class="btn btn-primary">Log in</button>
            <button type="button" class="btn btn-secondary">Sign in</button>
        </form>
    </div>
    <header>
        <div>
            <a href="index.php"><img src="../assets/logo.png" alt="logo mózgu"></a>
            <h2>Kto Pytał</h2>
        </div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="quizzCreator.html">Create Quizz</a></li>
                <li><a href="explore.html">Explore</a></li>
                <li><a href="profile.html">Profile</a></li>
            </ul>
        </nav>
        <button>Sign In</button>
    </header>
    <main>
        <section id="first">
            <div>
                <h1>Create and Share Interactive Quizzes</h1>
                <p>Make learning fun and engaging with our easy-to-use quiz platform. Perfect for educators, trainers, and quiz enthusiasts.</p>
                <div>
                    <button>Create Quiz</button>
                    <button>Take Quiz</button>
                </div>
            </div>
            <img src="assets/Szescian.png" alt="">
        </section>
        <section id="second">
            <h3>Popular Today</h3>
            <article></article>
        </section>
    </main>
    <footer>
        <div>
            <div>
                <h4>Kto Pytał</h4>
                <p>Making quiz creation and sharing easier than ever.</p>
            </div>
            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li>About Us</li>
                    <li>Features</li>
                    <li>Pricing</li>
                    <li>Blog</li>
                </ul>
            </div>
            <div>
                <h4>Support</h4>
                <ul>
                    <li>Help Center</li>
                    <li>Contact Us</li>
                    <li>Privacy Policy</li>
                    <li>Terms of Service</li>
                </ul>
            </div>
            <div>
                <h4>Follow Us</h4>
                <img src="" alt="logo facebook">
                <img src="" alt="logo x">
                <img src="" alt="logo instagram">
                <img src="" alt="logo linked in">
            </div>
        </div>
        <p id="copyright">&copy; 2025  Kto Pytał. All rights reserved.</p>
    </footer>
</body>
</html>

