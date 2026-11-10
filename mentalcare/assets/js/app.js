/**
 * MentalCare - Client-side Interactive Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle for Public Navbar
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
        });
    }

    // 2. Dashboard Sidebar Toggle on Mobile
    const sidebarToggleBtn = document.querySelector('.sidebar-toggle-btn');
    const appSidebar = document.querySelector('.app-sidebar');
    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            appSidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992) {
                if (!appSidebar.contains(e.target) && !sidebarToggleBtn.contains(e.target)) {
                    appSidebar.classList.remove('open');
                }
            }
        });
    }

    // 3. Interactive Kuisioner Progress Updater
    const kuisionerForm = document.querySelector('#kuisionerForm');
    if (kuisionerForm) {
        const questionBoxes = document.querySelectorAll('.question-box');
        const progressBarFill = document.querySelector('.progress-bar-fill');
        const progressPercentText = document.querySelector('#progressPercentText');
        const totalQuestions = questionBoxes.length;

        function updateProgress() {
            let answeredCount = 0;
            questionBoxes.forEach((box) => {
                const inputs = box.querySelectorAll('input[type="radio"]');
                let isAnswered = false;
                inputs.forEach((input) => {
                    if (input.checked) isAnswered = true;
                });
                if (isAnswered) {
                    box.classList.add('answered');
                    answeredCount++;
                } else {
                    box.classList.remove('answered');
                }
            });

            const percent = totalQuestions > 0 ? Math.round((answeredCount / totalQuestions) * 100) : 0;
            if (progressBarFill) progressBarFill.style.width = percent + '%';
            if (progressPercentText) progressPercentText.textContent = `${answeredCount} dari ${totalQuestions} terjawab (${percent}%)`;
        }

        kuisionerForm.addEventListener('change', (e) => {
            if (e.target.matches('input[type="radio"]')) {
                updateProgress();
            }
        });

        updateProgress();
    }

    // 4. Auto-dismiss alerts after 6 seconds (optional)
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 7000);
    });
});
