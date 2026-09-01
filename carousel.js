function iniciarCarrossel() {
    // Procura todos os carrosséis da página que devem rodar automaticamente
    const trilhos = document.querySelectorAll('.auto-slide');

    trilhos.forEach(track => {
        const slides = track.querySelectorAll('.slide-item');
        if (slides.length <= 1) return; // Se tiver só 1 imagem, não precisa girar

        let indexAtual = 0;

        // Intervalo assíncrono: Gira o carrossel a cada 3.5 segundos
        setInterval(() => {
            indexAtual++;
            
            // Se chegou na última imagem, zera o contador para voltar à primeira
            if (indexAtual >= slides.length) {
                indexAtual = 0;
            }
            
            // Move o trilho para a esquerda (ex: -100%, -200%, -300%)
            track.style.transform = `translateX(-${indexAtual * 100}%)`;
        }, 3500);
    });
}