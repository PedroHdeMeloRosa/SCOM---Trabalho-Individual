/**
 * ============================================================================
 * WAR THUNDER - SCRIPT DE INTERFACE E SISTEMAS FRONT-END
 * ============================================================================
 * Este arquivo contém toda a lógica de interface, animações, sistema de login
 * simulado, buscas, configurações e injeção de HTML.
 * Versão Documentada / Desminificada
 * ============================================================================
 */

/**
 * 1. CARROSSEL AUTOMÁTICO
 * Cria um slider simples que passa as imagens automaticamente.
 */
function iniciarCarrossel() {
    // Busca todos os elementos que possuem a classe '.auto-slide'
    let carrosseis = document.querySelectorAll(".auto-slide");
    
    carrosseis.forEach(carrossel => {
        let slides = carrossel.querySelectorAll(".slide-item");
        
        // Se houver 1 ou 0 slides, não há necessidade de carrossel, então interrompe.
        if (slides.length <= 1) return;
        
        let indiceAtual = 0;
        
        // A cada 3.5 segundos, avança o slide
        setInterval(() => {
            // O operador % garante que volte ao zero após o último slide
            indiceAtual = (indiceAtual + 1) % slides.length;
            
            // Move o container dos slides no eixo X usando CSS
            carrossel.style.transform = `translateX(-${100 * indiceAtual}%)`;
        }, 3500);
    });
}

/**
 * 2. RELÓGIO DE GUERRA
 * Atualiza um relógio digital e adiciona uma animação "flip" 3D aos números quando mudam.
 */
function iniciarRelogio() {
    let elHoras = document.getElementById("warHours");
    let elMinutos = document.getElementById("warMinutes");
    let elSegundos = document.getElementById("warSeconds");
    let elData = document.getElementById("warClockDate");
    
    // Se algum elemento do relógio não existir na página, cancela a função
    if (!elHoras || !elMinutos || !elSegundos || !elData) return;
    
    // Variáveis para guardar o estado anterior (para saber quando animar)
    let horaAnt = "", minutoAnt = "", segundoAnt = "";
    
    // Array com os meses para formatar a data
    let meses = ["JAN","FEV","MAR","ABR","MAI","JUN","JUL","AGO","SET","OUT","NOV","DEZ"];
    
    // Keyframes da animação de virar (flip) a plaquinha
    let animacaoFlip = [
        { transform: "perspective(200px) rotateX(0deg)", opacity: 1 },
        { transform: "perspective(200px) rotateX(90deg)", opacity: 0.5, offset: 0.5 },
        { transform: "perspective(200px) rotateX(0deg)", opacity: 1 }
    ];
    
    // Duração e estilo da animação
    let configAnimacao = { duration: 300, easing: "ease-in-out" };
    
    // Função que calcula e atualiza o tempo
    let atualizarRelogio = () => {
        let dataAtual = new Date();
        
        // padStart(2, "0") garante que "9" vire "09"
        let hr = String(dataAtual.getHours()).padStart(2, "0");
        let min = String(dataAtual.getMinutes()).padStart(2, "0");
        let seg = String(dataAtual.getSeconds()).padStart(2, "0");
        
        // Se a hora mudou, atualiza o texto e roda a animação
        if (hr !== horaAnt) {
            elHoras.textContent = hr;
            elHoras.animate(animacaoFlip, configAnimacao);
            horaAnt = hr;
        }
        
        // Se o minuto mudou...
        if (min !== minutoAnt) {
            elMinutos.textContent = min;
            elMinutos.animate(animacaoFlip, configAnimacao);
            minutoAnt = min;
        }
        
        // Se o segundo mudou...
        if (seg !== segundoAnt) {
            elSegundos.textContent = seg;
            elSegundos.animate(animacaoFlip, configAnimacao);
            segundoAnt = seg;
        }
        
        // Atualiza a data (ex: 23 SET 2026)
        let dia = String(dataAtual.getDate()).padStart(2, "0");
        elData.textContent = `${dia} ${meses[dataAtual.getMonth()]} ${dataAtual.getFullYear()}`;
    };
    
    atualizarRelogio(); // Executa imediatamente
    setInterval(atualizarRelogio, 1000); // Repete a cada 1 segundo
}

/**
 * 3. SISTEMA DE LOGIN (Simulado)
 * @param {string} basePath - Caminho base para localizar as imagens e recursos.
 */
function iniciarLogin(basePath) {
    let modalLogin = document.getElementById("loginModal");
    if (!modalLogin) return;
    
    // "Banco de dados" mockado localmente
    let bancoUsuarios = {
        "comandante@gmail.com": { senha: "gaijin123", role: "admin", titulo: "COMANDANTE" },
        "soldado@gmail.com": { senha: "gaijin123", role: "user", titulo: "SOLDADO" }
    };
    
    // Captura de elementos
    let btnAbrirLogin = document.querySelector(".btn-login");
    let txtBotaoLogin = document.getElementById("navLoginText");
    let btnFecharModal = document.getElementById("closeModal");
    let formLogin = document.getElementById("loginForm");
    let inputEmail = document.getElementById("userEmail");
    let inputSenha = document.getElementById("userPassword");
    let btnSubmit = document.getElementById("btnSubmitLogin");
    let btnRadar = document.getElementById("btnRadar"); // Botão "olhinho" da senha
    let iconeRadar = document.getElementById("radarIcon");
    
    let animacaoRadarRodando = false;
    
    // Função para exibir o banner lateral de notificação
    let mostrarBanner = (texto, iconeSrc = "WT - Artes/UI-UX icons/Soldier-salute.webp") => {
        let banner = document.getElementById("customBanner");
        if (banner) {
            document.getElementById("customBannerText").textContent = texto;
            document.getElementById("bannerIcon").innerHTML = `<img src="${basePath}${iconeSrc}" style="width:24px;height:auto;object-fit:contain;">`;
            
            banner.classList.add("show");
            setTimeout(() => banner.classList.remove("show"), 4000); // Some após 4s
        }
    };
    
    // Mostra/Oculta painéis restritos para admins
    let atualizarPermissoes = (role) => {
        document.querySelectorAll(".admin-controls").forEach(el => {
            el.style.display = (role === "admin") ? "block" : "none";
        });
    };
    
    // Valida se os campos foram preenchidos para liberar o botão de enviar
    let checarInputs = () => {
        let emailValido = inputEmail.value.includes("@") && inputEmail.value.includes(".");
        let senhaValida = inputSenha.value.length > 0;
        
        btnSubmit.disabled = !(emailValido && senhaValida);
        btnSubmit.classList.toggle("valido", emailValido && senhaValida);
    };
    
    // Evento: Abrir modal ou fazer Logout
    if (btnAbrirLogin) {
        btnAbrirLogin.addEventListener("click", (evento) => {
            evento.preventDefault();
            
            // Se já estiver logado, faz o logout
            if (localStorage.getItem("isAuth") === "true") {
                localStorage.removeItem("isAuth");
                localStorage.removeItem("userRole");
                if (txtBotaoLogin) txtBotaoLogin.textContent = "ENTRAR";
                mostrarBanner("DESCONECTADO.", "WT - Artes/UI-UX icons/Logout-icon.webp");
                atualizarPermissoes("visitante");
            } else {
                // Se não, abre o modal
                modalLogin.classList.add("ativo");
            }
        });
    }
    
    // Evento: Fechar modal
    if (btnFecharModal) btnFecharModal.addEventListener("click", () => modalLogin.classList.remove("ativo"));
    
    // Evento: Efeito "Radar Hack" no botão de mostrar senha
    if (btnRadar) {
        btnRadar.addEventListener("click", () => {
            if (animacaoRadarRodando || inputSenha.value === "") return;
            
            if (inputSenha.type === "password") {
                animacaoRadarRodando = true;
                let senhaReal = inputSenha.value;
                inputSenha.type = "text";
                iconeRadar.src = `${basePath}WT - Artes/UI-UX icons/Radar-ver.webp`;
                inputSenha.disabled = true;
                
                let contagem = 0;
                // Troca caracteres aleatórios rapidamente para gerar efeito hacker
                let intervaloEfeito = setInterval(() => {
                    inputSenha.value = senhaReal.split("").map(() => {
                        let caracteres = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*";
                        return caracteres[Math.floor(Math.random() * caracteres.length)];
                    }).join("");
                    
                    contagem++;
                    // Após 12 iterações, revela a senha real
                    if (contagem > 12) {
                        clearInterval(intervaloEfeito);
                        inputSenha.value = senhaReal;
                        inputSenha.disabled = false;
                        animacaoRadarRodando = false;
                    }
                }, 40);
            } else {
                // Oculta a senha normalmente
                inputSenha.type = "password";
                iconeRadar.src = `${basePath}WT - Artes/UI-UX icons/Radar-ocultar.webp`;
            }
        });
    }
    
    // Ouve a digitação para validar o botão
    inputEmail.addEventListener("input", checarInputs);
    inputSenha.addEventListener("input", checarInputs);
    
    // Evento: Enviar o formulário de login
    formLogin.addEventListener("submit", (evento) => {
        evento.preventDefault();
        
        let emailDigitado = inputEmail.value;
        let senhaDigitada = inputSenha.value;
        
        // Estado de "carregando"
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<img src="${basePath}WT - Artes/UI-UX icons/Loading-icon.webp" alt="" aria-hidden="true" style="width:24px;height:auto;animation:spin 1s linear infinite;"> AUTENTICANDO...`;
        btnSubmit.style.backgroundColor = "#F2B400";
        
        // Simula delay de rede de 1 segundo
        setTimeout(() => {
            if (bancoUsuarios[emailDigitado] && bancoUsuarios[emailDigitado].senha === senhaDigitada) {
                // Sucesso
                let usuario = bancoUsuarios[emailDigitado];
                localStorage.setItem("isAuth", "true");
                localStorage.setItem("userRole", usuario.role);
                
                document.getElementById("loginModal").classList.remove("ativo");
                if (txtBotaoLogin) txtBotaoLogin.textContent = "SAIR";
                
                mostrarBanner(`BEM-VINDO, ${usuario.titulo}`);
                atualizarPermissoes(usuario.role);
                
                // Reseta visual do formulário
                btnSubmit.innerHTML = "AUTENTICAR";
                btnSubmit.style.backgroundColor = "";
                formLogin.reset();
                checarInputs();
            } else {
                // Erro
                btnSubmit.innerHTML = "ACESSO NEGADO";
                btnSubmit.style.backgroundColor = "#e81000"; // Vermelho
                btnSubmit.classList.remove("valido");
                
                setTimeout(() => {
                    btnSubmit.innerHTML = "AUTENTICAR";
                    btnSubmit.style.backgroundColor = "";
                    checarInputs();
                }, 2000);
            }
        }, 1000);
    });
    
    // Inicialização (IIFE): Checa se já estava logado antes
    (() => {
        let estaLogado = localStorage.getItem("isAuth") === "true";
        let role = localStorage.getItem("userRole");
        
        if (estaLogado) {
            if (txtBotaoLogin) txtBotaoLogin.textContent = "SAIR";
            atualizarPermissoes(role);
        } else {
            if (txtBotaoLogin) txtBotaoLogin.textContent = "ENTRAR";
            atualizarPermissoes("visitante");
        }
    })();
}

/**
 * 4. SCROLL SUAVE & BOTÃO DE VOLTAR AO TOPO
 */
function iniciarScrollSuave(basePath) {
    // Cria o HTML do botão dinamicamente e injeta no body
    let htmlBotao = `<button id="btnVoltarTopo" title="Voltar ao Topo"><img src="${basePath}WT - Artes/UI-UX icons/Seta_cima-icon.webp" alt="" aria-hidden="true" class="icone-topo"><span class="texto-topo">VOLTAR AO TOPO</span></button>`;
    document.body.insertAdjacentHTML("beforeend", htmlBotao);
    
    let btnVoltarTopo = document.getElementById("btnVoltarTopo");
    
    if (btnVoltarTopo) {
        // Mostra o botão ao rolar mais de 300px
        window.addEventListener("scroll", () => {
            if (window.scrollY > 300) {
                btnVoltarTopo.classList.add("visivel");
            } else {
                btnVoltarTopo.classList.remove("visivel");
            }
        });
        
        // Ação do botão: subir
        btnVoltarTopo.addEventListener("click", () => {
            document.body.scrollIntoView({ behavior: "smooth", block: "start" });
        });
        
        // Faz com que todos os links com âncoras (href="#algumacoisa") rolem suavemente
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener("click", function (evento) {
                let destino = this.getAttribute("href");
                if (destino !== "#") {
                    let elementoDestino = document.querySelector(destino);
                    if (elementoDestino) {
                        evento.preventDefault();
                        elementoDestino.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                }
            });
        });
    }
}

/**
 * 5. BARRA DE PESQUISA INTELIGENTE (AUTOCOMPLETE)
 */
function iniciarBusca(basePath) {
    // Como a navbar é injetada via JS, usamos setInterval para aguardar o elemento existir no DOM
    let intervaloEspera = setInterval(() => {
        let inputBusca = document.getElementById("searchInput");
        let containerSugestoes = document.getElementById("searchSuggestions");
        
        if (inputBusca && containerSugestoes) {
            clearInterval(intervaloEspera);
            
            // "Banco de dados" com todos os tanques
            let listaTanques = [
                { nome: "M4A3E2 Jumbo", url: "WT - Artes/USA/USA Tanks/JUMBO/JUMBO.html", icone: "WT - Artes/USA/USA Tanks/JUMBO/JUMBO_shadow.svg" },
                { nome: "M18 Hellcat", url: "WT - Artes/USA/USA Tanks/M18/Hellcat.html", icone: "WT - Artes/USA/USA Tanks/M18/M-18 Hellcat_shadow.svg" },
                { nome: "M1A2 Abrams", url: "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2-ABRAMS.html", icone: "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2-Abrams_shadow.svg" },
                { nome: "T34 Heavy Tank", url: "WT - Artes/USA/USA Tanks/T34/T34.html", icone: "WT - Artes/USA/USA Tanks/T34/T34_shadow.svg" },
                { nome: "T95 Doom Turtle", url: "WT - Artes/USA/USA Tanks/T95/T95.html", icone: "WT - Artes/USA/USA Tanks/T95/T95_shadow.svg" },
                { nome: "Panther D", url: "WT - Artes/GER/Ger Tanks/PANTER D/PANTER-D.html", icone: "WT - Artes/GER/Ger Tanks/PANTER D/Panter D_shadow.svg" },
                { nome: "Tiger H1", url: "WT - Artes/GER/Ger Tanks/TIGER H1/TIGER-H1.html", icone: "WT - Artes/GER/Ger Tanks/TIGER H1/TIGER H1_shadow.svg" },
                { nome: "Tiger II V2", url: "WT - Artes/GER/Ger Tanks/TIGER II V2/TIGERII.html", icone: "WT - Artes/GER/Ger Tanks/TIGER II V2/Tiger II_shadow.svg" },
                { nome: "Sturmtiger", url: "WT - Artes/GER/Ger Tanks/STURMTIGER/Sturmtiger.html", icone: "WT - Artes/GER/Ger Tanks/STURMTIGER/Sturmtiger_shadow.svg" },
                { nome: "MAUS", url: "WT - Artes/GER/Ger Tanks/MAUS/MAUS.html", icone: "WT - Artes/GER/Ger Tanks/MAUS/MAUS_shadow.svg" },
                { nome: "T-34-85", url: "WT - Artes/RUS/Rus Tanks/T-34 85/T34-85.html", icone: "WT - Artes/RUS/Rus Tanks/T-34 85/T-34 85_shadow.svg" },
                { nome: "KV-1 (ZiS-5)", url: "WT - Artes/RUS/Rus Tanks/KV-1/KV-1.html", icone: "WT - Artes/RUS/Rus Tanks/KV-1/KV-1_shadow.svg" },
                { nome: "IS-3", url: "WT - Artes/RUS/Rus Tanks/IS-3/IS-3.html", icone: "WT - Artes/RUS/Rus Tanks/IS-3/IS-3_shadow.svg" },
                { nome: "Object 279", url: "WT - Artes/RUS/Rus Tanks/OBJ-279/OBJ-279.html", icone: "WT - Artes/RUS/Rus Tanks/OBJ-279/OBJ-279_shadow.svg" },
                { nome: "SU-100", url: "WT - Artes/RUS/Rus Tanks/SU-100/SU-100.html", icone: "WT - Artes/RUS/Rus Tanks/SU-100/SU-100_shadow.svg" },
                { nome: "Chi-Ha LG", url: "WT - Artes/JPN/JPN Tanks/CHI-RA LG/CHI-RA-LG.html", icone: "WT - Artes/JPN/JPN Tanks/CHI-RA LG/CHI-RA LG_shadow.svg" },
                { nome: "Ho-Ri Production", url: "WT - Artes/JPN/JPN Tanks/HO-RI/HO-RI.html", icone: "WT - Artes/JPN/JPN Tanks/HO-RI/HO-Ri_shadow.svg" },
                { nome: "Ta-Se", url: "WT - Artes/JPN/JPN Tanks/Ta-Se/Ta-Se.html", icone: "WT - Artes/JPN/JPN Tanks/Ta-Se/Ta-Se_shadow.svg" },
                { nome: "Bkan 1C", url: "WT - Artes/SWE/SWE Tanks/BKAN 1/BKAN 1.html", icone: "WT - Artes/SWE/SWE Tanks/BKAN 1/BKAN 1_shadow.svg" },
                { nome: "BT-42", url: "WT - Artes/SWE/SWE Tanks/BT-42/BT-42.html", icone: "WT - Artes/SWE/SWE Tanks/BT-42/BT-42_shadow.svg" },
                { nome: "SAV 20.12.48", url: "WT - Artes/SWE/SWE Tanks/SAV 20.12.48/SAV-20.html", icone: "WT - Artes/SWE/SWE Tanks/SAV 20.12.48/SAV 20.12.48_shadow.svg" }
            ];
            
            let indiceFoco = -1; // Para navegação pelo teclado
            
            // Função para pintar o item focado com teclado
            function atualizarFocoTeclado(elementosLI) {
                elementosLI.forEach(el => el.classList.remove("ativo"));
                if (indiceFoco > -1) {
                    elementosLI[indiceFoco].classList.add("ativo");
                    inputBusca.value = elementosLI[indiceFoco].querySelector("span").textContent;
                }
            }
            
            // Evento: Quando o usuário digita
            inputBusca.addEventListener("input", () => {
                let termoBusca = inputBusca.value.toLowerCase();
                containerSugestoes.innerHTML = "";
                indiceFoco = -1;
                
                // Se o input estiver vazio, esconde a caixa
                if (termoBusca.length === 0) {
                    containerSugestoes.style.display = "none";
                    return;
                }
                
                // Filtra os tanques pelo nome digitado
                let resultados = listaTanques.filter(tanque => tanque.nome.toLowerCase().includes(termoBusca));
                
                if (resultados.length > 0) {
                    containerSugestoes.style.display = "block";
                    
                    // Monta as tags <li> de sugestão
                    resultados.forEach(tanque => {
                        let li = document.createElement("li");
                        li.innerHTML = `<a href="${basePath}${tanque.url}" class="suggestion-link"><img src="${basePath}${tanque.icone}" alt="" aria-hidden="true" class="suggestion-icon"><span>${tanque.nome}</span></a>`;
                        
                        li.addEventListener("click", () => {
                            window.location.href = `${basePath}${tanque.url}`;
                        });
                        
                        containerSugestoes.appendChild(li);
                    });
                } else {
                    containerSugestoes.style.display = "none";
                }
            });
            
            // Evento: Navegação pelo teclado nas setas Up/Down
            inputBusca.addEventListener("keydown", (evento) => {
                let itensLista = containerSugestoes.querySelectorAll("li");
                if (itensLista.length === 0) return;
                
                if (evento.key === "ArrowDown") {
                    indiceFoco++;
                    if (indiceFoco >= itensLista.length) indiceFoco = -1;
                    atualizarFocoTeclado(itensLista);
                } else if (evento.key === "ArrowUp") {
                    indiceFoco--;
                    if (indiceFoco < 0) indiceFoco = itensLista.length - 1;
                    atualizarFocoTeclado(itensLista);
                } else if (evento.key === "Enter") {
                    evento.preventDefault();
                    if (indiceFoco > -1) itensLista[indiceFoco].click();
                }
            });
            
            // Fecha a barra de pesquisa se clicar fora dela
            document.addEventListener("click", (evento) => {
                if (!inputBusca.contains(evento.target) && !containerSugestoes.contains(evento.target)) {
                    containerSugestoes.style.display = "none";
                }
            });
        }
    }, 100);
}

/**
 * 6. INJETAR INTERFACE GLOBAL
 * Evita repetir código HTML em múltiplas páginas injetando Navbar, Modais e Banners via JS.
 */
function injetarInterface(basePath) {
    let htmlPrincipal = `
    <!-- Barra de Navegação -->
    <nav class="top-navbar">
        <div class="nav-left">
            <a href="https://warthunder.com/..." class="logo-container-nav">
                <div class="logo-plate-nav">
                    <img src="${basePath}WT - Artes/War-Thunder-logo.webp" alt="Logo do War Thunder" width="100" height="50" class="navbar-logo-img">
                </div>
            </a>
            <div class="search-container">
                <img src="${basePath}WT - Artes/UI-UX icons/Search_icon.webp" class="search-icon" alt="" aria-hidden="true" width="23" height="23">
                <input type="text" id="searchInput" aria-label="Buscar blindado" placeholder="Buscar blindado..." autocomplete="off">
                <ul class="search-suggestions" id="searchSuggestions"></ul>
            </div>
            <button id="navToggleBtn" class="nav-toggle-btn">☰ MENU</button>
        </div>
        
        <!-- Relógio -->
        <div class="nav-center">
            <div style="font-size:12px;font-weight:bold;color:#F2B400;letter-spacing:2px;margin-bottom:2px;">RELÓGIO DE GUERRA</div>
            <div class="clock-time">
                <span id="warHours" class="flip-digit">00</span>:<span id="warMinutes" class="flip-digit">00</span>:<span id="warSeconds" class="flip-digit">00</span>
                <span style="font-size:12px;color:#a0a0a0;text-shadow:none;">BRT</span>
            </div>
            <div class="clock-date" id="warClockDate">00/00/0000</div>
        </div>
        
        <!-- Botões Direitos -->
        <div class="nav-right">
            <a href="${basePath}index.html" class="nav-item">
                <div class="icon-box"><img src="${basePath}WT - Artes/UI-UX icons/HOME_icon.webp" alt="" aria-hidden="true" class="nav-icon-img" width="24" height="24"></div>
                <div class="text-box">INÍCIO</div>
            </a>
            <a href="#" class="nav-item" id="btnOpenConfig">
                <div class="icon-box"><img src="${basePath}WT - Artes/UI-UX icons/Config_icon.webp" alt="" aria-hidden="true" class="nav-icon-img" width="24" height="24"></div>
                <div class="text-box">CONFIG.</div>
            </a>
            <a href="#" class="nav-item">
                <div class="icon-box"><img src="${basePath}WT - Artes/UI-UX icons/Register_icon.webp" alt="" aria-hidden="true" class="nav-icon-img" width="24" height="24"></div>
                <div class="text-box">REGISTRO</div>
            </a>
            <a href="#" class="nav-item btn-login">
                <div class="icon-box"><img src="${basePath}WT - Artes/UI-UX icons/LOGIN-icon.webp" alt="" aria-hidden="true" class="nav-icon-img" width="24" height="24"></div>
                <div class="text-box" id="navLoginText">ENTRAR</div>
            </a>
        </div>
    </nav>
    
    <!-- Modal de Login -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal-box">
            <button class="fechar-modal" id="closeModal" aria-label="Fechar modal">X</button>
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
                        <button type="button" id="btnRadar" class="radar-toggle" aria-label="Alternar Senha" title="Alternar Senha">
                            <img src="${basePath}WT - Artes/UI-UX icons/Radar-ocultar.webp" alt="" aria-hidden="true" id="radarIcon" style="width:24px;height:auto;">
                        </button>
                    </div>
                </div>
                <button type="submit" id="btnSubmitLogin" class="btn-submit-militar" disabled>AUTENTICAR</button>
            </form>
        </div>
    </div>
    
    <!-- Modal de Configurações -->
    <div class="modal-overlay" id="configModal">
        <div class="modal-box">
            <button class="fechar-modal" id="closeConfigModal" aria-label="Fechar configurações">X</button>
            <h2 class="modal-title">SISTEMA</h2>
            <p class="modal-subtitle">Ajustes Táticos e Desempenho</p>
            <div class="config-item">
                <label for="themeSelect">Interface Visual</label>
                <select id="themeSelect">
                    <option value="dark">Tático Padrão (Escuro)</option>
                    <option value="desert">Dossiê Militar (Deserto)</option>
                    <option value="night">Visão Noturna (Térmica)</option>
                </select>
            </div>
            <div class="config-item">
                <label for="langSelect">Idioma do Sistema</label>
                <select id="langSelect">
                    <option value="pt">Português (BR)</option>
                    <option value="en">English (US)</option>
                </select>
            </div>
            <div class="config-item">
                <label for="togglePerf">Desempenho Extremo (Desativar Animações)</label>
                <label class="switch">
                    <input type="checkbox" id="togglePerf" aria-label="Ativar Desempenho Extremo">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="config-item" style="border:none;margin-top:25px">
                <label>Limpar Dados Locais</label>
                <button id="btnPurge" class="btn-submit-militar" style="width:auto;margin:0;padding:10px 15px;font-size:11px;background-color:var(--rus-vermelho);color:#fff">PURGAR</button>
            </div>
        </div>
    </div>
    
    <!-- Banner Lateral Animado -->
    <div id="customBanner" class="tactical-banner">
        <span class="material-symbols-outlined" id="bannerIcon">military_tech</span>
        <span id="customBannerText">Notificação</span>
    </div>
    `;
    
    // Injeta tudo no início da tag <body>
    document.body.insertAdjacentHTML("afterbegin", htmlPrincipal);
}

/**
 * 7. BOTÃO MODO LISTA / MODO CAIXAS
 * Permite alterar o layout de grade de veículos para uma lista vertical.
 */
function iniciarToggleVisualizacao() {
    let btnToggle = document.getElementById("btnToggleView");
    let containerNacoes = document.querySelector(".nation-content");
    
    if (btnToggle && containerNacoes) {
        btnToggle.addEventListener("click", () => {
            containerNacoes.classList.toggle("list-view");
            
            if (containerNacoes.classList.contains("list-view")) {
                btnToggle.innerHTML = "&#9638; MODO CAIXAS";
            } else {
                btnToggle.innerHTML = "&#9776; MODO LISTA";
            }
        });
    }
}

/**
 * 8. MODAL DE CONFIGURAÇÕES DE USUÁRIO
 */
function iniciarConfig() {
    let modalConfig = document.getElementById("configModal");
    let btnAbrirConfig = document.getElementById("btnOpenConfig");
    let btnFecharConfig = document.getElementById("closeConfigModal");
    let toggleDesempenho = document.getElementById("togglePerf");
    let selectTema = document.getElementById("themeSelect");
    let selectIdioma = document.getElementById("langSelect");
    let btnPurgar = document.getElementById("btnPurge");
    
    if (!modalConfig || !btnAbrirConfig) return;
    
    // Abrir/Fechar modal
    btnAbrirConfig.addEventListener("click", (evento) => {
        evento.preventDefault();
        modalConfig.classList.add("ativo");
    });
    btnFecharConfig.addEventListener("click", () => modalConfig.classList.remove("ativo"));
    
    // === CONFIGURAÇÃO: Modo Desempenho ===
    if (localStorage.getItem("lowPerf") === "true") {
        toggleDesempenho.checked = true;
        document.body.classList.add("low-perf");
    }
    toggleDesempenho.addEventListener("change", (evento) => {
        if (evento.target.checked) {
            localStorage.setItem("lowPerf", "true");
            document.body.classList.add("low-perf");
        } else {
            localStorage.setItem("lowPerf", "false");
            document.body.classList.remove("low-perf");
        }
    });
    
    // === CONFIGURAÇÃO: Temas ===
    let temaAtual = localStorage.getItem("siteTheme") || "dark";
    selectTema.value = temaAtual;
    
    if (temaAtual === "desert") document.body.classList.add("theme-desert");
    if (temaAtual === "night") document.body.classList.add("theme-night");
    
    selectTema.addEventListener("change", (evento) => {
        document.body.classList.remove("theme-desert", "theme-night");
        if (evento.target.value === "desert") document.body.classList.add("theme-desert");
        if (evento.target.value === "night") document.body.classList.add("theme-night");
        
        localStorage.setItem("siteTheme", evento.target.value);
    });
    
    // === CONFIGURAÇÃO: Idioma ===
    let idiomaAtual = localStorage.getItem("siteLang") || "pt";
    selectIdioma.value = idiomaAtual;
    
    selectIdioma.addEventListener("change", (evento) => {
        localStorage.setItem("siteLang", evento.target.value);
        let banner = document.getElementById("customBanner");
        if (banner) {
            document.getElementById("customBannerText").textContent = "Idioma salvo. Atualize a página para aplicar a tradução.";
            document.getElementById("bannerIcon").innerHTML = "language";
            banner.classList.add("show");
            setTimeout(() => banner.classList.remove("show"), 4000);
        }
    });
    
    // === CONFIGURAÇÃO: Purgar Dados ===
    btnPurgar.addEventListener("click", () => {
        localStorage.clear(); // Limpa histórico, login e preferências
        location.reload();    // Recarrega a página
    });
}

/**
 * 9. FUNÇÃO ORQUESTRADORA (BOOTSTRAP)
 * Executa todas as funções acima após a página carregar.
 */
function injetarMenu(basePath) {
    document.addEventListener("DOMContentLoaded", () => {
        injetarInterface(basePath);
        iniciarLogin(basePath);
        iniciarConfig();
        iniciarBusca(basePath);
        iniciarScrollSuave(basePath);
        iniciarRelogio();
        iniciarToggleVisualizacao();
        
        // Comportamento do Botão Menu Hambúrguer (Mobile)
        document.addEventListener("click", (evento) => {
            let btnToggle = document.getElementById("navToggleBtn");
            let navbar = document.querySelector(".top-navbar");
            
            if (btnToggle && navbar) {
                if (btnToggle.contains(evento.target)) {
                    navbar.classList.toggle("expanded"); // Abre o menu
                } else if (!navbar.contains(evento.target) && navbar.classList.contains("expanded")) {
                    navbar.classList.remove("expanded"); // Fecha se clicar fora
                }
            }
        });
    });
}

/**
 * 10. SLIDER MANUAL (Próximo / Anterior) - Específico para imagens de um tanque
 */
document.addEventListener("DOMContentLoaded", () => {
    let trilhaImagens = document.getElementById("tank-track");
    let legendaImagem = document.getElementById("tank-caption");
    let btnProximo = document.getElementById("btnNext");
    let btnAnterior = document.getElementById("btnPrev");
    
    if (trilhaImagens && legendaImagem && btnProximo && btnAnterior) {
        // Converte os filhos (as imagens) em um Array real
        let slides = Array.from(trilhaImagens.children);
        let indiceAtual = 0;
        
        // Função que movimenta a trilha
        function atualizarSlider() {
            let porcentagem = 100 * indiceAtual;
            trilhaImagens.style.transform = `translateX(-${porcentagem}%)`;
            
            // Puxa a legenda baseada na tag "alt" da imagem
            if (slides[indiceAtual] && slides[indiceAtual].alt) {
                legendaImagem.textContent = slides[indiceAtual].alt;
            }
        }
        
        atualizarSlider(); // Posiciona no início
        
        btnProximo.addEventListener("click", () => {
            // Avança ou volta pro zero se tiver chegado no final
            indiceAtual = (indiceAtual < slides.length - 1) ? indiceAtual + 1 : 0;
            atualizarSlider();
        });
        
        btnAnterior.addEventListener("click", () => {
            // Volta ou vai pro final se estiver no começo
            indiceAtual = (indiceAtual > 0) ? indiceAtual - 1 : slides.length - 1;
            atualizarSlider();
        });
    }
});