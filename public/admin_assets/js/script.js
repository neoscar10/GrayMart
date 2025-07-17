document.addEventListener("DOMContentLoaded", function () {
  const body = document.querySelector("body");
  const html = document.querySelector("html");

  $(".loader-wrapper").fadeOut("slow", function () {
    $(this).remove();
  });

  const button = document.querySelector(".tap-top");
  if (button) {
    window.addEventListener("scroll", () => {
      button.style.display = window.scrollY > 100 ? "block" : "none";
    });
    button.addEventListener("click", () => {
      window.scroll({ top: 0, left: 0, behavior: "smooth" });
    });
  }

  if (body) {
    body.addEventListener("click", function (event) {
      const headerDropdownMenu = document.querySelectorAll(".custom-menu");
      const dropdownEl = event.target.closest(".custom-dropdown");
      const dropdownMenuElement = event.target.closest(".custom-menu");

      if (!dropdownMenuElement) {
        headerDropdownMenu.forEach((item) => item.classList.remove("show"));
      }

      if (dropdownEl) {
        const dropdownMenu = dropdownEl.querySelector(".custom-menu");
        if (dropdownMenu && !dropdownMenu.classList.contains("show")) {
          dropdownMenu.classList.add("show");
        }
      }
    });
  }

  $(document).ready(function () {
    $(".full-screen").click(function () {
      const elem = document.documentElement;
      if (
        (document.fullScreenElement && document.fullScreenElement !== null) ||
        (!document.mozFullScreen && !document.webkitIsFullScreen)
      ) {
        if (elem.requestFullScreen) {
          elem.requestFullScreen();
        } else if (elem.mozRequestFullScreen) {
          elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullScreen) {
          elem.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
        }
      } else {
        if (document.cancelFullScreen) {
          document.cancelFullScreen();
        } else if (document.mozCancelFullScreen) {
          document.mozCancelFullScreen();
        } else if (document.webkitCancelFullScreen) {
          document.webkitCancelFullScreen();
        }
      }
    });
  });

  const filterSidebarToggle = document.querySelector(".md-sidebar-toggle");
  const filterSidebarAside = document.querySelector(".md-sidebar-aside");
  if (filterSidebarToggle && filterSidebarAside) {
    filterSidebarToggle.addEventListener("click", function () {
      filterSidebarAside.classList.toggle("open");
    });
  }

  $(".search").click(function () {
    $(".search-full").addClass("open");
  });
  $(".close-search").click(function () {
    $(".search-full").removeClass("open");
    $("body").removeClass("offcanvas");
  });

  if (window.location.pathname.includes("layout-dark.html")) {
    $("body").removeClass("light").addClass("dark-only");
  } else {
    $(".dark-mode").on("click", function () {
      const bodyModeDark = $("body").hasClass("dark-only");
      if (!bodyModeDark) {
        $(".dark-mode").addClass("active");
        localStorage.setItem("mode", "dark-only");
        $("body").addClass("dark-only").removeClass("light");
      } else {
        $(".dark-mode").removeClass("active");
        localStorage.setItem("mode", "light");
        $("body").removeClass("dark-only").addClass("light");
      }
    });
    $("body").addClass(localStorage.getItem("mode") || "light");
    if (localStorage.getItem("mode") === "dark-only") {
      $(".dark-mode").addClass("active");
    }
  }

  const toggleDataElements = document.querySelectorAll(".toggle-data");
  toggleDataElements.forEach((element) => {
    element.addEventListener("click", function () {
      document.querySelectorAll(".product-wrapper").forEach((wrapper) => {
        wrapper.classList.toggle("sidebaron");
      });
    });
  });

  $(".prooduct-details-box .close").on("click", function () {
    $(this).parentsUntil(".prooduct-details-box").parent().addClass("d-none");
  });

  $(".bg-center").parent().addClass("b-center");
  $(".bg-img-cover").parent().addClass("bg-size");
  $(".bg-img-cover").each(function () {
    var el = $(this),
      src = el.attr("src"),
      parent = el.parent();
    parent.css({
      "background-image": "url(" + src + ")",
      "background-size": "cover",
      "background-position": "center",
      display: "block",
    });
    el.hide();
  });

  var tnum = "en";
  $(document).ready(function () {
    if (localStorage.getItem("primary")) {
      $("#ColorPicker1").val(localStorage.getItem("primary"));
      $("#ColorPicker2").val(localStorage.getItem("secondary"));
    }

    $(document).click(function () {
      $(".translate_wrapper, .more_lang").removeClass("active");
    });

    $(".translate_wrapper .current_lang").click(function (e) {
      e.stopPropagation();
      $(this).parent().toggleClass("active");
      setTimeout(function () {
        $(".more_lang").toggleClass("active");
      }, 5);
    });

    translate(tnum);

    $(".more_lang .lang").click(function () {
      $(this).addClass("selected").siblings().removeClass("selected");
      $(".more_lang").removeClass("active");
      tnum = $(this).attr("data-value");
      translate(tnum);
      $(".current_lang .lang-txt").text(tnum);
      $(".current_lang i").attr("class", $(this).find("i").attr("class"));
    });
  });

  function translate(tnum) {
    $(".lan-1").text(trans[0][tnum]);
    $(".lan-2").text(trans[1][tnum]);
    $(".lan-3").text(trans[2][tnum]);
  }

  var trans = [
    { en: "General", es: "Paneloj", fr: "Générale" },
    { en: "Widgets", es: "Vidin", fr: "widgets" },
    { en: "Page layout", es: "Paneloj", fr: "Tableaux" },
  ];
});
