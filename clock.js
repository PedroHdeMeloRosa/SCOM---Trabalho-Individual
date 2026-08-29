function iniciarRelogio() {
    let lastHours = "", lastMinutes = "", lastSeconds = "";
    
    const updateClock = () => {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const meses = ["JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];
        const month = meses[now.getMonth()];
        const year = now.getFullYear();

        const elHours = document.getElementById('warHours');
        const elMinutes = document.getElementById('warMinutes');
        const elSeconds = document.getElementById('warSeconds');
        const dateElement = document.getElementById('warClockDate');
        
        if (elHours && elMinutes && elSeconds && dateElement) {
            const flipAnimation = [
                { transform: 'perspective(200px) rotateX(0deg)', opacity: 1 },
                { transform: 'perspective(200px) rotateX(90deg)', opacity: 0.5, offset: 0.5 },
                { transform: 'perspective(200px) rotateX(0deg)', opacity: 1 }
            ];
            const animOptions = { duration: 300, easing: 'ease-in-out' };

            if (h !== lastHours) { elHours.textContent = h; elHours.animate(flipAnimation, animOptions); lastHours = h; }
            if (m !== lastMinutes) { elMinutes.textContent = m; elMinutes.animate(flipAnimation, animOptions); lastMinutes = m; }
            if (s !== lastSeconds) { elSeconds.textContent = s; elSeconds.animate(flipAnimation, animOptions); lastSeconds = s; }

            dateElement.textContent = `${day} ${month} ${year}`;
        }
    };
    updateClock();
    setInterval(updateClock, 1000);
}