// ==========================================
// ROLAGEM SUAVE E BOTÃO "VOLTAR AO TOPO"
// ==========================================
function iniciarScrollSuave(caminhoBase) {
    // 1. Injeta apenas a estrutura HTML do botão
    const scrollBtnHTML = `
        <button id="btnVoltarTopo" title="Voltar ao Topo">
            <img src="${caminhoBase}WT - Artes/UI-UX icons/Seta_cima-icon.png" alt="Voltar ao Topo" class="icone-topo">
            <span class="texto-topo">VOLTAR AO TOPO</span>
        </button>
    `;
    document.body.insertAdjacentHTML('beforeend', scrollBtnHTML);

    const btnTopo = document.getElementById('btnVoltarTopo');
    if (!btnTopo) return;

    // 2. Lógica de Mostrar/Esconder baseada no scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btnTopo.classList.add('visivel');
        } else {
            btnTopo.classList.remove('visivel');
        }
    });

    // 3. Evento exigido academicamente (scrollIntoView)
    btnTopo.addEventListener('click', () => {
        document.body.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });

    // 4. Aplica a rolagem suave a qualquer link âncora da página
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const destino = this.getAttribute('href');
            
            if (destino !== "#") {
                const elementoDestino = document.querySelector(destino);
                
                if (elementoDestino) {
                    e.preventDefault();
                    elementoDestino.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
}