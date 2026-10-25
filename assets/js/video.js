document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".vide-thumb").forEach(function (thumb) {

    thumb.addEventListener("click", function () {
      const videoUrl = this.dataset.videoUrl;
      const wrapper = this.closest(".video-wrapper");
      const iframe = wrapper.querySelector("iframe");
      const videoBox = wrapper.querySelector(".video-box");

      iframe.src = videoUrl;
      videoBox.style.display = "block";
      this.style.display = "none";
    });

  });
});
