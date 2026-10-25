gsap.registerPlugin(ScrollTrigger);

window.addEventListener("DOMContentLoaded", () => {
  let mm = gsap.matchMedia();

  mm.add("(min-width: 1024px)", () => {
    // ========= DESKTOP-ONLY ANIMATIONS ==========

    // ---------- CONFIGURABLE DOT ANIMATIONS ----------
    const dotAnimations = [
      {
        selector: ".img-bottom-left-dot",
        keyframes: [
          { top: "-8.7rem", left: "3.9rem" },
          { top: "-5.9rem", left: "5.1rem" },
          { top: "-1.5rem", left: "4.6rem" },
          { top: "4.4rem", left: "5.5rem" },
          { top: "7.35rem", left: "11.4rem" },
          { top: "9.5rem", left: "18rem" },
          { top: "9.5rem", left: "23.9rem" },
          { top: "19.3rem", left: "11.6rem" },
          { top: "13rem", left: "18rem" },
          { top: "24rem", left: "20rem" },
          { top: "42.3rem", left: "14rem" },
        ],
      },
      {
        selector: ".img-top-right-dot",
        keyframes: [
          { top: "12.8rem", right: "11.3rem" },
          { top: "15.9rem", right: "7.6rem" },
          { top: "20.9rem", right: "13.5rem" },
          { top: "11.2rem", right: "18.5rem" },
          { top: "24.9rem", right: "8.9rem" },
          { top: "22.1rem", right: "8.9rem" },
          { top: "30.6rem", right: "11.7rem" },
          { top: "15.3rem", right: "4.4rem" },
          { top: "7.4rem", right: "9.3rem" },
          { top: "19.1rem", right: "0.02rem" },
          { top: "9.3rem", right: "20.07rem" },
        ],
      },
    ];

    dotAnimations.forEach((dot) => {
      gsap.to(dot.selector, {
        keyframes: dot.keyframes,
        duration: 10,
        ease: "none",
        repeat: -1,
      });
    });

    // ---------- GRADIENT CIRCLE ROTATION ----------
    gsap.to(".gradient-circle", {
      keyframes: [
        { rotate: "0deg", opacity: 1, percent: 0 },
        { opacity: 0.25, percent: 50 },
        { rotate: "360deg", opacity: 1, percent: 100 },
      ],
      duration: 10,
      ease: "none",
      repeat: -1,
    });

    // ---------- REVIEWS SECTION ANIMATION ----------
    let reviewsTl = gsap.timeline({
      scrollTrigger: {
        trigger: "section.reviews",
        start: "top top",
        end: "bottom top",
        scrub: 3,
        markers: false,
      },
    });

    reviewsTl.to(".reviews-gradient-arc-img", {
      rotation: 360,
      ease: "none",
    });

    reviewsTl.fromTo(
      ".reviews-gradient-circle-img",
      { x: "10rem", y: "10rem" },
      { x: "-8rem", y: "-160%", ease: "none" }
    );

    // Cleanup
    return () => {
      console.log("Desktop animations removed");
    };
  });
});
