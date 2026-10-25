// Add your JS customizations here
//gravity forms error handling
jQuery(document).on(
  "gform_post_render",
  function (event, form_id, current_page) {
    // code to trigger on form or form page render
    //Error for form with static labels
    jQuery(".gfield_error > .ginput_container").focusin(function () {
      jQuery(this).parent("li").children("label").show();
      jQuery(this).parent("li").find(".validation_message").hide();
      jQuery(this).parent("li").removeClass("gfield_error");
    });
    console.log("form render");
    toggleFloatLabel(".ginput_container_text");
    toggleFloatLabel(".ginput_container_textarea");
    toggleFloatLabel(".ginput_container_phone");
    toggleFloatLabel(".ginput_container_email");
    toggleFloatLabel(".ginput_container_date");
    toggleFloatLabel(".ginput_container_select");
    //Code for form with floating labels
    jQuery(".ginput_container_text").focusin(function () {
      jQuery(this).parent("div").children("label").addClass("float_label");
    });

    jQuery(".ginput_container_text").focusout(function () {
      toggleFloatLabel(this, "input");
    });

    jQuery(".ginput_container_email").focusin(function () {
      jQuery(this).parent("div").children("label").addClass("float_label");
    });

    jQuery(".ginput_container_email").focusout(function () {
      toggleFloatLabel(this, "email");
    });
    jQuery(".ginput_container_select").focusin(function () {
      jQuery(this).parent("div").children("label").addClass("float_label");
    });
    jQuery(".ginput_container_select").focusout(function () {
      toggleFloatLabel(this, "select");
    });
    jQuery(".ginput_container_date").focusin(function () {
      jQuery(this).parent("div").children("label").addClass("float_label");
    });

    jQuery(".ginput_container_date").focusout(function () {
      toggleFloatLabel(this, "input");
    });

    jQuery(".ginput_container_textarea").focusin(function () {
      jQuery(this).parent("div").children("label").addClass("float_label");
    });

    jQuery(".ginput_container_textarea").focusout(function () {
      toggleFloatLabel(this, "textarea");
    });

    jQuery(".ginput_container_phone").focusin(function () {
      jQuery(this).parent("div").children("label").addClass("float_label");
    });

    jQuery(".ginput_container_phone").focusout(function () {
      toggleFloatLabel(this, "tel");
    });

    //Error handling for form with floating labels
    jQuery(".floating_labels .gfield_error > label").hide();
    jQuery(".floating_labels .gfield_error .validation_message").addClass(
      "validation_message--float",
    );
  },
);

function toggleFloatLabel(selector, type) {
  var containerClass = ".ginput_container_text";
  if (typeof type == "undefined" || !type) {
    containerClass = selector;
  }
  if (containerClass == ".ginput_container_date") {
    type = "input";
  }

  if (type == "textarea") {
    containerClass = ".ginput_container_textarea";
  }

  if (type == "email") {
    containerClass = ".ginput_container_email";
    type = "input";
  }

  if (type == "tel") {
    containerClass = ".ginput_container_phone";
    type = "input";
  }
  if (type == "select") {
    containerClass = ".ginput_container_select";
    type = "select";
  }

  jQuery(selector)
    .children(type)
    .each(function () {
      if (!jQuery(this).val() || jQuery(this).val() == "(___) ___-____") {
        jQuery(this)
          .parent(containerClass)
          .parent("div")
          .find("label")
          .removeClass("float_label");
      } else {
        jQuery(this)
          .parent(containerClass)
          .parent("div")
          .find("label")
          .addClass("float_label");
      }
    });
}
// Add your JS customizations here
document.addEventListener("DOMContentLoaded", function () {
  // Select all inputs inside elements with class "numeric_keyboard"
  const inputs = document.querySelectorAll(".numeric_keyboard input");

  // Set the inputmode attribute to numeric
  inputs.forEach(function (input) {
    input.setAttribute("inputmode", "numeric");
  });
});

// Sticky Header Functionality
window.addEventListener("scroll", function () {
  const header = document.querySelector("header");
  const body = document.body;
  if (header) {
    if (window.scrollY > 50) {
      header.classList.add("sticky");
      const headerHeight = header.offsetHeight;
      body.style.paddingTop = headerHeight + "px";
    } else {
      header.classList.remove("sticky");
      body.style.paddingTop = "0px";
    }
  }
});

// // Menu Functionality
// const hamburgerBtn = document.querySelector(".hamburger-btn"),
//   closeBtn = document.querySelector(".close-btn"),
//   mobileHeaderNav = document.querySelector(".mobile-header-nav");

// if (hamburgerBtn && closeBtn && mobileHeaderNav) {
//   const toggleMenu = (show) => {
//     mobileHeaderNav.classList.toggle("d-block", show);
//   };

//   hamburgerBtn.addEventListener("click", () => toggleMenu(true));
//   closeBtn.addEventListener("click", () => toggleMenu(false));
// }
document.addEventListener("DOMContentLoaded", function () {
  const hamburgerBtn = document.querySelector(".hamburger-btn");
  const mobileHeaderNav = document.querySelector("#mobile-header-nav-");
  const ctaPopup = document.querySelector("#cta-popup");

  if (!hamburgerBtn || !mobileHeaderNav || !ctaPopup) {
    console.warn("Mobile header elements missing");
    return;
  }

  // Get CTA buttons ONLY from main header and menu (NOT from popup)
  const mainCTABtn = document.querySelector(
    ".mobile-header-header .callctaBtn",
  );
  const menuCTABtn = mobileHeaderNav.querySelector(".callctaBtn");

  // Get close button from the menu
  const closeBtn = mobileHeaderNav.querySelector(".close-btn");

  // Create overlay element
  const overlay = document.createElement("div");
  overlay.className = "mobile-overlay";
  document.querySelector(".mobile-header").appendChild(overlay);

  const hamburgerIcon = hamburgerBtn.querySelector("i");

  const setHamburgerOpen = () => {
    hamburgerIcon.className = hamburgerIcon.className.replace(
      "icon-bars2",
      "icon-xmark1",
    );
  };

  const setHamburgerClosed = () => {
    hamburgerIcon.className = hamburgerIcon.className.replace(
      "icon-xmark1",
      "icon-bars2",
    );
  };

  const showOverlay = () => {
    overlay.classList.add("active");
  };

  const hideOverlay = () => {
    overlay.classList.remove("active");
  };

  const openMenu = () => {
    mobileHeaderNav.classList.add("d-block");
    mobileHeaderNav.style.display = "block";
    ctaPopup.classList.remove("d-block");
    ctaPopup.style.display = "none";
    showOverlay();
    setHamburgerOpen();
  };

  const openCTA = () => {
    ctaPopup.classList.add("d-block");
    ctaPopup.style.display = "block";
    showOverlay();
    setHamburgerOpen();
  };

  const closeAll = () => {
    mobileHeaderNav.classList.remove("d-block");
    mobileHeaderNav.style.display = "none";
    ctaPopup.classList.remove("d-block");
    ctaPopup.style.display = "none";
    hideOverlay();
    setHamburgerClosed();
  };

  /* =========================
     HAMBURGER BUTTON
     ========================= */
  hamburgerBtn.addEventListener("click", function () {
    const isMenuOpen = mobileHeaderNav.classList.contains("d-block");
    const isCTAOpen = ctaPopup.classList.contains("d-block");

    if (isMenuOpen || isCTAOpen) {
      closeAll();
    } else {
      openMenu();
    }
  });

  /* =========================
     CLOSE BUTTON (in menu)
     ========================= */
  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      closeAll();
    });
  }

  /* =========================
     MAIN HEADER CTA BUTTON - ONLY OPENS
     ========================= */
  if (mainCTABtn) {
    mainCTABtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const isCTAOpen = ctaPopup.classList.contains("d-block");

      // Only open if not already open
      if (!isCTAOpen) {
        openCTA();
      }
      // Do nothing if already open - only close button can close it
    });
  }

  /* =========================
     MENU CTA BUTTON - ONLY OPENS
     ========================= */
  if (menuCTABtn) {
    menuCTABtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const isCTAOpen = ctaPopup.classList.contains("d-block");

      // Only open if not already open
      if (!isCTAOpen) {
        openCTA();
      }
      // Do nothing if already open - only close button can close it
    });
  }

  /* =========================
     OVERLAY CLICK TO CLOSE ALL
     ========================= */
  overlay.addEventListener("click", function () {
    closeAll();
  });

  /* =========================
     SUBMENU TOGGLE
     ========================= */
  const menuItems = mobileHeaderNav.querySelectorAll(
    ".wp-block-navigation-item",
  );

  menuItems.forEach((item) => {
    const submenuIcon = item.querySelector(
      ".wp-block-navigation__submenu-icon",
    );
    const submenu = item.querySelector(
      ".wp-block-navigation__submenu-container",
    );

    if (submenuIcon && submenu) {
      submenuIcon.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();

        item.classList.toggle("is-open");
        submenu.classList.toggle("is-open");
      });
    }
  });
});
//fall back image js

document.addEventListener("DOMContentLoaded", function () {
  // Loop through each post template group (adjust selector if needed)
  document
    .querySelectorAll(".wp-block-post-template .wp-block-group")
    .forEach(function (group) {
      var featuredFigure = group.querySelector(
        "figure.wp-block-post-featured-image",
      );
      var fallbackFigure = group.querySelector(
        "figure.fallback-img, .fallback-img",
      );

      if (!fallbackFigure) return;

      var hasFeatured = false;

      if (featuredFigure) {
        var img = featuredFigure.querySelector("img");
        // check if img exists and has a valid src
        if (img && img.getAttribute("src") && img.naturalWidth !== 0) {
          hasFeatured = true;
        }
      }

      if (hasFeatured) {
        fallbackFigure.style.display = "none";
      } else {
        fallbackFigure.style.display = "block";
      }
    });
});
/* ==========================================================================

TOP MENU AUTO-ACTIVE SYSTEM
Current page URL se matching link par active class lagata hai
Submenu page open hone par uske parent TOP MENU ko bhi active karta hai
Multiple menu levels / WordPress Navigation structure me 100% stable
Sirf exact URL match par active add hoti hai (no partial matching)
Homepage par koi active nahi lagta
========================================================================= */
document.addEventListener("DOMContentLoaded", function () {
  // All links (top + submenu)
  const allLinks = document.querySelectorAll(".desktop-header nav a"); // Current URL
  let currentURL = window.location.origin + window.location.pathname;
  currentURL = currentURL.replace(/\/$/, "");
  let activeLink = null; // STEP 1: Find correct link that matches the current page URL
  allLinks.forEach((link) => {
    let cleanURL = link.href.replace(/\/$/, "");
    if (cleanURL === currentURL) {
      activeLink = link;
    }
  });
  if (!activeLink) return; // STEP 2: Active class submenu link ko do
  activeLink.classList.add("active"); // STEP 3: Sabse nearest TOP MENU find karo (no matter how deep submenu is)
  let parentLi = activeLink.closest("li"); // Loop until LI is top-level (li whose parent ul is the first nav ul)
  while (
    parentLi &&
    !parentLi.parentElement.matches(".desktop-header nav > ul")
  ) {
    parentLi = parentLi.parentElement.closest("li");
  } // Parent top menu anchor
  if (parentLi) {
    let topMenuLink = parentLi.querySelector(":scope > a");
    if (topMenuLink) {
      topMenuLink.classList.add("active");
    }
  }
});

/**
 * Video Mute Toggle (IMAGE ICON VERSION)
 * Adds a mute/unmute button with PNG/JPG/WebP icons
 * Works on .wp-block-video and autoplay videos
 */

(function () {
  "use strict";

  function initVideoMuteToggle() {
    // Find all video elements that should have mute toggle
    const videos = document.querySelectorAll(
      ".wp-block-video video, video[autoplay]",
    );

    if (!videos.length) return;

    videos.forEach((video) => {
      // Skip if button already exists
      if (video.parentElement.querySelector(".video-mute-toggle")) return;

      // Create mute toggle button
      const button = document.createElement("button");
      button.className = "video-mute-toggle";
      button.setAttribute("aria-label", "Unmute video");
      button.setAttribute("type", "button");

      // -----------------------------
      // ICON IMAGE PATHS (EDIT THESE)
      // -----------------------------
      const muteIconSrc = "/wp-content/uploads/2026/02/unmute.png";
      const unmuteIconSrc = "/wp-content/uploads/2026/02/mute.png";

      // Create image element
      const iconImg = document.createElement("img");
      iconImg.className = "video-mute-icon";
      iconImg.alt = "Mute / Unmute";
      iconImg.style.display = "block";

      button.appendChild(iconImg);

      // -----------------------------
      // Button State Handler
      // -----------------------------
      function updateButtonState() {
        if (video.muted) {
          iconImg.src = unmuteIconSrc;
          button.setAttribute("aria-label", "Unmute video");
        } else {
          iconImg.src = muteIconSrc;
          button.setAttribute("aria-label", "Mute video");
        }
      }

      // -----------------------------
      // Toggle mute on click
      // -----------------------------
      button.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        video.muted = !video.muted;
        updateButtonState();
      });

      // -----------------------------
      // Make parent position relative
      // -----------------------------
      const parent = video.parentElement;
      const parentPosition = window.getComputedStyle(parent).position;
      if (parentPosition === "static") {
        parent.style.position = "relative";
      }

      // -----------------------------
      // Add button to DOM
      // -----------------------------
      parent.appendChild(button);

      // -----------------------------
      // Initialize state
      // -----------------------------
      updateButtonState();
    });
  }

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initVideoMuteToggle);
  } else {
    initVideoMuteToggle();
  }
})();
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".vide-thumb").forEach(function (thumb) {
    thumb.addEventListener("click", function () {
      let wrapper = this.closest(".video-wrapper");
      let videoBox = wrapper.querySelector(".video-box");
      let iframe = videoBox.querySelector("iframe");

      let videoURL = this.getAttribute("data-video-url");

      iframe.setAttribute("src", videoURL);

      this.style.display = "none";
      videoBox.style.display = "block";
    });
  });
});
