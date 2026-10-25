<?php
/*
 * Title: ECE Hero — Learn, Build, GATE
 * Slug: ts-block-theme/ece-hero
 * Categories: circuitshala-ece, featured, banner
 * Block Types: core/group 
 */
?>
<!-- wp:group {"className":"cs-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group cs-hero">
  <!-- wp:columns {"verticalAlignment":"center"} -->
  <div class="wp-block-columns are-vertically-aligned-center">
    <!-- wp:column {"width":"55%"} -->
    <div class="wp-block-column" style="flex-basis:55%">
      <!-- wp:paragraph {"className":"cs-badge"} --><p class="cs-badge">🇮🇳 Made for Indian ECE Students • Simple Hinglish Explanations</p><!-- /wp:paragraph -->
      <!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Learn <span class="cs-grad">ECE</span>. Build Projects.<br>Crack GATE &amp; Get Placed.</h1><!-- /wp:heading -->
      <!-- wp:paragraph {"className":"cs-lead"} --><p class="cs-lead">Year-wise notes, Embedded &amp; VLSI roadmaps, final-year projects with full circuit + code, and GATE EC PYQs — all in one place. Bilkul basics se, job-ready tak.</p><!-- /wp:paragraph -->

      <!-- wp:html -->
      <form class="cs-searchbar" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="search" name="s" placeholder="Search any topic… MOSFET, ESP32, Op-Amp, Verilog" aria-label="Search topics">
        <button class="cs-btn cs-btn--cyan" type="submit">Search Topic</button>
      </form>
      <p style="font-size:1.3rem;color:#cbd5e1;margin-top:1rem">🔥 Trending: <strong>ESP32 Projects</strong> • <strong>GATE EC PYQ</strong> • <strong>Verilog</strong> • <strong>Final Year Project Ideas</strong></p>
      <div class="cs-stats">
        <div class="cs-stat"><b>500+</b><span>Topics planned</span></div>
        <div class="cs-stat"><b>120+</b><span>Project ideas</span></div>
        <div class="cs-stat"><b>2</b><span>Job tracks</span></div>
        <div class="cs-stat"><b>100%</b><span>Beginner friendly</span></div>
      </div>
      <!-- /wp:html -->

      <!-- wp:buttons -->
      <div class="wp-block-buttons">
        <!-- wp:button {"className":"is-style-primary"} --><div class="wp-block-button is-style-primary"><a class="wp-block-button__link wp-element-button" href="/learn/">Start Learning Free →</a></div><!-- /wp:button -->
        <!-- wp:button {"className":"is-style-whatsapp"} --><div class="wp-block-button is-style-whatsapp"><a class="wp-block-button__link wp-element-button" href="/project-help/">📲 Project Help on WhatsApp</a></div><!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column {"width":"45%"} -->
    <div class="wp-block-column" style="flex-basis:45%">
      <!-- wp:group {"className":"cs-panel"} -->
      <div class="wp-block-group cs-panel">
        <!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Your ECE Roadmap: 1st Year → Core Job</h3><!-- /wp:heading -->
        <!-- wp:html -->
        <div style="display:flex;justify-content:space-between;margin:1.6rem 0 .8rem"><b>Semester + Skill Progress</b><b>68%</b></div>
        <div class="cs-progress"><i style="width:68%"></i></div>
        <a class="cs-lesson" href="/subjects/analog-circuits/"><span class="cs-lesson-icon">🔌</span><span style="flex:1"><b>Analog Circuits — Op-Amp</b><br><small>Notes • PYQ • 12 min read</small></span><span>→</span></a>
        <a class="cs-lesson" href="/projects/"><span class="cs-lesson-icon">🤖</span><span style="flex:1"><b>ESP32 IoT Projects</b><br><small>Circuit • Code • Components</small></span><span class="cs-pill is-amber">PROJECTS</span></a>
        <a class="cs-lesson" href="/vlsi/"><span class="cs-lesson-icon">💾</span><span style="flex:1"><b>Verilog for VLSI Jobs</b><br><small>Roadmap • Interview Qs</small></span><span>→</span></a>
        <a class="cs-lesson" href="/gate-pyq/"><span class="cs-lesson-icon">🏆</span><span style="flex:1"><b>GATE EC PYQs</b><br><small>With step-by-step solutions</small></span><span class="cs-pill">GATE</span></a>
        <!-- /wp:html -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
