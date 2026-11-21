</main>
</div>
<script src="js/searchable.js"></script>
<script>
    // Switch theme on click and remember the choice
    document.getElementById("theme-toggle").addEventListener("click", function () {
        var root = document.documentElement;
        var next = root.dataset.theme === "dark" ? "light" : "dark";
        root.dataset.theme = next;
        try { localStorage.setItem("theme", next); } catch (e) {}
    });
</script>
</body>
</html>