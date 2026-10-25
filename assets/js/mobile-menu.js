function toggleMenu(e) {
  e.stopPropagation();

  this.classList.toggle("is-open");
  const submenu = this.querySelector(".wp-block-navigation-submenu");
  if (submenu) submenu.classList.toggle("is-open");
}

function closeAllMenus() {
  const openItems = document.querySelectorAll(".wp-block-navigation-item.is-open");
  openItems.forEach((item) => {
    item.classList.remove("is-open");

    const submenu = item.querySelector(".wp-block-navigation-submenu.is-open");
    if (submenu) submenu.classList.remove("is-open");
  });
}

function initMobileMenu() {
  const items = document.querySelectorAll(".wp-block-navigation-item");

  items.forEach((li) => {
    li.removeEventListener("click", toggleMenu);

    if (window.innerWidth <= 1024) {
      li.addEventListener("click", toggleMenu);
    }
  });
}

// Init on load
document.addEventListener("DOMContentLoaded", () => {
  initMobileMenu();

  // Close menus on outside click
  document.addEventListener("click", (e) => {
    const nav = document.querySelector(".wp-block-navigation");

    if (!nav.contains(e.target)) {
      closeAllMenus();
    }
  });
});

// Re-init on resize
window.addEventListener("resize", initMobileMenu);
