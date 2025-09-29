document.addEventListener("DOMContentLoaded", function() {
    const Userbtn = document.getElementById("Userbtn");

    if (Userbtn) {
        Userbtn.addEventListener("click", function() {
            window.location.href = 'loginpage.php';
        });
    }
});