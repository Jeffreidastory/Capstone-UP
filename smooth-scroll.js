document.addEventListener('DOMContentLoaded', () => {
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');
    const arrowUp = document.getElementById('arrowUp');

    // Show/hide the scroll-to-top button based on scroll position
    window.addEventListener('scroll', () => {
        const shouldShow = window.scrollY > 100;
        scrollToTopBtn.style.display = shouldShow ? 'block' : 'none';
        arrowUp.style.display = shouldShow ? 'block' : 'none';
    });

    // Scroll to the top when the scroll-to-top button is clicked
    scrollToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    });

    // Scroll to the top or #home section when the arrow is clicked
    arrowUp.addEventListener('click', () => {
        if (window.scrollY > 0) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        } else {
            document.getElementById('home')?.scrollIntoView({
                behavior: 'smooth',
            });
        }
    });

    // Smooth scrolling for navbar links
    document.querySelectorAll('.navbar a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();

            const targetId = anchor.getAttribute('href').slice(1); // Remove the '#' symbol
            const target = document.getElementById(targetId);

            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start', // Align to the top of the viewport
                });
            }
        });
    });
});
