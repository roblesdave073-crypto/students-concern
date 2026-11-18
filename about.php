<?php require_once __DIR__ . '/includes/functions.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>About | Student Concerns</title>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?=url('assets/css/app.css')?>" rel="stylesheet">
<link href="<?=url('assets/css/about.css?v=4')?>" rel="stylesheet">
</head>
<body class="landing-body">
<header class="landing-nav">
<a class="landing-brand" href="<?=url()?>">
<span class="brand-mark"><i class="bi bi-chat-left-text"></i></span>
<span>Student Concerns<small>Southern Leyte State University</small></span>
</a>
<nav id="landing-navigation"><a href="<?=url()?>">Home</a><a href="#profile">My profile</a><a href="#system">About the system</a><a class="btn btn-navy" href="<?=url('auth/login.php')?>">Login</a></nav>
<button class="landing-menu" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="landing-navigation"><i class="bi bi-list"></i></button>
</header>
<main class="about-page">
<section class="about-profile" id="profile">
<div class="about-portrait-wrap"><img class="about-portrait" src="<?=url('assets/images/robles-profile.jfif')?>" alt="Profile portrait"></div>
<div class="about-profile-copy">
<small class="section-kicker">ABOUT ME</small>
<h1>Dave B. Robles</h1>
<p class="about-subtitle">Information Technology Student | Aspiring Software Developer</p>
<p class="about-school">Southern Leyte State University</p>
<p>I am an Information Technology student with an interest in web development, database management, software design, and information systems. I enjoy building practical technology solutions, exploring new programming concepts, and developing systems that improve everyday processes.</p>
<div class="about-contact-grid" aria-label="Personal information">
<div class="about-contact-item"><i class="bi bi-envelope"></i><span><small>Email</small><a href="mailto:roblesdave173@gmail.com">roblesdave173@gmail.com</a></span></div>
<div class="about-contact-item"><i class="bi bi-telephone"></i><span><small>Contact number</small><a href="tel:09936158636">09936158636</a></span></div>
<div class="about-contact-item"><i class="bi bi-person"></i><span><small>Age</small><strong>20 years old</strong></span></div>
<div class="about-contact-item"><i class="bi bi-geo-alt"></i><span><small>Address</small><strong>Maanyag, Tomas Oppus, Southern Leyte</strong></span></div>
</div>
</div>
</section>
<section class="about-details">
<article class="about-panel">
<small class="section-kicker">MY SKILLS</small>
<h2>Skills and interests</h2>
<div class="about-skills">
<span>HTML</span><span>CSS</span><span>JavaScript</span><span>PHP</span><span>Bootstrap</span>
<span>MySQL</span><span>SQL</span><span>Database Design</span><span>CRUD Operations</span>
<span>Java</span><span>UML</span><span>MVC Architecture</span><span>Design Patterns</span>
<span>Cisco Packet Tracer</span><span>Network Configuration</span><span>Arduino</span><span>Tinkercad</span><span>Basic Electronics</span>
</div>
</article>
<article class="about-panel" id="system">
<small class="section-kicker">ABOUT THE SYSTEM</small>
<h2>Student Complaint and Concerns Management System</h2>
<p>This system gives students an organized way to submit and track concerns. Administrators can review and assign cases, while staff can post updates and work toward resolution. Students can follow each step and receive notifications about their concerns.</p>
<a class="about-text-link" href="<?=url()?>#features">Explore system features <i class="bi bi-arrow-right"></i></a>
</article>
</section>
<section class="about-panel about-projects">
<small class="section-kicker">SELECTED WORK</small>
<h2>IT projects and experiences</h2>
<div class="about-project-list">
<article><span class="project-number">01 / WEB SYSTEM</span><h3>Student Concerns Management</h3><p>A web platform for students to submit and track concerns while administrators manage and assign cases.</p></article>
<article><span class="project-number">02 / PHP + MYSQL</span><h3>Barangay Service Requests</h3><p>A system for managing community concerns and service requests with CRUD functionality.</p></article>
<article><span class="project-number">03 / DATABASE</span><h3>Activity and Product Management</h3><p>Projects for organizing activities, products, sales records, and inventory information.</p></article>
<article><span class="project-number">04 / HARDWARE</span><h3>Arduino and Electronics</h3><p>Built basic LED circuits and Arduino projects, including enclosure designs in Tinkercad.</p></article>
<article><span class="project-number">05 / NETWORKING</span><h3>Network Topology Design</h3><p>Designed Cisco Packet Tracer networks with routers, switches, PCs, servers, and IP addressing.</p></article>
<article><span class="project-number">06 / SOFTWARE DESIGN</span><h3>Programming and Design Patterns</h3><p>Applied OOP, MVC, Command and Observer patterns, and UML diagrams in Java projects.</p></article>
</div>
</section>
<section class="about-panel about-goal">
<small class="section-kicker">MY GOAL</small>
<h2>Building useful, reliable systems</h2>
<p>My goal is to keep improving my technical skills, gain practical experience in software development, and create useful, reliable, and user-friendly systems that solve real-world problems.</p>
</section>
</main>
<footer class="landing-footer"><span>Student Complaint and Concerns Management System</span><span>Southern Leyte State University</span></footer>
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
    link.addEventListener('click', () => setLandingMenuOpen(false));
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('.landing-nav') && landingNavigation.classList.contains('open')) {
        setLandingMenuOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setLandingMenuOpen(false);
});
</script>
</body>
</html>
