<section class="studio-hero">
  <div class="container">
    <div class="studio-hero-grid">
      <div class="studio-hero-copy">
        <span class="studio-kicker"><span></span> YOUR NEXT CHAPTER STARTS HERE</span>
        <h1 class="hero-animated">
          <span class="hero-line hero-line-1">Your trusted academic resource</span>
          <span class="hero-line hero-line-2">&amp; <em class="hero-highlight">research support platform.</em></span>
        </h1>
        <p class="studio-hero-intro">Empowering Nepalese students with quality academic resources.</p>
        <p class="studio-hero-description">Find the references, research guides and expert support you need to move forward with confidence.</p>
        <form class="studio-search" action="<?= BASE_URL ?>/resources.php" method="get" role="search">
          <div class="studio-search-line"><i class="bi bi-search" aria-hidden="true"></i><input name="search" type="search" placeholder="What are you working on?" aria-label="Search academic resources"><button class="btn btn-primary" type="submit">Find resources <i class="bi bi-arrow-right"></i></button></div>
          <div class="studio-search-filters"><label>Campus<select name="campus"><option value="">All campuses</option><option>Gyan Jyoti Campus</option><option>Babai Campus</option><option>Gorkha Campus</option><option>Mark International College</option><option>Mahendra Multiple Campus</option><option>Deukhuri Bahumukhi Campus</option></select></label><label>Program<select name="program"><option value="">All programs</option><option>BBS</option><option>BBA</option><option>Ma In Societies</option><option>MBS</option><option>MBA</option></select></label></div>
        </form>
        <div class="studio-hero-actions"><a href="<?= BASE_URL ?>/resources.php">Explore resources <i class="bi bi-arrow-up-right"></i></a><a href="<?= BASE_URL ?>/services.php">Get research support <i class="bi bi-arrow-up-right"></i></a></div>
        <div class="studio-trust"><span><i class="bi bi-shield-check"></i> Protected downloads</span><span><i class="bi bi-wallet2"></i> Pay with eSewa</span></div>
      </div>
      <div class="studio-hero-art">
        <div class="studio-photo-frame"><img src="<?= BASE_URL ?>/assets/images/hero_students.jpg" alt="Students collaborating on their academic research" fetchpriority="high" width="640" height="720"><div class="studio-photo-caption"><span>LEARNING, TOGETHER</span><strong>Big ideas begin<br>with a little curiosity.</strong></div></div>
        <div class="studio-floating-note"><div class="studio-note-icon"><i class="bi bi-journal-bookmark"></i></div><div><strong>A little guidance.<br>A lot of potential.</strong><span>Resources for your original work</span></div></div>
        <div class="studio-photo-tag"><i class="bi bi-mortarboard"></i> Made for Nepal's students</div>
      </div>
    </div>
    <div class="studio-stats" aria-label="AcademicHub in numbers">
      <div class="studio-stats-intro"><span class="studio-kicker">GROWING WITH YOU</span><strong>A community built<br>around learning.</strong></div>
      <?php foreach(['students'=>'Students','resources'=>'Academic resources','campuses'=>'Campuses in our catalog'] as $key=>$label): ?><div><strong data-count="<?= (int)$site_stats[$key] ?>"><?= number_format((int)$site_stats[$key]) ?></strong><span><?= $label ?></span></div><?php endforeach; ?>
      <div><strong><i class="bi bi-mortarboard"></i></strong><span>Research Support</span><a href="<?= BASE_URL ?>/services.php" class="small d-block mt-2">Find expert guidance →</a></div>
    </div>
  </div>
</section>

<style>
/* Hero Line-by-Line Animation */
.hero-line {
  display: block;
  opacity: 0;
  transform: translateY(30px);
  animation: heroFadeUp 0.8s ease-out forwards;
}

.hero-line-1 {
  animation-delay: 0.2s;
}

.hero-line-2 {
  animation-delay: 0.6s;
}

@keyframes heroFadeUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Hero Highlight Gradient Animation */
.hero-highlight {
  background: linear-gradient(135deg, #2563EB 0%, #3B82F6 50%, #60A5FA 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  background-size: 200% 200%;
  animation: heroGradient 3s ease-in-out infinite;
}

@keyframes heroGradient {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

/* Hero Section Entrance Animation */
.studio-hero-copy {
  animation: heroEntrance 1s ease-out;
}

@keyframes heroEntrance {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.studio-hero-art {
  animation: heroArtEntrance 1s ease-out 0.3s backwards;
}

@keyframes heroArtEntrance {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Smooth entrance for hero elements */
.studio-kicker {
  animation: fadeInUp 0.6s ease-out 0.1s backwards;
}

.studio-hero-intro {
  animation: fadeInUp 0.6s ease-out 0.8s backwards;
}

.studio-hero-description {
  animation: fadeInUp 0.6s ease-out 1s backwards;
}

.studio-search {
  animation: fadeInUp 0.6s ease-out 1.2s backwards;
}

.studio-hero-actions {
  animation: fadeInUp 0.6s ease-out 1.4s backwards;
}

.studio-trust {
  animation: fadeInUp 0.6s ease-out 1.6s backwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

<section class="studio-section" id="programs"><div class="container"><div class="studio-section-heading"><div><span class="studio-kicker">FIND YOUR FOCUS</span><h2>Your program.<br>Your next step.</h2></div><p>Purposeful reference materials for every stage of your academic journey.</p></div><div class="studio-programs">
<?php foreach(['BBS'=>['Business Studies','bi-bar-chart'],'BBA'=>['Business Administration','bi-briefcase'],'Ma In Societies'=>['Computer Applications','bi-code-slash'],'MBS'=>['Business Studies · Master’s','bi-journal-richtext'],'MBA'=>['Business Administration · Master’s','bi-mortarboard']] as $programName=>[$programLabel,$programIcon]): ?><a class="studio-program <?= $programName==='MBA'?'studio-program-featured':'' ?>" href="<?= BASE_URL ?>/<?= $programName==='MBA'?'mba.php':'resources.php?program='.$programName ?>"><i class="bi <?= $programIcon ?>"></i><strong><?= $programName ?></strong><span><?= $programLabel ?></span><i class="bi bi-arrow-up-right studio-program-arrow"></i></a><?php endforeach; ?>
</div></div></section>

<section class="studio-section studio-section-tint" id="featured"><div class="container"><div class="studio-section-heading"><div><span class="studio-kicker">THE ACADEMIC LIBRARY</span><h2>Start with a better reference.</h2></div><a href="<?= BASE_URL ?>/resources.php" class="btn btn-outline-primary">Browse the collection <i class="bi bi-arrow-right"></i></a></div>
<div class="studio-resource-grid">
<?php foreach($featured_resources as $res): ?>
<article class="studio-resource-card">
  <a class="studio-resource-cover" href="<?= BASE_URL ?>/resource-details.php?id=<?= (int)$res['id'] ?>" aria-label="Preview <?= htmlspecialchars($res['title']) ?>">
    <?php if(!empty($res['preview_image']) && is_file(PREVIEW_IMAGE_DIR.basename($res['preview_image']))): ?><img src="<?= BASE_URL ?>/uploads/previews/<?= rawurlencode(basename($res['preview_image'])) ?>" alt="Cover of <?= htmlspecialchars($res['title']) ?>" loading="lazy" width="600" height="400"><?php else: ?><div class="studio-document"><span>ACADEMICHUB / REFERENCE LIBRARY</span><i class="bi bi-file-earmark-richtext"></i><strong><?= htmlspecialchars($res['program']) ?></strong><span><?= htmlspecialchars($res['document_type']) ?></span></div><?php endif; ?>
    <span class="studio-format"><?= htmlspecialchars($res['file_format']) ?> · <?= (int)$res['pages'] ?> pages</span>
  </a><div class="studio-resource-body"><div class="studio-resource-meta"><span><?= htmlspecialchars($res['program']) ?></span><span><?= htmlspecialchars($res['subject']) ?></span></div><h3><a href="<?= BASE_URL ?>/resource-details.php?id=<?= (int)$res['id'] ?>"><?= htmlspecialchars($res['title']) ?></a></h3><p><i class="bi bi-buildings"></i> <?= htmlspecialchars($res['campus']) ?></p><div class="studio-resource-bottom"><div><span>Resource price</span><strong><?= format_npr($res['price']) ?></strong></div><div class="d-flex gap-2"><a class="btn btn-light" href="<?= BASE_URL ?>/resource-details.php?id=<?= (int)$res['id'] ?>">Preview</a><a class="btn btn-primary" href="<?= BASE_URL ?>/payment.php?resource_id=<?= (int)$res['id'] ?>">Buy <i class="bi bi-arrow-right"></i></a></div></div></div>
</article><?php endforeach; ?>
</div><?php if(!$featured_resources): ?><div class="empty-state card"><h3>New resources are on the way</h3><p>Explore the catalog or ask our team for research support.</p><a class="btn btn-primary" href="<?= BASE_URL ?>/resources.php">Explore resources</a></div><?php endif; ?>
</div></section>

<section class="studio-section" id="campuses"><div class="container"><div class="studio-section-heading"><div><span class="studio-kicker">CLOSER TO YOUR CAMPUS</span><h2>A familiar place to begin.</h2></div><p>Discover reference materials from across Nepal’s academic community.</p></div><div class="studio-campus-grid">
<?php foreach(['Gyan Jyoti Campus'=>['campus_gyan_jyoti.jpg','Dang, Nepal'],'Babai Campus'=>['campus_babai.jpg','Dang, Nepal'],'Gorkha Campus'=>['campus_gorkha.jpg','Nepal']] as $campusName=>[$campusPhoto,$campusLocation]): ?><a class="studio-campus" href="<?= BASE_URL ?>/resources.php?campus=<?= urlencode($campusName) ?>"><img src="<?= BASE_URL ?>/assets/images/<?= $campusPhoto ?>" alt="<?= htmlspecialchars($campusName) ?>" loading="lazy" width="600" height="400"><div><span><i class="bi bi-geo-alt"></i> <?= $campusLocation ?></span><h3><?= htmlspecialchars($campusName) ?></h3><span class="studio-campus-cta">Explore campus resources <i class="bi bi-arrow-up-right"></i></span></div></a><?php endforeach; ?>
</div><div class="studio-campus-more"><span>More places. More possibilities.</span><?php foreach(['Mark International College','Mahendra Multiple Campus','Deukhuri Bahumukhi Campus','Other Campuses'] as $campusName): ?><a href="<?= BASE_URL ?>/resources.php?campus=<?= urlencode($campusName) ?>"><?= htmlspecialchars($campusName) ?> <i class="bi bi-arrow-up-right"></i></a><?php endforeach; ?></div></div></section>

<section class="studio-section studio-process-section" id="how-it-works"><div class="container"><div class="studio-section-heading"><div><span class="studio-kicker">LESS FRICTION. MORE LEARNING.</span><h2>From discovery to your desk.</h2></div><p>Four simple steps to your next academic resource.</p></div><ol class="studio-process">
<?php foreach(['Find'=>['bi-search','Search by campus, program or subject.'],'Preview'=>['bi-file-earmark-text','Explore the structure and resource details.'],'Pay'=>['bi-wallet2','Complete your payment through eSewa.'],'Download'=>['bi-cloud-arrow-down','Access your file after payment verification.']] as $step=>[$icon,$copy]): ?><li><i class="bi <?= $icon ?>"></i><h3><?= $step ?></h3><p><?= $copy ?></p></li><?php endforeach; ?></ol></div></section>

<section class="studio-section" id="services"><div class="container"><div class="studio-section-heading"><div><span class="studio-kicker">A LITTLE EXPERT GUIDANCE</span><h2>Your work. Stronger support.</h2></div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/services.php">Explore all services <i class="bi bi-arrow-right"></i></a></div><div class="studio-services">
<?php foreach(['APA Formatting'=>['bi-text-paragraph','Give your research a clear, consistent academic format.'],'Research Guidance'=>['bi-compass','Build confidence in your questions and methodology.'],'Questionnaire Design'=>['bi-ui-checks','Design better questions for more useful findings.'],'Data Analysis Support'=>['bi-graph-up-arrow','Make sense of your data with practical SPSS guidance.'],'Report Formatting'=>['bi-file-earmark-break','Bring your chapters, tables and references together.'],'Proposal Review'=>['bi-pencil-square','Refine your proposal before taking the next step.']] as $serviceName=>[$serviceIcon,$serviceCopy]): ?><a href="<?= BASE_URL ?>/services.php?type=<?= urlencode($serviceName) ?>" class="studio-service"><i class="bi <?= $serviceIcon ?>"></i><h3><?= htmlspecialchars(['Report Formatting'=>'Proofreading & Formatting','Proposal Review'=>'Research Proposal','Data Analysis Support'=>'SPSS / Data Analysis'][$serviceName] ?? $serviceName) ?></h3><p><?= $serviceCopy ?></p><span>Find out more <i class="bi bi-arrow-up-right"></i></span></a><?php endforeach; ?>
</div></div></section>

<?php require __DIR__.'/home-community.php'; ?>

<section class="studio-section studio-section-tint" id="testimonials"><div class="container"><div class="studio-section-heading"><div><span class="studio-kicker">IN THEIR OWN WORDS</span><h2>Learning that makes a difference.</h2></div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/dashboard.php#review-pane">Share your experience</a></div><div class="studio-reviews">
<?php foreach($homepage_reviews as $review): ?><article class="studio-review"><div class="studio-review-rating" aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?php for($star=0;$star<5;$star++): ?><i class="bi <?= $star<(int)$review['rating']?'bi-star-fill':'bi-star' ?>" aria-hidden="true"></i><?php endfor; ?></div><blockquote>“<?= htmlspecialchars($review['review_text']) ?>”</blockquote><div class="d-flex gap-3 align-items-center"><?= render_user_avatar($review['profile_image']??null,$review['student_name'],44) ?><div><strong><?= htmlspecialchars($review['student_name']) ?></strong><span><?= htmlspecialchars($review['program']) ?> · <?= htmlspecialchars($review['student_campus']) ?></span></div></div></article><?php endforeach; ?>
</div><?php if(!$homepage_reviews): ?><p class="text-muted">Student reviews will appear here after approval. Share your experience from your dashboard.</p><?php endif; ?></div></section>

<section class="studio-section"><div class="container"><div class="studio-faq-grid"><div><span class="studio-kicker">GOOD QUESTIONS</span><h2>A little clarity,<br>before you begin.</h2><p class="text-muted">Need a hand with something else?</p><a href="<?= BASE_URL ?>/services.php" class="btn btn-outline-primary">Talk to our support team</a></div><div class="accordion" id="academicFaq"><?php foreach($page_schema['mainEntity'] as $faqIndex=>$faq): ?><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-<?= $faqIndex ?>" aria-expanded="false" aria-controls="faq-<?= $faqIndex ?>"><?= htmlspecialchars($faq['name']) ?></button></h3><div id="faq-<?= $faqIndex ?>" class="accordion-collapse collapse" data-bs-parent="#academicFaq"><div class="accordion-body"><?= htmlspecialchars($faq['acceptedAnswer']['text']) ?></div></div></div><?php endforeach; ?></div></div></div></section>
<div class="container"><div class="studio-integrity"><i class="bi bi-shield-check"></i><p><strong>Reference responsibly. Create originally.</strong> Our materials support learning and research. Always prepare and submit your own original work.</p><a href="<?= BASE_URL ?>/about.php#terms">Our commitment <i class="bi bi-arrow-right"></i></a></div></div>
