// ==========================================
// RELÓGIO DE GUERRA TÁTICO
// ==========================================
function iniciarRelogio() {
    // 1. Busca os elementos no DOM apenas UMA vez
    const elHours = document.getElementById('warHours');
    const elMinutes = document.getElementById('warMinutes');
    const elSeconds = document.getElementById('warSeconds');
    const dateElement = document.getElementById('warClockDate');
    
    // Se não achar o relógio na página, aborta a missão
    if (!elHours || !elMinutes || !elSeconds || !dateElement) return;

    let lastHours = "", lastMinutes = "", lastSeconds = "";
    const meses = ["JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];

    // 2. Define a animação FORA do loop para não recriá-la a cada segundo
    const flipAnimation = [
        { transform: 'perspective(200px) rotateX(0deg)', opacity: 1 },
        { transform: 'perspective(200px) rotateX(90deg)', opacity: 0.5, offset: 0.5 },
        { transform: 'perspective(200px) rotateX(0deg)', opacity: 1 }
    ];
    const animOptions = { duration: 300, easing: 'ease-in-out' };

    const updateClock = () => {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        
        // 3. Executa as animações
        if (h !== lastHours) { elHours.textContent = h; elHours.animate(flipAnimation, animOptions); lastHours = h; }
        if (m !== lastMinutes) { elMinutes.textContent = m; elMinutes.animate(flipAnimation, animOptions); lastMinutes = m; }
        if (s !== lastSeconds) { elSeconds.textContent = s; elSeconds.animate(flipAnimation, animOptions); lastSeconds = s; }

        const day = String(now.getDate()).padStart(2, '0');
        dateElement.textContent = `${day} ${meses[now.getMonth()]} ${now.getFullYear()}`;
    };

    updateClock();
    setInterval(updateClock, 1000);
}

// O script se liga sozinho ao carregar a página
document.addEventListener('DOMContentLoaded', iniciarRelogio);