<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bookmark Function -->
<script>
function toggleBookmark(name, link) {

    let bookmarks = JSON.parse(localStorage.getItem("bookmarks")) || [];

    let index = bookmarks.findIndex(b => b.link === link);

    if (index > -1) {
        bookmarks.splice(index, 1);
        alert("Removed from bookmarks");
    } else {
        bookmarks.push({ name, link });
        alert("Added to bookmarks");
    }

    localStorage.setItem("bookmarks", JSON.stringify(bookmarks));
}
</script>

<!-- NAVBAR SCROLL (ONLY ONE VERSION - FIXED) -->
<script>
window.addEventListener("DOMContentLoaded", function () {

    const nav = document.getElementById("mainNav");

    if (!nav) return;

    window.addEventListener("scroll", function () {

        if (window.scrollY > 20) {
            nav.classList.add("scrolled");
        } else {
            nav.classList.remove("scrolled");
        }

    });

});
</script>

<!-- Main JS -->
<script src="js/scripts.js"></script>

</body>
</html>