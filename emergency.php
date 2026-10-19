<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
start_session();

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff7f7">
    <title>Report an Emergency · San Juan Barangay</title>

    <link rel="preconnect" href="[fonts.googleapis.com](https://fonts.googleapis.com)">
    <link rel="preconnect" href="[fonts.gstatic.com](https://fonts.gstatic.com)" crossorigin>
    <link
        href="[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap)"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="emergency-page">
    <header class="landing-header">
        <a class="landing-brand" href="index.php">
            <span class="brand-mark">SJ</span>
            <span>
                <strong>Barangay San Juan</strong>
                <small>Emergency Reporting</small>
            </span>
        </a>

        <a class="header-login" href="index.php">← Service options</a>
    </header>

    <main class="emergency-layout">
        <section class="emergency-intro">
            <p class="eyebrow danger-text">URGENT ASSISTANCE</p>
            <h1>Report an emergency</h1>
            <p>
                Give clear and accurate information so barangay responders can
                assess the incident as quickly as possible.
            </p>

            <div class="urgent-notice" role="note">
                <strong>Someone is in immediate danger?</strong>
                <p>
                    Contact the appropriate local emergency hotline first.
                    Do not rely only on this online form.
                </p>
            </div>

            <ol class="process-list">
                <li>
                    <span>1</span>
                    <div>
                        <strong>Describe the emergency</strong>
                        <p>Choose the incident type and explain what happened.</p>
                    </div>
                </li>
                <li>
                    <span>2</span>
                    <div>
                        <strong>Provide the location</strong>
                        <p>Add a street, landmark, purok, or nearby building.</p>
                    </div>
                </li>
                <li>
                    <span>3</span>
                    <div>
                        <strong>Keep your phone available</strong>
                        <p>A responder may contact you for more information.</p>
                    </div>
                </li>
            </ol>
        </section>

        <section class="emergency-form-card">
            <p class="eyebrow">INCIDENT DETAILS</p>
            <h2>What is happening?</h2>
            <p class="muted">Fields marked with * are required.</p>

            <form
                class="stack-form"
                action="api/emergencies.php"
                method="post"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <label for="incident_type">
                    Emergency type *
                    <select id="incident_type" name="incident_type" required>
                        <option value="">Select an emergency</option>
                        <option value="medical">Medical emergency</option>
                        <option value="fire">Fire or smoke</option>
                        <option value="crime">Crime or public safety</option>
                        <option value="accident">Road accident</option>
                        <option value="disaster">Flood or natural disaster</option>
                        <option value="other">Other emergency</option>
                    </select>
                </label>

                <label for="location">
                    Incident location *
                    <input
                        id="location"
                        name="location"
                        required
                        maxlength="255"
                        autocomplete="street-address"
                        placeholder="Street, purok, landmark, or nearby building"
                    >
                </label>

                <label for="description">
                    What happened? *
                    <textarea
                        id="description"
                        name="description"
                        required
                        minlength="10"
                        maxlength="1500"
                        rows="5"
                        placeholder="Describe what you can see and whether anyone is injured"
                    ></textarea>
                </label>

                <div class="form-grid">
                    <label for="reporter_name">
                        Your name *
                        <input
                            id="reporter_name"
                            name="reporter_name"
                            required
                            maxlength="120"
                            autocomplete="name"
                            placeholder="Full name"
                        >
                    </label>

                    <label for="contact_number">
                        Contact number *
                        <input
                            id="contact_number"
                            name="contact_number"
                            type="tel"
                            required
                            maxlength="20"
                            autocomplete="tel"
                            inputmode="tel"
                            placeholder="09XX XXX XXXX"
                        >
                    </label>
                </div>

                <label for="evidence">
                    Photo or evidence
                    <input
                        id="evidence"
                        name="evidence"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <small>Optional. Upload a JPG, PNG, or WebP image.</small>
                </label>

                <label class="consent-field">
                    <input type="checkbox" name="confirmation" value="1" required>
                    <span>
                        I confirm that the information provided is accurate to
                        the best of my knowledge.
                    </span>
                </label>

                <button class="primary-btn emergency-submit" type="submit">
                    Submit emergency report <span>→</span>
                </button>

                <p class="form-security-note">
                    False or intentionally misleading reports may delay
                    assistance for people who need it.
                </p>
            </form>
        </section>
    </main>
</body>
</html>
