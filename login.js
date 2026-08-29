function iniciarLogin(caminhoBase) {
    // ==========================================
    // CONFIGURAÇÃO DE CREDENCIAIS EXATAS DO PROJETO
    // ==========================================
    const EMAIL_TESTE = "PAKKAoBR@gmail.com";
    const SENHA_TESTE = "12345";

    const modal = document.getElementById('loginModal');
    const btnAbrirModal = document.querySelector('.btn-login'); 
    const navLoginText = document.getElementById('navLoginText'); 
    const btnFecharModal = document.getElementById('closeModal');
    const loginForm = document.getElementById('loginForm');
    
    const inputEmail = document.getElementById('userEmail');
    const inputSenha = document.getElementById('userPassword');
    const btnSubmit = document.getElementById('btnSubmitLogin');
    const btnRadar = document.getElementById('btnRadar');
    
    const radarIcon = document.getElementById('radarIcon');

    // [NOVO] Força a imagem correta do radar assim que o script carrega (evita o bug visual)
    if(radarIcon) {
        radarIcon.src = `${caminhoBase}WT - Artes/UI-UX icons/Radar-ocultar.png`;
    }

    let isAuthenticated = false; 
    let isScrambling = false;    

    // [NOVO] Agora recebe o caminho da imagem e injeta uma tag <img> no banner
    const mostrarBanner = (mensagem, iconeSrc = 'WT - Artes/UI-UX icons/Soldier-salute.png') => {
        const banner = document.getElementById('customBanner');
        document.getElementById('customBannerText').textContent = mensagem;
        
        // Substitui o antigo ícone em texto por uma imagem real
        document.getElementById('bannerIcon').innerHTML = `<img src="${caminhoBase}${iconeSrc}" style="width: 24px; height: auto; object-fit: contain;">`;
        
        banner.classList.add('show');
        setTimeout(() => { banner.classList.remove('show'); }, 4000);
    };

    btnAbrirModal.addEventListener('click', (e) => {
        e.preventDefault();
        if (isAuthenticated) {
            isAuthenticated = false;
            navLoginText.textContent = "ENTRAR";
            // Você pode trocar a imagem do "logout" abaixo se tiver uma específica
            mostrarBanner("PILOTO,DESCANSAR.", "WT - Artes/UI-UX icons/Soldier-salute.png");
        } else {
            modal.classList.add('ativo');
        }
    });

    btnFecharModal.addEventListener('click', () => { modal.classList.remove('ativo'); });

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
                
                iterations++;
                if (iterations > 12) {
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

    const validarFormulario = () => {
        const emailPreenchido = inputEmail.value.includes('@') && inputEmail.value.includes('.');
        const senhaPreenchida = inputSenha.value.length > 0;
        
        if (emailPreenchido && senhaPreenchida) {
            btnSubmit.disabled = false;
            btnSubmit.classList.add('valido');
        } else {
            btnSubmit.disabled = true;
            btnSubmit.classList.remove('valido');
        }
    };

    inputEmail.addEventListener('input', validarFormulario);
    inputSenha.addEventListener('input', validarFormulario);

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault(); 

        // 1. VERIFICAÇÃO EXATA DE CREDENCIAIS
        if (inputEmail.value !== EMAIL_TESTE || inputSenha.value !== SENHA_TESTE) {
            btnSubmit.innerHTML = `ACESSO NEGADO`;
            btnSubmit.style.backgroundColor = "#8B0000"; // Vermelho escuro de erro
            btnSubmit.classList.remove('valido');
            
            setTimeout(() => {
                btnSubmit.innerHTML = `AUTENTICAR`;
                btnSubmit.style.backgroundColor = ""; 
                validarFormulario();
            }, 2000);
            return; // Interrompe o código aqui se estiver errado
        }

        // 2. SE ESTIVER CERTO, INICIA A PROMISE
        btnSubmit.disabled = true;
        
        // [NOVO] Troque o "Loading-icon.png" pela sua imagem de loading. A animação de girar (spin) continuará funcionando nela!
        btnSubmit.innerHTML = `<img src="${caminhoBase}WT - Artes/UI-UX icons/Loading-icon.png" style="width: 24px; height: auto; animation: spin 1s linear infinite;"> AUTENTICANDO...`;
        btnSubmit.style.backgroundColor = "#F2B400"; 

        const simularAutenticacao = new Promise((resolve) => {
            setTimeout(() => { resolve(inputEmail.value); }, 2000); 
        });

        simularAutenticacao.then((emailUser) => {
            modal.classList.remove('ativo');
            isAuthenticated = true;
            const comandante = emailUser.split('@')[0].toUpperCase();
            navLoginText.textContent = "SAIR";
            
            // Dispara o banner usando a imagem padrão (Soldier-salute.png configurada lá em cima)
            mostrarBanner(`BEM-VINDO, PILOTO ${comandante}`);

            setTimeout(() => {
                btnSubmit.innerHTML = `AUTENTICAR`;
                btnSubmit.style.backgroundColor = ""; 
                loginForm.reset();
                validarFormulario();
            }, 500);
        });
    });
}