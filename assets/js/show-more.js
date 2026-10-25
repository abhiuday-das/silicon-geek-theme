document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".js-read-toggle").forEach(function (btnWrapper) {
    const btn = btnWrapper.querySelector("a");

    btnWrapper.addEventListener("click", function (e) {
      e.preventDefault();

      const card = btnWrapper.closest(".team-card");
      if (!card) return;

      const container = card.querySelector(".team-readmore");
      const inner = card.querySelector(".team-readmore-inner");
      if (!container || !inner) return;

      const isExpanded = container.classList.contains("is-expanded");

      if (!isExpanded) {
        // EXPAND
        container.classList.add("is-expanded");

        // IMPORTANT: animate INNER, not container
        inner.style.maxHeight = inner.scrollHeight + "px";

        btn.textContent = "Read Less";
        btn.setAttribute("aria-expanded", "true");
      } else {
        // COLLAPSE
        inner.style.maxHeight = inner.scrollHeight + "px";
        inner.offsetHeight; // force reflow

        inner.style.maxHeight = "0px";
        container.classList.remove("is-expanded");

        btn.textContent = "Read More";
        btn.setAttribute("aria-expanded", "false");
      }
    });
  });
});
