function injetarInterface(caminhoBase) {
    const estiloMenu = document.createElement('style');
    estiloMenu.innerHTML = `
        /* ==================================================
           BARRA DE NAVEGAÇÃO
           ================================================== */
        .top-navbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 90px;
            background-color: var(--metal-cinza-escuro, #1a1a1a);
            border-bottom: 2px solid var(--metal-cinza-claro, #444);
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 40px; box-sizing: border-box; z-index: 10000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.8);
        }
        .nav-left, .nav-right { display: flex; align-items: center; }
        .nav-right { justify-content: space-between; gap: 50px; }

        .logo-container-nav { text-decoration: none; }
        .logo-plate-nav {
            background-color: var(--dourado-titulos); border: 2px solid var(--metal-cinza-claro, #736b6b);
            padding: 5px 15px; border-radius: 8px; box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5), 0 4px 6px rgba(0, 0, 0, 0.3);
            display: flex; align-items: center; justify-content: center;
            transition: border-color 0.3s; width: auto; height: 100%; max-height: 120px;
        }
        .logo-plate-nav:hover { border-color: var(--texto-branco, #ffffff); }
        .navbar-logo-img { height: 90px; width: auto; }

        .nav-item {
            display: flex; flex-direction: column; align-items: center; text-decoration: none;
            color: #a0a0a0; font-family: Arial, sans-serif; font-size: 11px; font-weight: bold;
            letter-spacing: 1px; transition: all 0.2s ease-in-out;
        }
        .icon-box { margin-bottom: 5px; display: flex; align-items: center; justify-content: center; height: 24px; }
        .nav-icon-img { width: auto; height: 50px; object-fit: contain; transition: filter 0.2s; padding-bottom: 10px; }
        .nav-item:hover { color: #ffffff; transform: translateY(-3px); }
        .nav-item:hover .nav-icon-img { filter: brightness(1.3) drop-shadow(0px 0px 5px rgba(255, 255, 255, 0.3)); }

        /* ==================================================
           RELÓGIO DE GUERRA (Centro)
           ================================================== */
        .nav-center {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-family: 'Courier New', Courier, monospace; text-align: center;
            color: #F2B400; text-shadow: 0 0 5px rgba(242, 180, 0, 0.7);
        }
        .flip-digit { display: inline-block; min-width: 28px; }
        .clock-time {
            color: lightgoldenrodyellow; font-size: 20px; font-weight: bold;
            letter-spacing: 3px; text-shadow: 0 0 8px rgba(63, 211, 47, 0.6);
        }
        .clock-date { color: goldenrod; font-size: 11px; letter-spacing: 2px; margin-top: 2px; }

        /* ==================================================
           MODAL DE LOGIN (MILITAR)
           ================================================== */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.8); display: flex; align-items: center; justify-content: center;
            z-index: 20000; opacity: 0; pointer-events: none; transition: opacity 0.3s ease;
        }
        .modal-overlay.ativo { opacity: 1; pointer-events: auto; }
        .modal-box {
            background-color: var(--metal-chumbo, #1E2021); border: 2px solid var(--rus-vermelho, #6E1111);
            padding: 40px; border-radius: 8px; width: 90%; max-width: 400px; position: relative;
            box-shadow: 0 0 30px rgba(110, 17, 17, 0.4); transform: scale(0.9); transition: transform 0.3s ease;
        }
        .modal-overlay.ativo .modal-box { transform: scale(1); }
        .fechar-modal {
            position: absolute; top: 15px; right: 20px; background: none; border: none;
            color: var(--metal-cinza-claro, #8a8d8f); font-size: 20px; font-weight: bold; cursor: pointer; transition: color 0.2s;
        }
        .fechar-modal:hover { color: #ffffff; }
        .modal-title { color: var(--dourado-titulos); text-align: center; font-size: 24px; letter-spacing: 2px; margin-bottom: 5px; }
        .modal-subtitle { color: #a0a0a0; text-align: center; font-size: 12px; margin-bottom: 30px; letter-spacing: 1px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; color: var(--metal-cinza-claro); font-size: 11px; font-weight: bold; margin-bottom: 8px; }
        .input-group input {
            width: 100%; padding: 12px; background-color: rgba(0,0,0,0.5); border: 1px solid var(--metal-cinza-claro);
            color: #fff; border-radius: 4px; outline: none; font-family: monospace;
        }
        .input-group input:focus { border-color: var(--dourado-titulos); }
        .password-wrapper { position: relative; }
        .radar-toggle {
            position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
            background: none; border: none; color: var(--metal-cinza-claro); cursor: pointer; padding: 5px;
        }
        .radar-toggle:hover { color: var(--dourado-titulos); }
        .btn-submit-militar {
            width: 100%; padding: 15px; margin-top: 10px; background-color: var(--metal-cinza-escuro);
            border: 1px solid var(--metal-cinza-claro); color: #555; font-weight: bold; letter-spacing: 2px;
            border-radius: 4px; cursor: not-allowed; transition: all 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-submit-militar.valido { background-color: var(--rus-vermelho); color: #fff; cursor: pointer; border-color: #ff4d4d; box-shadow: 0 0 15px rgba(255, 0, 0, 0.4); }

        /* ==================================================
           BANNER DE NOTIFICAÇÃO
           ================================================== */
        .tactical-banner {
            position: fixed; top: -100px; left: 50%; transform: translateX(-50%);
            background-color: #1a1a1a; border: 2px solid var(--dourado-titulos); color: var(--dourado-titulos);
            padding: 15px 30px; border-radius: 0 0 8px 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.9); z-index: 30000;
            font-family: monospace; font-size: 14px; font-weight: bold; display: flex; align-items: center; gap: 12px;
            transition: top 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .tactical-banner.show { top: 0; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    `;
    document.head.appendChild(estiloMenu);

    const menuHTML = `
    <nav class="top-navbar">
        <div class="nav-left">
            <a href="${caminhoBase}index.html" class="logo-container-nav">
                <div class="logo-plate-nav">
                    <img src="${caminhoBase}WT - Artes/War-Thunder-logo.png" alt="Logo do War Thunder" class="navbar-logo-img">
                </div>
            </a>
        </div>
        <div class="nav-center">
            <div style="font-size: 12px; font-weight: bold; color: #F2B400; letter-spacing: 2px; margin-bottom: 2px;">RELÓGIO DE GUERRA</div>
            <div class="clock-time">
                <span id="warHours" class="flip-digit">00</span>:<span id="warMinutes" class="flip-digit">00</span>:<span id="warSeconds" class="flip-digit">00</span> 
                <span style="font-size: 12px; color: #a0a0a0; text-shadow: none;">BRT</span>
            </div>
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
                            <img src="${caminhoBase}Caminho/Para/Sua/Imagem-Ver.png" id="radarIcon" style="width: 24px; height: auto;">
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