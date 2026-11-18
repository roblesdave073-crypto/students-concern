<?php require_once __DIR__ . '/includes/functions.php';
if (current_user())go(current_user()['role'] . '/dashboard.php');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Concerns | Campus Care</title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=url('assets/css/app.css')?>" rel="stylesheet">
</head>
<body class="landing-body">
<header class="landing-nav">
<a class="landing-brand" href="<?=url()?>">
<span class="brand-mark">
<i class="bi bi-chat-left-text">
</i>
</span>
<span>Student Concerns<small>Southern leyte State University</small>
</span>
</a>
<nav id="landing-navigation">
<a href="#home">Home</a>
<a href="<?=url('about.php')?>">About</a>
<a href="#features">Features</a>
<a href="#how">How it works</a>
<a href="#contact">Contact</a>
<a class="btn btn-outline-navy" href="<?=url('auth/login.php')?>">Login</a>
<a class="btn btn-navy" href="<?=url('auth/register.php')?>">Register</a>
</nav>
<button class="landing-menu" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="landing-navigation">
<i class="bi bi-list">
</i>
</button>
</header>
<main>
<section class="landing-hero" id="home">
<div class="hero-copy">
<span class="eyebrow-pill">Your voice, tracked until resolved</span>
<h1>Submit your concerns.<br>Track every step.</h1>
<p>A simple platform for students to file complaints, ask for help, and see exactly how their case is being handled.</p>
<div class="hero-actions">
<a class="btn btn-white" href="<?=url('auth/login.php')?>">Login</a>
<a class="btn btn-hero-outline" href="<?=url('auth/register.php')?>">Register</a>
</div>
</div>
<div class="tracking-card">
<div class="d-flex justify-content-between align-items-center">
<small>TRACKING NO.</small>
<span class="badge-status badge-inverse">In progress</span>
</div>
<h2>SCC-2026-000127</h2>
<p>Broken air conditioning, Room 204</p>
<div class="tracking-steps">
<span class="done">
</span>
<span class="done">
</span>
<span class="done">
</span>
<span>
</span>
</div>
<div class="tracking-labels">
<span>Submitted</span>
<span>Assigned</span>
<span>Working</span>
<span>Resolved</span>
</div>
</div>
</section>
<section class="landing-about" id="about">
<div>
<small class="section-kicker">ABOUT THE SYSTEM</small>
<h2>A clear path from concern to solution</h2>
<p>The Student Complaint and Concerns Management System gives students an organized way to report problems, and gives administrators and staff the tools to review, assign, respond to, and resolve them, with a complete record of every action.</p>
</div>
<div class="role-grid">
<article>
<i class="bi bi-mortarboard">
</i>
<strong>Students</strong>
<span>Submit and track concerns</span>
</article>
<article>
<i class="bi bi-person-check">
</i>
<strong>Faculty and staff</strong>
<span>Process and resolve cases</span>
</article>
<article class="wide">
<i class="bi bi-shield-check">
</i>
<strong>Administrators</strong>
<span>Review, assign, and report</span>
</article>
</div>
</section>
<section class="landing-features" id="features">
<small class="section-kicker">SYSTEM FEATURES</small>
<h2>Everything a concern needs</h2>
<div class="feature-grid"><?php foreach ([['file-earmark-plus','Easy submission','Choose a category and department, describe the concern, and attach photos or documents.'],['search','Unique tracking number','Every complaint gets its own number so you can follow its status and history.'],['bell','Instant notifications','Get alerts when your complaint is reviewed, assigned, answered, or resolved.'],['shield-check','Anonymous option','Report sensitive concerns without your identity shown to unauthorized viewers.'],['people','Staff assignment','Administrators route each case to the right staff member for action.'],['bar-chart','Reports and analytics','Charts by category and department, printable reports, and CSV export.']] as [$icon,$title,$desc]):?><article>
<span class="feature-icon">
<i class="bi bi-<?=$icon?>">
</i>
</span>
<h3><?=e($title)?></h3>
<p><?=e($desc)?></p>
</article><?php endforeach;
?></div>
</section>
<section class="landing-how" id="how">
<small class="section-kicker">HOW IT WORKS</small>
<h2>Four steps to resolution</h2>
<div class="steps-grid"><?php foreach ([['Submit','Fill out the form and receive your tracking number.'],['Review','An administrator checks it and assigns staff.'],['Action','Staff work on the case and post updates.'],['Resolve','Read the result, give feedback, or reopen the case.']] as $i => $step):?><article>
<b><?=$i+1?></b>
<h3><?=e($step[0])?></h3>
<p><?=e($step[1])?></p>
</article><?php endforeach;
?></div>
</section>
<section class="landing-contact" id="contact">
<div>
<h2>Contact us</h2>
<p>Need urgent help? Reach the office directly.</p>
</div>
<div>
<span>
<i class="bi bi-geo-alt">
</i> [Office, building]</span>
<span>
<i class="bi bi-envelope">
</i> [Email]</span>
<span>
<i class="bi bi-telephone">
</i> [Phone]</span>
</div>
</section>
</main>
<footer class="landing-footer">
<span>Student Complaint and Concerns Management System</span>
<span>Southern leyte State University</span>
</footer>
<script>
const landingMenu = document.querySelector('.landing-menu');
const landingNavigation = document.querySelector('#landing-navigation');
const landingMenuIcon = landingMenu.querySelector('i');

function setLandingMenuOpen(isOpen) {
    landingNavigation.classList.toggle('open', isOpen);
    landingMenu.setAttribute('aria-expanded', String(isOpen));
    landingMenu.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    landingMenuIcon.classList.toggle('bi-list', !isOpen);
    landingMenuIcon.classList.toggle('bi-x-lg', isOpen);
}

landingMenu.addEventListener('click', () => {
    setLandingMenuOpen(!landingNavigation.classList.contains('open'));
});

landingNavigation.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        setLandingMenuOpen(false);
    });
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('.landing-nav') && landingNavigation.classList.contains('open')) {
        setLandingMenuOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setLandingMenuOpen(false);
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth > 620) {
        setLandingMenuOpen(false);
    }
});
</script>
</body>
</html>
