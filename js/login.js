// ==========================================
// SISTEMA DE AUTENTICAÇÃO TÁTICA E SESSÃO
// ==========================================
function iniciarLogin(caminhoBase) {
    const modal = document.getElementById('loginModal');
    
    // Trava de segurança: se o modal não existir nesta página, aborta o script para não gerar erros.
    if (!modal) return;

    // BANCO DE DADOS SIMULADO (Front-end)
    const CONTAS = {
        "comandante@gmail.com": { senha: "gaijin123", role: "admin", titulo: "COMANDANTE" },
        "soldado@gmail.com":    { senha: "gaijin123", role: "user",  titulo: "SOLDADO" }
    };

    // Elementos do DOM
    const btnAbrirModal = document.querySelector('.btn-login'); 
    const navLoginText = document.getElementById('navLoginText'); 
    const btnFecharModal = document.getElementById('closeModal');
    const loginForm = document.getElementById('loginForm');
    const inputEmail = document.getElementById('userEmail');
    const inputSenha = document.getElementById('userPassword');
    const btnSubmit = document.getElementById('btnSubmitLogin');
    const btnRadar = document.getElementById('btnRadar');
    const radarIcon = document.getElementById('radarIcon');
    
    let isScrambling = false;    

    // ==========================================
    // FUNÇÕES AUXILIARES
    // ==========================================
    const mostrarBanner = (mensagem, iconeSrc = 'WT - Artes/UI-UX icons/Soldier-salute.png') => {
        const banner = document.getElementById('customBanner');
        if (!banner) return;

        document.getElementById('customBannerText').textContent = mensagem;
        document.getElementById('bannerIcon').innerHTML = `<img src="${caminhoBase}${iconeSrc}" style="width: 24px; height: auto; object-fit: contain;">`;
        
        banner.classList.add('show');
        setTimeout(() => banner.classList.remove('show'), 4000);
    };

    const aplicarPermissoes = (role) => {
        const adminControls = document.querySelectorAll('.admin-controls');
        adminControls.forEach(el => {
            el.style.display = (role === 'admin') ? 'block' : 'none';
        });
    };

    const checarSessao = () => {
        const isAuth = localStorage.getItem('isAuth') === 'true';
        const role = localStorage.getItem('userRole');
        
        if (isAuth) {
            if (navLoginText) navLoginText.textContent = "SAIR";
            aplicarPermissoes(role);
        } else {
            if (navLoginText) navLoginText.textContent = "ENTRAR";
            aplicarPermissoes('visitante');
        }
    };

    const validarFormulario = () => {
        const emailValido = inputEmail.value.includes('@') && inputEmail.value.includes('.');
        const senhaValida = inputSenha.value.length > 0;
        
        btnSubmit.disabled = !(emailValido && senhaValida);
        btnSubmit.classList.toggle('valido', emailValido && senhaValida);
    };

    // ==========================================
    // EVENTOS DE INTERFACE
    // ==========================================
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', (e) => {
            e.preventDefault();
            if (localStorage.getItem('isAuth') === 'true') {
                localStorage.removeItem('isAuth');
                localStorage.removeItem('userRole');
                if (navLoginText) navLoginText.textContent = "ENTRAR";
                mostrarBanner("DESCONECTADO.", "WT - Artes/UI-UX icons/Logout-icon.png");
                aplicarPermissoes('visitante');
            } else {
                modal.classList.add('ativo');
            }
        });
    }

    if (btnFecharModal) {
        btnFecharModal.addEventListener('click', () => modal.classList.remove('ativo'));
    }

    // Embaralhamento do Radar (Scramble)
    if (btnRadar) {
        btnRadar.addEventListener('click', () => {
            if (isScrambling || inputSenha.value === "") return;
            
            if (inputSenha.type === 'password') {
                isScrambling = true;
                const realPassword = inputSenha.value;
                inputSenha.type = 'text';
                radarIcon.src = `${caminhoBase}WT - Artes/UI-UX icons/Radar-ver.png`; 
                inputSenha.disabled = true; 

                let iterations = 0;
                const scrambleInterval = setInterval(() => {
                    inputSenha.value = realPassword.split('').map(() => {
                        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
                        return chars[Math.floor(Math.random() * chars.length)];
                    }).join('');
                    
                    if (++iterations > 12) {
                        clearInterval(scrambleInterval);
                        inputSenha.value = realPassword; 
                        inputSenha.disabled = false;
                        isScrambling = false;
                    }
                }, 40);
            } else {
                inputSenha.type = 'password';
                radarIcon.src = `${caminhoBase}WT - Artes/UI-UX icons/Radar-ocultar.png`;
            }
        });
    }

    // ==========================================
    // EVENTOS DE FORMULÁRIO (LOGIN)
    // ==========================================
    inputEmail.addEventListener('input', validarFormulario);
    inputSenha.addEventListener('input', validarFormulario);

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault(); 
        const emailDigitado = inputEmail.value;
        const senhaDigitada = inputSenha.value;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<img src="${caminhoBase}WT - Artes/UI-UX icons/Loading-icon.png" style="width: 24px; height: auto; animation: spin 1s linear infinite;"> AUTENTICANDO...`;
        btnSubmit.style.backgroundColor = "#F2B400"; 

        setTimeout(() => {
            if (CONTAS[emailDigitado] && CONTAS[emailDigitado].senha === senhaDigitada) {
                const conta = CONTAS[emailDigitado];
                localStorage.setItem('isAuth', 'true');
                localStorage.setItem('userRole', conta.role);

                modal.classList.remove('ativo');
                if (navLoginText) navLoginText.textContent = "SAIR";
                mostrarBanner(`BEM-VINDO, ${conta.titulo}`);
                aplicarPermissoes(conta.role);

                btnSubmit.innerHTML = `AUTENTICAR`;
                btnSubmit.style.backgroundColor = ""; 
                loginForm.reset();
                validarFormulario();
            } else {
                btnSubmit.innerHTML = `ACESSO NEGADO`;
                btnSubmit.style.backgroundColor = "#8B0000"; 
                btnSubmit.classList.remove('valido');
                
                setTimeout(() => {
                    btnSubmit.innerHTML = `AUTENTICAR`;
                    btnSubmit.style.backgroundColor = ""; 
                    validarFormulario();
                }, 2000);
            }
        }, 1000); 
    });

    // Inicia checando o status atual
    checarSessao();
}