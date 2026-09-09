// ==========================================
// CARROSSEL AUTOMÁTICO
// ==========================================
function iniciarCarrossel() {
    const trilhos = document.querySelectorAll('.auto-slide');

    trilhos.forEach(track => {
        const slides = track.querySelectorAll('.slide-item');
        if (slides.length <= 1) return; // Ignora se não houver o que girar

        let indexAtual = 0;

        setInterval(() => {
            // Lógica Sênior: O módulo (%) zera o index automaticamente ao atingir o limite
            indexAtual = (indexAtual + 1) % slides.length;
            
            // Move o trilho
            track.style.transform = `translateX(-${indexAtual * 100}%)`;
        }, 3500);
    });
}

// O script se inicializa sozinho assim que o HTML termina de carregar
document.addEventListener('DOMContentLoaded', iniciarCarrossel);