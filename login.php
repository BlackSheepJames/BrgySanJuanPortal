<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8fa">
    <title>Sign in · San Juan Barangay</title>

    <link rel="preconnect" href="[fonts.googleapis.com](https://fonts.googleapis.com)">
    <link rel="preconnect" href="[fonts.gstatic.com](https://fonts.gstatic.com)" crossorigin>
    <link
        href="[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap)"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/app.css">
    <script defer src="assets/auth.js"></script>
</head>
<body>
    <main class="login-screen">
        <section class="login-visual" aria-label="Barangay San Juan">
            <div class="login-visual-inner">
                <div class="brand-mark large">SJ</div>
                <p class="eyebrow light">BARANGAY SAN JUAN</p>
                <h1>Better service,<br>closer to home.</h1>
                <p>
                    One calm, secure place for the everyday work that keeps
                    our community moving.
                </p>
                <div class="login-landscape">
                    <span></span><span></span><span></span><i>✦</i>
                </div>
                <div class="login-caption">
                    LOCAL SERVICE · COMMUNITY FIRST
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-form-wrap">
                <a class="back-link" href="index.php">← Back to service options</a>

                <div class="mobile-brand">
                    <div class="brand-mark">SJ</div>
                    <span>San Juan</span>
                </div>

                <p class="eyebrow">WELCOME BACK</p>
                <h2>Sign in to your workspace</h2>
                <p class="muted">
                    Enter your username or personal Gmail address and password.
                </p>

                <form id="loginForm" class="stack-form" novalidate>
                    <label for="identifier">
                        Username or email
                        <input
                            id="identifier"
                            name="identifier"
                            autocomplete="username"
                            required
                            placeholder="Username or name@gmail.com"
                        >
                    </label>

                    <label for="password">
                        Password
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            minlength="8"
                            data-password-policy
                            placeholder="Your password"
                        >
                        <small>
                            At least 8 characters, with an uppercase letter,
                            a number, and a symbol.
                        </small>
                    </label>

                    <p
                        id="authError"
                        class="form-error hidden"
                        role="alert"
                    ></p>

                    <button class="primary-btn" type="submit">
                        Sign in <span>→</span>
                    </button>
                </form>

                <p class="login-foot">
                    Private workspace · San Juan Barangay Hall<br>
                    <a class="text-button" href="register.php">
                        New here? Create a resident account
                    </a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>
