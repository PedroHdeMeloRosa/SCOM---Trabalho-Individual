// ==========================================
// INJEÇÃO DA INTERFACE GLOBAL (NavBar e Modais)
// ==========================================
function injetarInterface(caminhoBase) {
    const menuHTML = `
    <nav class="top-navbar">
        <div class="nav-left">
            <a href="${caminhoBase}https://wiki.warthunder.com/ground" class="logo-container-nav">
                <div class="logo-plate-nav">
                    <img src="${caminhoBase}WT - Artes/War-Thunder-logo.png" alt="Logo do War Thunder" class="navbar-logo-img">
                </div>
            </a>
            
            <!-- CAIXA DE PESQUISA -->
            <div class="search-container">
                <img src="${caminhoBase}WT - Artes/UI-UX icons/Search_icon.png" class="search-icon" alt="Lupa">
                <input type="text" id="searchInput" placeholder="Buscar blindado..." autocomplete="off">
                <ul class="search-suggestions" id="searchSuggestions"></ul>
            </div>
        </div>
        
        <div class="nav-center">
            <div style="font-size: 12px; font-weight: bold; color: #F2B400; letter-spacing: 2px; margin-bottom: 2px;">RELÓGIO DE GUERRA</div>
            <div class="clock-time"><span id="warHours" class="flip-digit">00</span>:<span id="warMinutes" class="flip-digit">00</span>:<span id="warSeconds" class="flip-digit">00</span> <span style="font-size: 12px; color: #a0a0a0; text-shadow: none;">BRT</span></div>
            <div class="clock-date" id="warClockDate">00/00/0000</div>
        </div>
        
        <div class="nav-right">
            <a href="${caminhoBase}index.html" class="nav-item">
                <div class="icon-box"><img src="${caminhoBase}WT - Artes/UI-UX icons/HOME_icon.png" alt="Início" class="nav-icon-img"></div>
                <div class="text-box">INÍCIO</div>
            </a>
            <a href="#" class="nav-item">
                <div class="icon-box"><img src="${caminhoBase}WT - Artes/UI-UX icons/Config_icon.jpg" alt="Configurações" class="nav-icon-img"></div>
                <div class="text-box">CONFIG.</div>
            </a>
            <a href="#" class="nav-item">
                <div class="icon-box"><img src="${caminhoBase}WT - Artes/UI-UX icons/Register_icon.jpg" alt="Registro" class="nav-icon-img"></div>
                <div class="text-box">REGISTRO</div>
            </a>
            <a href="#" class="nav-item btn-login">
                <div class="icon-box"><img src="${caminhoBase}WT - Artes/UI-UX icons/LOGIN-icon.jpg" alt="Login" class="nav-icon-img"></div>
                <div class="text-box" id="navLoginText">ENTRAR</div>
            </a>
        </div>
    </nav>

    <!-- MODAL DE ACESSO -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal-box">
            <button class="fechar-modal" id="closeModal">X</button>
            <h2 class="modal-title">ACESSO RESTRITO</h2>
            <p class="modal-subtitle">Identificação de Comandante</p>
            <form id="loginForm">
                <div class="input-group">
                    <label for="userEmail">CÓDIGO DE ACESSO (E-mail)</label>
                    <input type="email" id="userEmail" placeholder="comandante@gmail.com" required>
                </div>
                <div class="input-group">
                    <label for="userPassword">CHAVE CRIPTOGRÁFICA (Senha)</label>
                    <div class="password-wrapper">
                        <input type="password" id="userPassword" placeholder="Insira a chave exata" required>
                        <button type="button" id="btnRadar" class="radar-toggle" title="Alternar Senha">
                            <img src="${caminhoBase}WT - Artes/UI-UX icons/Radar-ocultar.png" id="radarIcon" style="width: 24px; height: auto;">
                        </button>
                    </div>
                </div>
                <button type="submit" id="btnSubmitLogin" class="btn-submit-militar" disabled>AUTENTICAR</button>
            </form>
        </div>
    </div>

    <!-- BANNER -->
    <div id="customBanner" class="tactical-banner">
        <span class="material-symbols-outlined" id="bannerIcon">military_tech</span>
        <span id="customBannerText">Notificação</span>
    </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', menuHTML);
}

document.addEventListener('click', (e) => {
    const trigger = document.getElementById('navToggleBtn');
    const navbar = document.querySelector('.top-navbar');
    
    if (trigger && navbar) {
        if (trigger.contains(e.target)) {
            navbar.classList.toggle('expanded');
        } else if (!navbar.contains(e.target)) {
            navbar.classList.remove('expanded'); // Fecha se clicar fora
        }
    }
});

// ==========================================
// FUNÇÃO DO BOTÃO DE MODO DE EXIBIÇÃO
// ==========================================
function iniciarToggleVisualizacao() {
    const toggleBtn = document.getElementById('btnToggleView');
    const container = document.querySelector('.nation-content');
    
    // Trava de Segurança
    if (!toggleBtn || !container) return; 

    toggleBtn.addEventListener('click', () => {
        container.classList.toggle('list-view');
        
        // Troca o visual do botão dependendo do modo ativo
        if (container.classList.contains('list-view')) {
            toggleBtn.innerHTML = '&#9638; MODO CAIXAS';
        } else {
            toggleBtn.innerHTML = '&#9776; MODO LISTA';
        }
    });
}