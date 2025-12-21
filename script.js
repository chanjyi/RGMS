console.log("script.js loaded ✅");

function toggleSidebar() {
    // 1. Get the elements
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");

    // 2. Toggle the class
    sidebar.classList.toggle("open");

    // 3. Handle the Overlay
    if (overlay) {
        if (sidebar.classList.contains("open")) {
            overlay.style.display = "block";
        } else {
            overlay.style.display = "none";
        }
    } else {
        console.warn("Overlay element not found! Check sidebar.php");
    }
}

// Ensure clicking the overlay closes the menu
const overlay = document.getElementById("overlay");
if (overlay) {
    overlay.addEventListener("click", toggleSidebar);
}


/* =========================================
   YOUR EXISTING LOGIN/POPUP LOGIC
   (Kept this here so you don't lose it)
   ========================================= */
const wrapper = document.querySelector('.wrapper');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const btnPopups = document.querySelectorAll('.btnLogin-popup'); 
const iconClose = document.querySelector('.icon-close');

if (registerLink && wrapper) {
    registerLink.addEventListener('click', (e) => {
        e.preventDefault();
        wrapper.classList.add('active');
    });
}

if (loginLink && wrapper) {
    loginLink.addEventListener('click', (e) => {
        e.preventDefault();
        wrapper.classList.remove('active');
    });
}

if (btnPopups) {
    btnPopups.forEach(btn => {
        btn.addEventListener('click', () => {
            if(wrapper) {
                wrapper.classList.add('active-popup');
                wrapper.classList.remove('active');
            }
        });
    });
}

if (iconClose && wrapper) {
    iconClose.addEventListener('click', () => {
        wrapper.classList.remove('active-popup');
        wrapper.classList.remove('active');
    });
}