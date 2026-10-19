<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
start_session();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8fa">
    <title>Resident registration · San Juan Barangay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
    <script defer src="assets/auth.js"></script>
</head>
<body>
    <main class="register-screen">
        <section class="register-card">
            <header class="register-top">
                <div class="brand-mark">SJ</div>
                <div>
                    <p class="eyebrow">BARANGAY SAN JUAN</p>
                    <h1>Resident registration</h1>
                </div>
                <a class="secondary-btn small" href="index.php">← Sign in</a>
            </header>

            <p class="muted register-intro">Create a resident account. New registrations receive the Barangay Residents role. Staff roles can only be assigned by the System Admin.</p>

            <form id="registerForm" class="register-form" novalidate>
                <h2>Personal information</h2>
                <div class="register-grid">
                    <label>Last name <i>*</i><input name="last_name" required maxlength="90"></label>
                    <label>First name <i>*</i><input name="first_name" required maxlength="90"></label>
                    <label>Middle name<input name="middle_name" maxlength="90"></label>
                    <label>Suffix<input name="suffix" maxlength="20" placeholder="Jr., III"></label>
                    <label>Mobile number <i>*</i>
                        <input name="phone" type="text" inputmode="numeric" pattern="[0-9]{7,15}" minlength="7" maxlength="15" required data-digits-only placeholder="Digits only">
                        <small>Enter 7–15 digits, without spaces or symbols.</small>
                    </label>
                    <label>Email address <i>*</i>
                        <input name="email" type="email" autocomplete="email" required maxlength="160" placeholder="name@gmail.com" data-gmail-only>
                        <small>Use a personal @gmail.com address.</small>
                    </label>
                    <label class="wide">Current address <i>*</i><textarea name="address" rows="2" required></textarea></label>
                    <label>Birth date<input name="birth_date" type="date"></label>
                    <label>Gender<select name="gender"><option>Prefer not to say</option><option>Female</option><option>Male</option><option>Other</option></select></label>
                    <label>Civil status<select name="civil_status"><option value="">Select…</option><option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option></select></label>
                    <label>Occupation<input name="occupation"></label>
                    <label>Purok<input name="purok"></label>
                    <label>Household code<input name="household_code"></label>
                    <label>Household role<select name="household_role"><option value="">Select…</option><option>Head</option><option>Spouse</option><option>Child</option><option>Parent</option><option>Other</option></select></label>
                    <label>Registered voter<select name="voter_status"><option value="0">No / not sure</option><option value="1">Yes</option></select></label>

                    <fieldset class="wide sector-field">
                        <legend>Sector classifications <small>(select all that apply)</small></legend>
                        <div class="register-sectors">
                            <label><input type="checkbox" name="sectors[]" value="Senior Citizen"> Senior Citizen</label>
                            <label><input type="checkbox" name="sectors[]" value="PWD"> PWD</label>
                            <label><input type="checkbox" name="sectors[]" value="Solo Parent"> Solo Parent</label>
                            <label><input type="checkbox" name="sectors[]" value="4Ps Beneficiary"> 4Ps Beneficiary</label>
                            <label><input type="checkbox" name="sectors[]" value="Youth"> Youth</label>
                            <label><input type="checkbox" name="sectors[]" value="Indigenous People"> Indigenous People</label>
                        </div>
                    </fieldset>

                    <h2 class="wide">Account security</h2>
                    <label>Password <i>*</i>
                        <input name="password" type="password" required minlength="8" autocomplete="new-password" data-password-policy>
                        <small>At least 8 characters, with an uppercase letter, a number, and a symbol.</small>
                    </label>
                    <label>Confirm password <i>*</i>
                        <input name="confirm_password" type="password" required minlength="8" autocomplete="new-password" data-confirm-password>
                    </label>
                </div>

                <p id="authError" class="form-error hidden" role="alert"></p>
                <div class="register-actions">
                    <button class="primary-btn" type="submit">Create resident account <span>→</span></button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
