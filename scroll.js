function iniciarScrollSuave(caminhoBase) {
    // 1. Injetar o botão "Voltar ao Topo" no HTML (Com imagem e texto)
    const scrollBtnHTML = `
        <button id="btnVoltarTopo" title="Voltar ao Topo">
            <img src="${caminhoBase}WT - Artes/UI-UX icons/Seta_cima-icon.png" alt="Voltar ao Topo" class="icone-topo">
            <span class="texto-topo">VOLTAR AO TOPO</span>
        </button>
    `;
    document.body.insertAdjacentHTML('beforeend', scrollBtnHTML);

    // 2. Injetar o CSS do botão
    const estiloScroll = document.createElement('style');
    estiloScroll.innerHTML = `
        #btnVoltarTopo {
            position: fixed;
            bottom: -80px; /* Escondido fora da tela por padrão */
            right: 30px;
            background-color: var(--rus-vermelho, #6E1111);
            color: #fff;
            border: 2px solid var(--metal-cinza-claro, #8a8d8f);
            border-radius: 25px; /* Formato de pílula para caber o texto */
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px; /* Espaçamento entre a imagem e o texto */
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            transition: bottom 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55), transform 0.2s ease, background-color 0.2s ease;
            z-index: 15000;
        }

        /* Estilização da imagem personalizada */
        .icone-topo {
            width: 20px;
            height: auto;
            object-fit: contain;
        }

        /* Estilização do texto visível */
        .texto-topo {
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            white-space: nowrap; /* Impede que o texto quebre em duas linhas */
        }
        
        /* A classe que o JS vai adicionar quando rolarmos para baixo */
        #btnVoltarTopo.visivel {
            bottom: 30px; /* Sobe para dentro da tela */
        }
        
        #btnVoltarTopo:hover {
            background-color: #ff4d4d;
            transform: scale(1.05); /* Escala reduzida para não ficar exagerado na pílula */
        }
    `;
    document.head.appendChild(estiloScroll);

    // 3. Lógica JavaScript para Mostrar/Esconder e fazer a Rolagem Suave
    const btnTopo = document.getElementById('btnVoltarTopo');

    // Escuta a rolagem da página. Se passar de 300px, o botão aparece.
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btnTopo.classList.add('visivel');
        } else {
            btnTopo.classList.remove('visivel');
        }
    });

    // EVENTO EXIGIDO PELO PROFESSOR: scrollIntoView
    btnTopo.addEventListener('click', () => {
        // Rola suavemente até o topo da tag <body>
        document.body.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });

    // Aplica a rolagem suave a qualquer link da página que aponte para um ID
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const destino = this.getAttribute('href');
            
            // Ignora links vazios como href="#" usados no seu menu
            if (destino !== "#" && document.querySelector(destino)) {
                e.preventDefault();
                document.querySelector(destino).scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}