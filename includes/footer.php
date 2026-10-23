<?php

declare(strict_types=1);
?>

</div>

<script>
    const sidebarToggle = document.querySelector(
        '[data-sidebar-toggle]'
    );

    const sidebar = document.querySelector(
        '.admin-sidebar'
    );

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('admin-sidebar-open');
        });
    }

    document.addEventListener('click', function (event) {
        if (
            window.innerWidth <= 900 &&
            sidebar &&
            sidebar.classList.contains('admin-sidebar-open') &&
            !sidebar.contains(event.target) &&
            !sidebarToggle.contains(event.target)
        ) {
            sidebar.classList.remove('admin-sidebar-open');
        }
    });
</script>

</body>
</html>