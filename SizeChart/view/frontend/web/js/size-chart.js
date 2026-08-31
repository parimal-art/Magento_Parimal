document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('size-chart-modal');
    const openBtn = document.getElementById('open-size-chart-btn');
    const closeBtn = document.getElementById('close-size-chart-btn');

    if (openBtn && modal && closeBtn) {
        openBtn.addEventListener('click', function (event) {
            event.preventDefault();
            modal.style.display = 'block';
        });

        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});
