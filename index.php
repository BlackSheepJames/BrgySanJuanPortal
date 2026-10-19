<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#155c3b">
    <meta name="description" content="Access Barangay San Juan community services, resident support, and emergency reporting.">
    <title>Barangay San Juan · Community Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="service-landing portal-home" id="top">
    <div class="portal-notice">
        <div class="portal-container portal-notice-inner">
            <span><b>BARANGAY SAN JUAN</b><span class="notice-divider">/</span> Community service portal</span>
            <a href="#emergency">Need urgent assistance? <span aria-hidden="true">→</span></a>
        </div>
    </div>

    <header class="landing-header portal-header">
        <a class="landing-brand portal-brand" href="index.php" aria-label="Barangay San Juan home">
            <img src="img/Barangay%20San%20Juan%20Municipal%20Seal.png" alt="Barangay San Juan municipal seal" width="54" height="54">
            <span>
                <strong>Barangay San Juan</strong>
                <small>Serving our community together</small>
            </span>
        </a>

        <nav class="portal-nav" aria-label="Main navigation">
            <a class="active" href="index.php">Home</a>
            <a href="#services">Services</a>
            <a href="#about">About</a>
            <a href="#emergency">Emergency</a>
        </nav>

        <a class="header-login portal-login" href="login.php">Sign in <span aria-hidden="true">→</span></a>
    </header>

    <main>
        <section class="portal-hero">
            <div class="portal-container portal-hero-grid">
                <div class="portal-hero-copy">
                    <p class="portal-eyebrow"><span></span> YOUR BARANGAY, HERE FOR YOU</p>
                    <h1>Public service,<br><em>closer to home.</em></h1>
                    <p class="portal-hero-intro">
                        Welcome to the Barangay San Juan community portal. Find resident services,
                        connect with your barangay, and access support in one place.
                    </p>
                    <div class="portal-hero-actions">
                        <a class="portal-button portal-button-primary" href="#services">Explore services <span aria-hidden="true">→</span></a>
                        <a class="portal-button portal-button-quiet" href="register.php">Create a resident account</a>
                    </div>
                    <div class="portal-welcome-note">
                        <span class="portal-welcome-icon" aria-hidden="true">SJ</span>
                        <span><strong>Community first</strong><small>Local service for every resident</small></span>
                    </div>
                </div>
                <div class="portal-hero-art" aria-hidden="true">
                    <div class="portal-seal-halo"></div>
                    <div class="portal-seal-frame">
                        <img src="img/Barangay%20San%20Juan%20Municipal%20Seal.png" alt="">
                    </div>
                    <span class="portal-art-leaf leaf-one"></span>
                    <span class="portal-art-leaf leaf-two"></span>
                    <span class="portal-art-dot dot-one"></span>
                    <span class="portal-art-dot dot-two"></span>
                    <div class="portal-art-caption"><span></span> BARANGAY SAN JUAN</div>
                </div>
            </div>
        </section>

        <section class="portal-service-section" id="services">
            <div class="portal-container">
                <div class="portal-section-heading">
                    <div>
                        <p class="portal-eyebrow">HOW CAN WE HELP?</p>
                        <h2>Services for our community</h2>
                        <p>Choose a service to get started. Sign in to access your requests and records.</p>
                    </div>
                    <a class="portal-section-link" href="login.php">Go to your portal <span aria-hidden="true">↗</span></a>
                </div>

                <div class="portal-service-grid">
                    <a class="portal-service-card" href="login.php">
                        <span class="portal-service-icon icon-docs" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M7 3.75h7l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/><path d="M14 3.75v4h4M8.5 12h7M8.5 15.5h7"/></svg>
                        </span>
                        <span class="portal-card-kicker">DOCUMENTS & RECORDS</span>
                        <strong>Barangay certificates</strong>
                        <p>Request documents and keep track of your applications.</p>
                        <span class="portal-card-action">Access service <b aria-hidden="true">→</b></span>
                    </a>

                    <a class="portal-service-card" href="register.php">
                        <span class="portal-service-icon icon-resident" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.25"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M17 8v6M14 11h6"/></svg>
                        </span>
                        <span class="portal-card-kicker">RESIDENT SUPPORT</span>
                        <strong>Resident services</strong>
                        <p>Create an account to access services and manage your profile.</p>
                        <span class="portal-card-action">Register as a resident <b aria-hidden="true">→</b></span>
                    </a>

                    <a class="portal-service-card" href="login.php">
                        <span class="portal-service-icon icon-community" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 19.25V8l8-4 8 4v11.25M2.75 19.25h18.5M9 19v-5h6v5M8 9h.01M12 9h.01M16 9h.01"/></svg>
                        </span>
                        <span class="portal-card-kicker">COMMUNITY PROGRAMS</span>
                        <strong>Community services</strong>
                        <p>Connect with barangay programs, announcements, and support.</p>
                        <span class="portal-card-action">Explore online services <b aria-hidden="true">→</b></span>
                    </a>

                    <a class="portal-service-card" href="login.php">
                        <span class="portal-service-icon icon-staff" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8M8 12.5h3M15 16l1.5 1.5L20 14"/></svg>
                        </span>
                        <span class="portal-card-kicker">STAFF & OFFICIALS</span>
                        <strong>Staff portal</strong>
                        <p>Barangay staff can sign in to manage services and records.</p>
                        <span class="portal-card-action">Staff sign in <b aria-hidden="true">→</b></span>
                    </a>
                </div>
            </div>
        </section>

        <section class="portal-about-section" id="about">
            <div class="portal-container portal-about-grid">
                <div class="portal-about-seal">
                    <img src="img/Barangay%20San%20Juan%20Municipal%20Seal.png" alt="Official seal of Barangay San Juan" loading="lazy">
                </div>
                <div class="portal-about-copy">
                    <p class="portal-eyebrow">A BARANGAY THAT CARES</p>
                    <h2>Working together for a stronger community.</h2>
                    <p>
                        Barangay San Juan is here to help residents access local services and stay
                        connected with their community. This portal makes it easier to get started
                        online, while our barangay team remains ready to assist.
                    </p>
                    <a class="portal-text-link" href="login.php">Visit the community portal <span aria-hidden="true">→</span></a>
                </div>
                <div class="portal-about-values">
                    <div><span>01</span><strong>Accessible services</strong><p>Find the right place to begin your request.</p></div>
                    <div><span>02</span><strong>Resident-centered support</strong><p>Use your account to stay connected to your services.</p></div>
                    <div><span>03</span><strong>Local community</strong><p>Barangay services, built around the people we serve.</p></div>
                </div>
            </div>
        </section>

        <section class="portal-emergency-section" id="emergency">
            <div class="portal-container portal-emergency-inner">
                <div class="portal-emergency-mark" aria-hidden="true">!</div>
                <div>
                    <p class="portal-eyebrow">URGENT ASSISTANCE</p>
                    <h2>Need to report an emergency?</h2>
                    <p>Submit an emergency report with your location and contact details.</p>
                    <small>If someone is in immediate danger, contact the appropriate local emergency hotline first.</small>
                </div>
                <a class="portal-button portal-button-emergency" href="emergency.php">Report an emergency <span aria-hidden="true">→</span></a>
            </div>
        </section>
    </main>

    <footer class="portal-footer">
        <div class="portal-container portal-footer-main">
            <a class="portal-footer-brand" href="index.php">
                <img src="img/Barangay%20San%20Juan%20Municipal%20Seal.png" alt="" width="42" height="42" loading="lazy">
                <span><strong>Barangay San Juan</strong><small>Community service portal</small></span>
            </a>
            <div class="portal-footer-links">
                <a href="index.php">Home</a>
                <a href="#services">Services</a>
                <a href="register.php">Resident registration</a>
                <a href="login.php">Sign in</a>
                <a href="emergency.php">Emergency report</a>
            </div>
        </div>
        <div class="portal-container portal-footer-bottom">
            <span>Barangay San Juan · Local service, community first</span>
            <a href="#top" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">Back to top ↑</a>
        </div>
    </footer>
</body>
</html>
