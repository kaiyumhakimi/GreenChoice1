/*
* GreenChoice Custom Scripts (FIXED CLEAN VERSION)
*/

window.addEventListener("DOMContentLoaded", function () {

    // ==========================
    // NAVBAR SCROLL EFFECT (ONLY ONCE)
    // ==========================
    const nav = document.getElementById("mainNav");

    window.addEventListener("scroll", function () {
        if (!nav) return;

        if (window.scrollY > 20) {
            nav.classList.add("scrolled");
        } else {
            nav.classList.remove("scrolled");
        }
    });

    // ==========================
    // DARK MODE LOAD
    // ==========================
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark-mode");
    }

});


// ==========================
// SIDEBAR
// ==========================
function openNav() {
    const sidebar = document.getElementById("mySidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (sidebar) sidebar.style.width = "380px";
    if (overlay) overlay.style.display = "block";
}

function closeNav() {
    const sidebar = document.getElementById("mySidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (sidebar) sidebar.style.width = "0";
    if (overlay) overlay.style.display = "none";
}


// ==========================
// COMPARE SYSTEM
// ==========================
let selectedProducts = [];

function handleCompare(checkbox) {

    const bar = document.getElementById("compareBar");
    const count = document.getElementById("compareCount");

    if (!bar || !count) return;

    if (checkbox.checked) {
        selectedProducts.push(checkbox.value);
    } else {
        selectedProducts = selectedProducts.filter(item => item !== checkbox.value);
    }

    count.innerText = selectedProducts.length;

    if (selectedProducts.length > 0) {
        bar.classList.remove("d-none");
    } else {
        bar.classList.add("d-none");
    }
}

function clearCompare() {

    document.querySelectorAll(".compare-checkbox").forEach(cb => {
        cb.checked = false;
    });

    selectedProducts = [];

    const bar = document.getElementById("compareBar");
    const count = document.getElementById("compareCount");

    if (bar) bar.classList.add("d-none");
    if (count) count.innerText = "0";
}