
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Course Connect</title>

<!-- Icons & Fonts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
 /* -------------------------
     Reset & Globals
  --------------------------*/
  *{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  html,body{height:100%}
  body{
    font-family:"Inter",system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;
    color:#0f172a;
    background:#f6f8fb;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    overflow-x:hidden;
  }
  a{color:inherit;text-decoration:none}
  img{display:block;max-width:100%}

  /* -------------------------
     Page Transition overlay
  --------------------------*/
  .page-transition{
    position:fixed;inset:0;z-index:9999;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#0b1220,#0f172a);
    color:white;opacity:1;visibility:visible;transition:all .45s cubic-bezier(.2,.8,.2,1);
  }
  .page-transition.hidden{opacity:0;visibility:hidden;pointer-events:none}
  .page-transition .inner{ text-align:center }
  .spinner{
    width:52px;height:52px;border-radius:50%;
    border:4px solid rgba(255,255,255,.12);
    border-top-color:#fff; animation:spin 1s linear infinite;margin:0 auto 18px;
  }
  @keyframes spin{to{transform:rotate(360deg)}}

  /* -------------------------
     Layout: header placeholder
  --------------------------*/
  header{height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 6%;background:white;box-shadow:0 4px 18px rgba(12,18,26,.04);position:fixed;top:0;left: 0;
  width: 100%;
  z-index: 50;
  transition: top 0.3s ease;
}
  .brand{display:flex;align-items:center;gap:12px}
  .brand .logo{
    width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,#60a5fa,#a78bfa);
    display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;box-shadow:0 6px 18px rgba(99,102,241,.12)
  }
  nav{display:flex;gap:18px;align-items:center}
  nav a{font-weight:600;color:#0f172a;opacity:.85;padding:8px 12px;border-radius:8px;transition:all .18s}
  nav a:hover{background:rgba(59,130,246,.07);transform:translateY(-2px)}

  /* -------------------------
     Hero — compact two-column
  --------------------------*/
  .hero{
    display:grid;grid-template-columns:1fr 520px;gap:36px;align-items:center;
    padding:48px 6% 36px;min-height:62vh;background:linear-gradient(180deg,rgba(15,23,42,0.02),transparent);
  }
  /* Left column */
  .hero-left{max-width:780px}
  .eyebrow{
    display:inline-block;background:linear-gradient(90deg,#34d399,#60a5fa);
    color:white;padding:6px 12px;border-radius:999px;font-weight:700;margin-bottom:14px;font-size:.85rem;letter-spacing:.3px;
    box-shadow:0 8px 20px rgba(99,102,241,.06);
  }
  .hero-left h1{
    font-size:2.6rem;line-height:1.03;margin-bottom:14px;font-weight:800;
    background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;
    text-wrap:balance;
  }
  .hero-left p{color:#475569;font-size:1.05rem;margin-bottom:22px;max-width:56ch}
  .hero-ctas{display:flex;gap:14px;flex-wrap:wrap}
  .cta{
    background:#0f172a;color:white;padding:12px 18px;border-radius:12px;font-weight:700;border:none;cursor:pointer;
    display:inline-flex;align-items:center;gap:10px;box-shadow:0 14px 40px rgba(15,23,42,.12);transition:all .25s;
  }
  .cta.secondary{
    background:white;color:#0f172a;border:1px solid rgba(15,23,42,.06);box-shadow:none;font-weight:700;
  }
  .cta:hover{transform:translateY(-4px)}
  .muted-note{color:#64748b;font-size:.92rem;margin-top:12px}

  /* Right column hero image card */
  .hero-right{
    background:linear-gradient(180deg,rgba(255,255,255,0.04),rgba(255,255,255,0.02));
    border-radius:14px;padding:10px;box-shadow:0 12px 40px rgba(2,6,23,.12);
    display:flex;align-items:center;justify-content:center;overflow:hidden;
  }
  .hero-right .card{
    width:100%;height:100%;border-radius:10px;overflow:hidden;background:#fff;
    display:flex;flex-direction:column;gap:0;
  }
  .hero-right img{width:100%;height:100%;object-fit:cover;display:block}

  /* -------------------------
     Courses grid
  --------------------------*/
  .section{padding:36px 6%}
  .section h2{font-size:1.6rem;margin-bottom:18px;font-weight:800;color:#0b1220}
  .course-grid{
    display:grid;grid-template-columns:repeat(3,1fr);gap:22px;
  }
  .card-course{background:white;border-radius:12px;overflow:hidden;box-shadow:0 12px 40px rgba(2,6,23,.06);transition:transform .32s,box-shadow .32s;padding:0}
  .card-course:hover{transform:translateY(-10px);box-shadow:0 28px 64px rgba(2,6,23,.09)}
  .card-course img{height:160px;object-fit:cover;width:100%}
  .card-course .body{padding:18px}
  .card-course h3{font-size:1.12rem;margin-bottom:8px;font-weight:700}
  .card-course p{color:#475569;font-size:.95rem;margin-bottom:12px}
  .card-course .meta{display:flex;justify-content:space-between;align-items:center}
  .pill{background:linear-gradient(90deg,#eef2ff,#fff);padding:6px 10px;border-radius:999px;font-weight:700;font-size:.92rem;color:#334155}

  /* -------------------------
     Stats
  --------------------------*/
  .stats{display:flex;gap:16px;justify-content:space-between;margin-top:22px;background:linear-gradient(90deg,#fff,#fbfdff);padding:18px;border-radius:12px}
  .stat{flex:1;text-align:center;padding:12px}
  .stat .num{font-weight:800;font-size:1.6rem;color:#0b1220}
  .stat .lbl{color:#64748b;margin-top:6px}

  /* -------------------------
     Instructors / Newsletter / Testimonials / Media / FAQ / Footer
  --------------------------*/
  .instructors-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
  .instructor{background:white;padding:18px;border-radius:12px;text-align:center;box-shadow:0 14px 40px rgba(2,6,23,.05);transition:transform .28s}
  .instructor:hover{transform:translateY(-6px)}
  .instructor img{width:110px;height:110px;border-radius:999px;margin:0 auto 10px;object-fit:cover}
  .instructor h4{font-weight:700;margin-bottom:6px}
  .instructor p{color:#64748b;font-size:.95rem}

  .newsletter{display:flex;gap:18px;align-items:center;padding:22px;background:linear-gradient(90deg,#0f172a,#1e293b);color:white;border-radius:12px}
  .newsletter input{flex:1;padding:12px;border-radius:8px;border:none;outline:none}
  .newsletter button{padding:12px 18px;border-radius:8px;border:none;background:#60a5fa;font-weight:700;color:#07263a}

  .testimonials-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
  .testimonial{background:#0f172a;color:white;padding:18px;border-radius:12px;box-shadow:0 12px 40px rgba(2,6,23,.06)}

  .media-logos{display:flex;gap:20px;align-items:center;flex-wrap:wrap}
  .media-logos img{width:110px;opacity:.75;transition:transform .2s,opacity .2s}
  .media-logos img:hover{transform:translateY(-6px);opacity:1}

  /* FAQ accordion */
  .accordion{max-width:920px;margin:0 auto}
  .accordion-item{background:white;border-radius:10px;margin-bottom:12px;overflow:hidden;border:1px solid rgba(2,6,23,.04)}
  .accordion-item button{width:100%;text-align:left;padding:16px;border:none;background:transparent;font-weight:700;display:flex;justify-content:space-between;align-items:center;cursor:pointer}
  .accordion-item .content{max-height:0;overflow:hidden;padding:0 16px 0;transition:max-height .28s ease}
  .accordion-item .content p{padding:12px 0;color:#475569}

  /* Footer */
  footer{padding:36px 6%;background:#071024;color:#cbd5e1;margin-top:28px;border-radius:12px}
  footer .foot-row{display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap;align-items:center}
  footer a{color:#cbd5e1}

  /* -------------------------
     Utilities & Animations
  --------------------------*/
  .fade-in{opacity:0;transform:translateY(26px);transition:all .8s cubic-bezier(.2,.9,.25,1)}
  .fade-in.show{opacity:1;transform:translateY(0)}

  /* Small screens */
  @media (max-width:1100px){
    .hero{grid-template-columns:1fr 420px}
    .course-grid{grid-template-columns:repeat(2,1fr)}
    .instructors-grid{grid-template-columns:repeat(2,1fr)}
    .testimonials-grid{grid-template-columns:repeat(2,1fr)}
  }
  @media (max-width:820px){
    header{padding:0 4%}
    .hero{grid-template-columns:1fr;gap:18px;padding:32px 4%}
    .hero-right{order:2}
    .course-grid{grid-template-columns:1fr}
    .instructors-grid{grid-template-columns:1fr}
    .testimonials-grid{grid-template-columns:1fr}
    .stats{flex-direction:column;gap:12px}
    nav{display:none}
  }
  @media (max-width:420px){
    .hero-left h1{font-size:1.6rem}
    .eyebrow{font-size:.78rem}
  }

  /* Logo icon */
.logo-icon{
  width:50px;height:50px;flex-shrink:0;
  filter:drop-shadow(0 0 8px rgba(99,102,241,0.4));
  animation:logoGlow 4s ease-in-out infinite;
}
@keyframes logoGlow{
  0%,100%{filter:drop-shadow(0 0 6px rgba(96,165,250,.6))}
  50%{filter:drop-shadow(0 0 14px rgba(167,139,250,.8))}
}

/* Wordmark */
.brand-text{display:flex;flex-direction:column}
.brand-title{
  font-weight:800;font-size:1.2rem;
  background:linear-gradient(90deg,#60a5fa,#a78bfa);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.brand-sub{font-size:.85rem;color:#64748b}


</style>
</head>
<body>

  <!-- PAGE TRANSITION -->
  <div class="page-transition" id="pageTransition">
    <div class="inner">
      <div class="spinner"></div>
      <div id="transitionText" style="font-weight:700">Loading...</div>
    </div>
  </div>

  <!-- HEADER -->
  <header>
  <div class="brand">
    <!-- LOGO ICON -->
    <svg class="logo-icon" viewBox="0 0 64 64" aria-hidden="true">
      <defs>
        <linearGradient id="gradLogo" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#60a5fa">
            <animate attributeName="stop-color" values="#60a5fa;#a78bfa;#60a5fa" dur="6s" repeatCount="indefinite" />
          </stop>
          <stop offset="100%" stop-color="#a78bfa">
            <animate attributeName="stop-color" values="#a78bfa;#60a5fa;#a78bfa" dur="6s" repeatCount="indefinite" />
          </stop>
        </linearGradient>
      </defs>
      <!-- Outer circle -->
      <circle cx="32" cy="32" r="30" fill="url(#gradLogo)" opacity="0.9"/>
      <!-- Inner C shapes -->
      <path d="M42 20a12 12 0 1 0 0 24" fill="none" stroke="white" stroke-width="5" stroke-linecap="round"/>
      <path d="M26 20a12 12 0 1 0 0 24" fill="none" stroke="white" stroke-width="5" stroke-linecap="round"/>
    </svg>

    <!-- WORDMARK -->
    <div class="brand-text">
      <span class="brand-title">Course Connect</span>
      <span class="brand-sub">Your online course programs</span>
    </div>
  </div>

  <nav>
    <a href="#courses">Courses</a>
    <a href="#instructors">Instructors</a>
    <a href="#testimonials">Testimonials</a>
    <a href="#faq">FAQ</a>
  </nav>

  <div style="display:flex;gap:12px;align-items:center">
    <button class="cta secondary" onclick="navigateToPage('student_page.php','Student Portal')"><i class="fas fa-user-graduate"></i> Student</button>
    <button class="cta" onclick="navigateToPage('admin_page.php','Admin Portal')"><i class="fas fa-user-shield"></i> Admin</button>
  </div>
</header>


  <!-- HERO -->
  <section class="hero">
    <div class="hero-left fade-in">
      <h1 style="margin-top: 40px;">Professional Certificate Programs Across Multiple Disciplines</h1>
<p>Learn from industry experts and top-tier faculty. Master essential skills, work on practical projects, and earn a certificate that boosts your career across various fields.</p>


      <div class="hero-ctas">
        <button class="cta" onclick="navigateToPage('student_page.php','Student Portal')"><i class="fas fa-play-circle"></i> View Programs</button>
        <button class="cta secondary" onclick="document.getElementById('syllabus-modal')?.scrollIntoView({behavior:'smooth'})"><i class="fas fa-file-download"></i> Download Syllabus</button>
      </div>

      <div class="muted-note">In collaboration with enterprise partners • Start dates & batch info available on course pages</div>

      <!-- quick stats -->
 <div class="stats" style="margin-top:20px;">
  <div class="stat">
    <div class="num" id="studentsCounter" data-target="0">0</div>
    <div class="lbl">Students</div>
  </div>
  <div class="stat">
    <div class="num" id="coursesCounter" data-target="0">0</div>
    <div class="lbl">Courses</div>
  </div>
  <div class="stat">
    <div class="num" id="instructorsCounter" data-target="0">0</div>
    <div class="lbl">Instructors</div>
  </div>
  <div class="stat">
    <div class="num" id="awardsCounter" data-target="0">0</div>
    <div class="lbl">Awards</div>
  </div>
</div>



      </div>
    </div>

    <div class="hero-right fade-in" aria-hidden="true">
      <div class="card" role="img" aria-label="IIT campus or course image">
        <img src="hero.avif" alt="Campus or courses">
      </div>
    </div>
  </section>

  <!-- COURSES -->
<section id="courses" class="section fade-in">
  <h2>Our Popular Courses</h2>
  <div class="course-grid" role="list">

    <article class="card-course" role="listitem">
      <img src="images/course1.png" alt="Programming & Software Development">
      <div class="body">
        <h3>Programming & Software Development</h3>
        <p>Learn modern programming languages, frameworks, and best practices through hands-on projects.</p>
        <div class="meta">
          <div class="pill">Self-Paced • 6 months</div>
          <a href="#" aria-label="Learn more about Programming & Software Development">Learn More →</a>
        </div>
      </div>
    </article>

    <article class="card-course" role="listitem">
      <img src="images/course2.png" alt="Business & Management">
      <div class="body">
        <h3>Business & Management</h3>
        <p>Master essential business skills, leadership strategies, and MBA-level insights for career growth.</p>
        <div class="meta">
          <div class="pill">Instructor-led • 4 months</div>
          <a href="#" aria-label="Learn more about Business & Management">Learn More →</a>
        </div>
      </div>
    </article>

    <article class="card-course" role="listitem">
      <img src="images/course3.png" alt="Creative Design & UI/UX">
      <div class="body">
        <h3>Creative Design & UI/UX</h3>
        <p>Develop design thinking, UI/UX skills, and creative tools to craft stunning digital experiences.</p>
        <div class="meta">
          <div class="pill">Project-based • 3 months</div>
          <a href="#" aria-label="Learn more about Creative Design & UI/UX">Learn More →</a>
        </div>
      </div>
    </article>

  </div>
</section>


  <!-- INSTRUCTORS -->
  <section id="instructors" class="section fade-in">
    <h2>Meet Our Instructors</h2>
    <div class="instructors-grid">
      <div class="instructor">
        <img src="uploads/profile_pics/teacher1.jpg" alt="Dr. Priya Sharma">
        <h4>Dr. XYZ Dehingia</h4>
        <p>Professor • Data Science & AI</p>
      </div>
      <div class="instructor">
        <img src="uploads/profile_pics/teacher2.jpg" alt="Mr. Rajeev Kumar">
        <h4>Mr. 321 Baruah</h4>
        <p>ML Specialist • Industry Practitioner</p>
      </div>
      <div class="instructor">
        <img src="uploads/profile_pics/teacher3.jpg" alt="Ms. Anjali Mehta">
        <h4>Ms. ABC Gogoi</h4>
        <p>Lead Fullstack Engineer</p>
      </div>
    </div>
  </section>

  <!-- NEWSLETTER -->
  <section class="section fade-in">
    <div class="newsletter" role="region" aria-label="Newsletter">
      <div style="flex:1">
        <h3 style="margin:0 0 6px;font-weight:800;color:white">Get course updates & exclusive offers</h3>
        <div style="color:rgba(255,255,255,.78);font-size:.95rem">Join our newsletter and never miss deadlines, scholarships and webinars.</div>
      </div>
      <form style="display:flex;gap:12px;width:540px" onsubmit="alert('Subscribed!');return false;">
        <input type="email" placeholder="Enter your email" required aria-label="Email address">
        <button type="submit">Subscribe</button>
      </form>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section id="testimonials" class="section fade-in">
    <h2>What Our Students Say</h2>
    <div class="testimonials-grid" aria-live="polite">
      <div class="testimonial">
        <p>"Course Connect transformed my career with practical projects and industry mentors."</p>
        <div style="margin-top:12px;font-weight:700">— XYZ S.</div>
      </div>
      <div class="testimonial">
        <p>"The instructors are top notch — clear, supportive and very experienced."</p>
        <div style="margin-top:12px;font-weight:700">— 456 K.</div>
      </div>
      <div class="testimonial">
        <p>"I landed a job after the capstone project. Highly recommended."</p>
        <div style="margin-top:12px;font-weight:700">— BAR 35.</div>
      </div>
    </div>
  </section>

  <!-- MEDIA / PARTNERS -->
<section class="section fade-in">
  <h2>Recognized By</h2>
  <div class="media-logos" style="margin-top:12px">
    <img src="images/logos/ibm-logo.png" alt="IBM" />
    <img src="images/logos/microsoft-logo.png" alt="Microsoft" />
    <img src="images/logos/google-logo.png" alt="Google" />
    <img src="images/logos/adobe-logo.png" alt="Adobe" />
  </div>
</section>


  <!-- FAQ -->
  <section id="faq" class="section fade-in">
    <h2>Frequently Asked Questions</h2>
    <div class="accordion" role="tablist" aria-label="Frequently Asked Questions">
      <div class="accordion-item">
        <button aria-expanded="false">What is Course Connect? <i class="fas fa-chevron-down"></i></button>
        <div class="content"><p>Course Connect is an online learning platform offering professional certificate programs, hands-on projects and mentorship.</p></div>
      </div>
      <div class="accordion-item">
        <button aria-expanded="false">How do I enroll? <i class="fas fa-chevron-down"></i></button>
        <div class="content"><p>Click the Student Portal or any "Learn More" on a course page and follow enrollment instructions. Scholarships and EMI options may be available.</p></div>
      </div>
      <div class="accordion-item">
        <button aria-expanded="false">Are certificates provided? <i class="fas fa-chevron-down"></i></button>
        <div class="content"><p>Yes — we provide verified certificates upon successful completion of course requirements and capstone projects.</p></div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="foot-row">
      <div>
        <div style="font-weight:800;color:white">Course Connect</div>
        <div style="color:#94a3b8;font-size:.95rem">© 2025 • Empowering education</div>
      </div>
      <div style="display:flex;gap:14px;align-items:center">
        <a href="#" aria-label="Privacy Policy">Privacy</a>
        <a href="#" aria-label="Terms of Service">Terms</a>
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.06);margin-top:18px;padding-top:14px;text-align:center;color:#475569;font-size:0.8rem;">
      Source available on <a href="https://github.com/Gyandeep09" target="_blank" rel="noopener" style="color:#60a5fa;font-weight:600;">@Gyandeep09</a> &middot; GitHub
    </div>
  </footer>

<script>
  /* -------------------------
     Page transition behavior
     - Keeps your original behavior:
       displays overlay while navigating to portal pages.
  --------------------------*/
/* -------------------------
   Page transition behavior
--------------------------*/
/* -------------------------
     Page transition behavior
--------------------------*/
const pageTransition = document.getElementById('pageTransition');

function navigateToPage(url, portalName) {
  document.getElementById('transitionText').textContent = `Redirecting to ${portalName}...`;
  pageTransition.classList.remove('hidden');
  setTimeout(() => { window.location.href = url; }, 300);
}

window.addEventListener('load', () => pageTransition.classList.add('hidden'));
window.addEventListener('pageshow', (evt) => {
  if (evt.persisted) pageTransition.classList.add('hidden');
});

/* -------------------------
     Scroll-triggered fade-ins
--------------------------*/
const fadeEls = document.querySelectorAll('.fade-in');
const fadeObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('show');
      fadeObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });

fadeEls.forEach(el => fadeObserver.observe(el));

/* -------------------------
     Stats Counter Animation
--------------------------*/
function animateCounter(el, target) {
  target = Number(target) || 0;
  const duration = 1200;
  const frameRate = 60;
  const totalFrames = Math.round(duration / (1000 / frameRate));
  let frame = 0;
  const easeOutQuad = t => t * (2 - t);
  const startValue = Number(el.textContent) || 0; // Start from current number
  const range = target - startValue;

  const counter = setInterval(() => {
    frame++;
    const progress = easeOutQuad(frame / totalFrames);
    const current = startValue + Math.round(range * progress);
    el.textContent = current;

    if (frame >= totalFrames) {
      clearInterval(counter);
      el.textContent = target; // Ensure it ends on the exact target
    }
  }, 1000 / frameRate);
}

function updateStats() {
  fetch('fetch_stats.php')
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
      animateCounter(document.getElementById('studentsCounter'), data.students);
      animateCounter(document.getElementById('coursesCounter'), data.courses);
      animateCounter(document.getElementById('instructorsCounter'), data.instructors);
      animateCounter(document.getElementById('awardsCounter'), data.awards);
    })
    .catch(err => console.error('Stats fetch error:', err));
}

// Initial call to load stats when the script runs
updateStats();

// Optional: refresh stats every minute
setInterval(updateStats, 60000);


/* -------------------------
     Accordion: open/close
--------------------------*/
document.querySelectorAll('.accordion-item').forEach(item => {
  const btn = item.querySelector('button');
  const content = item.querySelector('.content');
  btn.addEventListener('click', () => {
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    document.querySelectorAll('.accordion-item').forEach(it => {
      if (it !== item) {
        it.querySelector('button').setAttribute('aria-expanded', 'false');
        it.querySelector('.content').style.maxHeight = null;
        it.querySelector('i').classList.remove('rotate');
      }
    });
    if (!expanded) {
      btn.setAttribute('aria-expanded', 'true');
      content.style.maxHeight = content.scrollHeight + 'px';
      btn.querySelector('i').classList.add('rotate');
    } else {
      btn.setAttribute('aria-expanded', 'false');
      content.style.maxHeight = null;
      btn.querySelector('i').classList.remove('rotate');
    }
  });
});

const style = document.createElement('style');
style.innerHTML = `
  .accordion-item button i{transition:transform .28s}
  .accordion-item button i.rotate{transform:rotate(180deg)}
`;
document.head.appendChild(style);

document.querySelectorAll('.accordion-item button').forEach(btn => {
  btn.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
  });
});

/* -------------------------
     Header scroll hide/show
--------------------------*/
let lastScroll = 0;
const header = document.querySelector('header');
window.addEventListener('scroll', () => {
  const currentScroll = window.pageYOffset;
  if (currentScroll <= 0) {
    header.style.top = '0';
    return;
  }
  header.style.top = currentScroll > lastScroll ? '-80px' : '0';
  lastScroll = currentScroll;
});

function animateCounter(el, target) {
  target = Number(target) || 0; // ✅ ensures numeric value
  const duration = 1200;
  const frameRate = 60;
  const totalFrames = Math.round(duration / (1000 / frameRate));
  let frame = 0;
  const easeOutQuad = t => t*(2-t);

  const counter = setInterval(() => {
    frame++;
    const progress = easeOutQuad(frame / totalFrames);
    const current = Math.round(target * progress);
    el.textContent = current;
    if(frame >= totalFrames){
      clearInterval(counter);
      el.textContent = target;
    }
  }, 1000 / frameRate);
}


function updateStats() {
  fetch('fetch_stats.php')
    .then(res => res.json())
    .then(data => {
      animateCounter(document.getElementById('studentsCounter'), data.students);
      animateCounter(document.getElementById('coursesCounter'), data.courses);
      animateCounter(document.getElementById('instructorsCounter'), data.instructors);
      animateCounter(document.getElementById('awardsCounter'), data.awards);
    })
    .catch(err => console.error('Stats fetch error:', err));
}

// Call once on page load
updateStats();

// Optional: refresh every 30-60 seconds
setInterval(updateStats, 60000);

</script>
</body>
</html>
