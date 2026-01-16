document.addEventListener("DOMContentLoaded", () => {

    // Parallax hover on card
    const card = document.querySelector(".profile-card");
    if (card) {
        card.addEventListener("mousemove", e => {
            const rect = card.getBoundingClientRect();
            let x = (e.clientX - rect.left) / rect.width - 0.5;
            let y = (e.clientY - rect.top) / rect.height - 0.5;

            card.style.transform = `rotateX(${y * 4}deg) rotateY(${x * -4}deg)`;
        });

        card.addEventListener("mouseleave", () => {
            card.style.transform = "rotateX(0) rotateY(0)";
        });
    }

});
