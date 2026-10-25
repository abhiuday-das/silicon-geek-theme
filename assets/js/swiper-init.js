document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".cards-swiper").forEach((container) => {

    const options = JSON.parse(container.dataset.swiper);

    new Swiper(container, {
      slidesPerView: 1, // mobile default
      spaceBetween: options.spaceBetween,
      loop: true,
      autoplay: options.autoplay,

      navigation: options.navigation
        ? {
            nextEl: container.querySelector(".swiper-button-next"),
            prevEl: container.querySelector(".swiper-button-prev"),
          }
        : false,

      pagination: options.pagination
        ? {
            el: container.querySelector(".swiper-pagination"),
            clickable: true,
          }
        : false,

      // 📌 BREAKPOINTS FIX
      breakpoints: {
        768: {
          slidesPerView: 2, // Tablet
        },
        992: {
          slidesPerView: options.slidesPerView, // Desktop from data
        },
      },
    });
  });
});
