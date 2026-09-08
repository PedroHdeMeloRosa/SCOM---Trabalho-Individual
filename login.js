function iniciarLogin(caminhoBase) {
    // ==========================================
    // BANCO DE DADOS SIMULADO (Front-end)
    // ==========================================
    const CONTAS = {
        "comandante@gmail.com": { senha: "gaijin123", role: "admin", titulo: "COMANDANTE" },
        "soldado@gmail.com":    { senha: "gaijin123", role: "user",  titulo: "SOLDADO" }
    };

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
    const radarText = document.getElementById('radarText');

    let isScrambling = false;    

    // Sistema de Banner Tático
    const mostrarBanner = (mensagem, iconeSrc = 'WT - Artes/UI-UX icons/Soldier-salute.png') => {
        const banner = document.getElementById('customBanner');
        document.getElementById('customBannerText').textContent = mensagem;
        document.getElementById('bannerIcon').innerHTML = `<img src="${caminhoBase}${iconeSrc}" style="width: 24px; height: auto; object-fit: contain;">`;
        
        banner.classList.add('show');
        setTimeout(() => { banner.classList.remove('show'); }, 4000);
    };

    // ==========================================
    // GERENCIADOR DE SESSÃO (Resolve o Deslogue)
    // ==========================================
    const checarSessao = () => {
        const isAuth = localStorage.getItem('isAuth') === 'true';
        const role = localStorage.getItem('userRole');
        
        if (isAuth) {
            navLoginText.textContent = "SAIR";
            aplicarPermissoes(role);
        } else {
            navLoginText.textContent = "ENTRAR";
            aplicarPermissoes('visitante');
        }
    };

    // ==========================================
    // GERENCIADOR DE PERMISSÕES (Mostra/Oculta Botão)
    // ==========================================
    const aplicarPermissoes = (role) => {
        // Procura todos os botões/áreas restritas na página atual
        const adminControls = document.querySelectorAll('.admin-controls');
        
        adminControls.forEach(el => {
            if (role === 'admin') {
                el.style.display = 'block'; // Revela para o Comandante
            } else {
                el.style.display = 'none'; // Continua escondido para Soldados e Visitantes
            }
        });
    };

    // Botão de Abrir Modal / Deslogar
    btnAbrirModal.addEventListener('click', (e) => {
        e.preventDefault();
        if (localStorage.getItem('isAuth') === 'true') {
            // DESLOGAR
            localStorage.removeItem('isAuth');
            localStorage.removeItem('userRole');
            navLoginText.textContent = "ENTRAR";
            mostrarBanner("DESCONECTADO.", "WT - Artes/UI-UX icons/Logout-icon.png");
            aplicarPermissoes('visitante'); // Esconde os botões instantaneamente
        } else {
            modal.classList.add('ativo');
        }
    });

    btnFecharModal.addEventListener('click', () => { modal.classList.remove('ativo'); });

    // Lógica Visual do Botão Radar (Ver/Ocultar)
    // Lógica Visual do Botão Radar (Ver/Ocultar com Animação Tática)
    btnRadar.addEventListener('click', () => {
        if (isScrambling || inputSenha.value === "") return;
        
        if (inputSenha.type === 'password') {
            isScrambling = true;
            const realPassword = inputSenha.value;
            inputSenha.type = 'text';
            
            // Troca a imagem e o texto para OCULTAR
            radarIcon.src = `${caminhoBase}WT - Artes/UI-UX icons/Radar-ver.png`; 
            if (radarText) radarText.textContent = "OCULTAR";
            
            inputSenha.disabled = true; 

            // Efeito de Embaralhamento (Scramble)
            let iterations = 0;
            const scrambleInterval = setInterval(() => {
                inputSenha.value = realPassword.split('').map(() => {
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
                    return chars[Math.floor(Math.random() * chars.length)];
                }).join('');
                
                iterations++;
                if (iterations > 12) {
                    clearInterval(scrambleInterval);
                    inputSenha.value = realPassword; // Mostra a senha real
                    inputSenha.disabled = false;
                    isScrambling = false;
                }
            }, 40);
        } else {
            // Volta para modo senha normal imediatamente
            inputSenha.type = 'password';
            radarIcon.src = `${caminhoBase}WT - Artes/UI-UX icons/Radar-ocultar.png`;
            if (radarText) radarText.textContent = "VER";
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

    // ==========================================
    // FLUXO DE LOGIN
    // ==========================================
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault(); 
        const emailDigitado = inputEmail.value;
        const senhaDigitada = inputSenha.value;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<img src="${caminhoBase}WT - Artes/UI-UX icons/Loading-icon.png" style="width: 24px; height: auto; animation: spin 1s linear infinite;"> AUTENTICANDO...`;
        btnSubmit.style.backgroundColor = "#F2B400"; 

        setTimeout(() => {
            // Verifica se a conta existe no JS e se a senha bate
            if (CONTAS[emailDigitado] && CONTAS[emailDigitado].senha === senhaDigitada) {
                // SUCESSO: Salva os dados no navegador
                const conta = CONTAS[emailDigitado];
                localStorage.setItem('isAuth', 'true');
                localStorage.setItem('userRole', conta.role);

                modal.classList.remove('ativo');
                navLoginText.textContent = "SAIR";
                mostrarBanner(`BEM-VINDO, ${conta.titulo}`);
                
                // Aplica a regra de mostrar/ocultar botão
                aplicarPermissoes(conta.role);

                btnSubmit.innerHTML = `AUTENTICAR`;
                btnSubmit.style.backgroundColor = ""; 
                loginForm.reset();
                validarFormulario();
            } else {
                // ERRO
                btnSubmit.innerHTML = `ACESSO NEGADO`;
                btnSubmit.style.backgroundColor = "#8B0000"; 
                btnSubmit.classList.remove('valido');
                
                setTimeout(() => {
                    btnSubmit.innerHTML = `AUTENTICAR`;
                    btnSubmit.style.backgroundColor = ""; 
                    validarFormulario();
                }, 2000);
            }
        }, 1000); // Simulando tempo de resposta do servidor
    });

    // Chama a função ao carregar a página para verificar se já estava logado
    checarSessao();
}