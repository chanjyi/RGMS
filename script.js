console.log("script.js loaded ✅");
console.log("open btn:", document.getElementById("openSidebarBtn"));
console.log("sidebar:", document.getElementById("sidebar"));

const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const btnPopups = document.querySelectorAll('.btnLogin-popup'); // all login buttons
const iconClose = document.querySelector('.icon-close');

// Switch to Register form
if (registerLink) {
    registerLink.addEventListener('click', (e) => {
        e.preventDefault();
        wrapper.classList.add('active');
    });
}

// Switch back to Login form
if (loginLink) {
    loginLink.addEventListener('click', (e) => {
        e.preventDefault();
        wrapper.classList.remove('active');
    });
}

// Open popup from ANY login/order button
btnPopups.forEach(btn => {
    btn.addEventListener('click', () => {
        wrapper.classList.add('active-popup');
        // always show login tab first when opening
        wrapper.classList.remove('active');
    });
});

// Close popup
if (iconClose) {
    iconClose.addEventListener('click', () => {
        wrapper.classList.remove('active-popup');
        wrapper.classList.remove('active');
    });
}

function openSidebar() {
  document.getElementById("sidebar")?.classList.add("open");
  const overlay = document.getElementById("overlay");
  if (overlay) overlay.style.display = "block";
}


function closeSidebar() {
  document.getElementById("sidebar")?.classList.remove("open");
  const overlay = document.getElementById("overlay");
  if (overlay) overlay.style.display = "none";
}


document.getElementById("openSidebarBtn")?.addEventListener("click", openSidebar);
document.getElementById("closeSidebarBtn")?.addEventListener("click", closeSidebar);
document.getElementById("overlay")?.addEventListener("click", closeSidebar);
