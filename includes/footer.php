</main>
</div>
<script>
const menus = document.querySelectorAll('aside nav a');
menus.forEach(a => { if (location.pathname.endsWith(a.getAttribute('href'))) a.classList.add('active'); });
</script>
</body>
</html>
