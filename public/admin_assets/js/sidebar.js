(function () {
  const body = document.querySelector("body");
  const wrapper = document.querySelector(".page-wrapper");

  const sidebarListItems = document.querySelectorAll(".sidebar-link");
  sidebarListItems.forEach((item) => {
    item.addEventListener("click", () => {
      item.classList.toggle("active");
      const submenu = item.closest(".sidebar-list")?.querySelector(".sidebar-submenu");
      if (submenu) {
        submenu.style.display = item.classList.contains("active") ? "block" : "none";
      }
      sidebarListItems.forEach((otherList) => {
        if (otherList !== item) {
          otherList.classList.remove("active");
          const otherSubmenu = otherList.closest(".sidebar-list")?.querySelector(".sidebar-submenu");
          if (otherSubmenu) {
            otherSubmenu.style.display = "none";
          }
        }
      });
    });
  });

  const sidebarToggle = document.querySelector(".toggle-sidebar");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
      wrapper?.classList.toggle("sidebar-open");
    });
  }
})();

const pinTitle = document.querySelector(".pin-title");
let pinIcon = document.querySelectorAll(".sidebar-list .fa-thumbtack");
function togglePinnedName() {
  if (!pinTitle) return;
  if (document.getElementsByClassName("pined").length) {
    if (!pinTitle.classList.contains("show")) pinTitle.classList.add("show");
  } else {
    pinTitle.classList.remove("show");
  }
}
pinIcon.forEach((item) => {
  const linkName = item.parentNode.querySelector("h6")?.innerHTML;
  let InitialLocalStorage = JSON.parse(localStorage.getItem("pins") || "[]");
  if (InitialLocalStorage.includes(linkName)) {
    item.parentNode.classList.add("pined");
  }
  item.addEventListener("click", () => {
    let localStoragePins = JSON.parse(localStorage.getItem("pins") || "[]");
    item.parentNode.classList.toggle("pined");
    if (item.parentNode.classList.contains("pined")) {
      if (!localStoragePins.includes(linkName)) localStoragePins.push(linkName);
    } else {
      localStoragePins = localStoragePins.filter((name) => name !== linkName);
    }
    localStorage.setItem("pins", JSON.stringify(localStoragePins));
    togglePinnedName();
  });
});
togglePinnedName();

const submenuTitles = document.querySelectorAll(".submenu-title");
submenuTitles.forEach((title) => {
  title.addEventListener("click", () => {
    const parentLi = title.closest("li");
    parentLi.classList.toggle("active");
    const submenu = parentLi.querySelector(".according-submenu");
    if (submenu) {
      submenu.style.display = submenu.style.display === "block" ? "none" : "block";
    }
    submenuTitles.forEach((otherTitle) => {
      if (otherTitle !== title) {
        const otherParentLi = otherTitle.closest("li");
        otherParentLi.classList.remove("active");
        const otherSubmenu = otherParentLi.querySelector(".according-submenu");
        if (otherSubmenu) {
          otherSubmenu.style.display = "none";
        }
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const pageWrapper = document.querySelector(".page-wrapper");
  const toggleSidebarButton = document.querySelector(".toggle-sidebar");
  if (!pageWrapper || !toggleSidebarButton) return;

  if (window.innerWidth <= 1199) {
    pageWrapper.classList.add("sidebar-open");
    toggleSidebarButton.classList.add("close");
  }
  window.addEventListener("resize", function () {
    if (window.innerWidth <= 1199) {
      pageWrapper.classList.add("sidebar-open");
      toggleSidebarButton.classList.add("close");
    } else {
      pageWrapper.classList.remove("sidebar-open");
      toggleSidebarButton.classList.remove("close");
    }
  });
});
