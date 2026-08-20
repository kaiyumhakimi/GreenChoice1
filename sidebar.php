<div id="sidebarOverlay" onclick="closeNav()"></div>

<div id="mySidebar" class="sidebar bg-white"
style="background:#ffffff;
transition:0.3s;
border-right:1px solid rgba(0,0,0,0.08);
box-shadow:0 0 2rem rgba(0,0,0,0.05);
display:flex;
flex-direction:column;
height:100vh;
overflow:hidden;">

    <!-- Close Button -->
    <div class="text-end p-3 pb-0">
        <button class="closebtn btn btn-link text-secondary text-decoration-none fs-3 p-0 line-height-1"
                onclick="closeNav()"
                style="transition:0.2s;color:#6c757d !important;">
            &times;
        </button>
    </div>

    <!-- Header -->
    <div class="sidebar-header-box px-4 py-3 text-center border-bottom"
         style="border-color:rgba(0,0,0,0.06)!important;">

        <div class="mb-2 d-inline-block p-2 rounded-circle shadow-sm"
             style="background:#e8f5e9;">
            <i class="fas fa-leaf text-success fs-4 d-block"
               style="color:#2e7d32!important;"></i>
        </div>

        <h4 class="fw-bold mb-1" style="color:#1b5e20;">
            GreenChoice
        </h4>

        <small class="text-uppercase small"
               style="letter-spacing:1px;color:#757575;">
            Sustainable Tech Guide
        </small>

    </div>

    <!-- Navigation -->
    <div class="px-3 py-3 d-flex flex-column justify-content-evenly flex-grow-1">

        <a href="index.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-home me-3 fs-5 text-success"></i>
            Home
        </a>

        <a href="search.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-search me-3 fs-5 text-success"></i>
            Search
        </a>

        <a href="category.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-th-large me-3 fs-5 text-success"></i>
            Category
        </a>

        <a href="bookmarks.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-bookmark me-3 fs-5 text-success"></i>
            Bookmarks
        </a>

        <a href="profile.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-recycle me-3 fs-5 text-success"></i>
            E-Waste Recycling
        </a>

        <a href="eco_awareness.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-newspaper me-3 fs-5 text-success"></i>
            Tech News
        </a>

        <a href="about.php"
           class="sidebar-btn nav-link py-2 px-3 rounded-pill d-flex align-items-center fw-bold transition-all">
            <i class="fas fa-info-circle me-3 fs-5 text-success"></i>
            About
        </a>

    </div>

    <!-- Footer -->
    <div class="px-4 pb-3 pt-2 text-center">

        <a href="login.php"
           class="sidebar-btn btn w-100 py-3 rounded-pill fw-bold text-white border-0 shadow-sm d-flex align-items-center justify-content-center gap-2 mx-auto"
           style="background:linear-gradient(135deg,#2e7d32 0%,#1b5e20 100%);
                  letter-spacing:.5px;
                  max-width:85%;">

            <i class="fas fa-user-circle fs-5 text-white"></i>
            Sign In

        </a>

    </div>

</div>

<script>
function openNav() {
    document.getElementById("mySidebar").style.width = "280px";
    document.getElementById("sidebarOverlay").style.display = "block";
    document.body.style.overflow = "hidden";
}

function closeNav() {
    document.getElementById("mySidebar").style.width = "0";
    document.getElementById("sidebarOverlay").style.display = "none";
    document.body.style.overflow = "auto";
}
</script>

<style>
#mySidebar .nav-link{
    color:#424242 !important;
    letter-spacing:.5px;
    transition:all .25s ease;
}

#mySidebar .sidebar-btn:hover{
    background:#e8f5e9 !important;
    color:#1b5e20 !important;
    padding-left:1.5rem !important;
}

#mySidebar .sidebar-btn:hover i{
    color:#2e7d32 !important;
}

#mySidebar .btn:hover{
    transform:translateY(-2px);
    box-shadow:0 .6rem 1.2rem rgba(46,125,50,.25)!important;
}

.line-height-1{
    line-height:1;
}

.transition-all{
    transition:all .25s ease;
}
</style>