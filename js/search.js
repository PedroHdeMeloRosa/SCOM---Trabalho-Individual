function iniciarBusca(caminhoBase) {
    // Cria um radar que procura a barra de pesquisa a cada 100ms
    const radarBusca = setInterval(() => {
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        
        // Só continua QUANDO a interface finalmente for desenhada na tela
        if (searchInput && searchSuggestions) {
            clearInterval(radarBusca); // Desliga o radar, já encontramos os elementos!

            // 1. O Novo Banco de Dados (Array de Objetos)
            const blindados = [
                /*  TANQUES E BLINDADOS USA */
                { nome: "M4A3E2 Jumbo", url: "WT - Artes/USA/USA Tanks/JUMBO/JUMBO.html", icone: "WT - Artes/USA/USA Tanks/JUMBO/JUMBO shadow.png" },
                { nome: "M18 Hellcat", url: "WT - Artes/USA/USA Tanks/M18/Hellcat.html", icone: "WT - Artes/USA/USA Tanks/M18/M-18_Hellcat shadow.png" },
                { nome: "M1A2 Abrams", url: "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2-ABRAMS.html", icone: "WT - Artes/USA/USA Tanks/M1A2-Abrams/M1A2-Abrams shadow.png" },
                { nome: "T34 Heavy Tank", url: "WT - Artes/USA/USA Tanks/T34/T34.html", icone: "WT - Artes/USA/USA Tanks/T34/T34 shadow.png" },
                { nome: "T95 Doom Turtle", url: "WT - Artes/USA/USA Tanks/T95/T95.html", icone: "WT - Artes/USA/USA Tanks/T95/T95 for shadow2.png" },

                /*  TANQUES E BLINDADOS ALEMANHA */
                { nome: "Panther D", url: "WT - Artes/GER/Ger Tanks/PANTER D/PANTER-D.html", icone: "WT - Artes/GER/Ger Tanks/PANTER D/PANTER D - Picture.png" },
                { nome: "Tiger H", url: "WT - Artes/GER/Ger Tanks/TIGER H1/TIGER-H1.html", icone: "WT - Artes/GER/Ger Tanks/TIGER H1/WT HER Shadow2 Tiger.png" },
                { nome: "Tiger II V2", url: "WT - Artes/GER/Ger Tanks/TIGER II V2/TIGERII.html", icone: "WT - Artes/GER/Ger Tanks/TIGER II V2/Tiger II Sombra3.png" },
                { nome: "Sturmtiger", url: "WT - Artes/GER/Ger Tanks/STURMTIGER/Sturmtiger.html", icone: "WT - Artes/GER/Ger Tanks/STURMTIGER/Sturmtiger-shadow.png" },
                { nome: "MAUS", url: "WT - Artes/GER/Ger Tanks/MAUS/MAUS.html", icone: "WT - Artes/GER/Ger Tanks/MAUS/MAUS Shadow.png" },

                /*  TANQUES E BLINDADOS RÚSSIA */
                { nome: "T-34-85", url: "WT - Artes/RUS/RUS Tanks/T-34 85/T34-85.html", icone: "WT - Artes/RUS/RUS Tanks/T-34 85/WT RUS Shadow T-34.png" },
                { nome: "KV-1 (ZiS-5)", url: "WT - Artes/RUS/Rus Tanks/KV-1/KV-1.html", icone: "WT - Artes/RUS/RUS Tanks/KV-1/KV-1 shadow.png" },
                { nome: "IS-3", url: "WT - Artes/RUS/Rus Tanks/IS-3/IS-3.html", icone: "WT - Artes/RUS/RUS Tanks/IS-3/IS3 shadow.webp" },
                { nome: "Object 279", url: "WT - Artes/RUS/Rus Tanks/OBJ-279/OBJ-279.html", icone: "WT - Artes/RUS/RUS Tanks/OBJ-279/OBJ-279 shadow.png" },
                { nome: "SU-100", url: "WT - Artes/RUS/Rus Tanks/SU-100/SU-100.html", icone: "WT - Artes/RUS/RUS Tanks/SU-100/SU-100 shadow.png" }
            ];

            let focoAtual = -1;

            // 2. Evento de Digitação (Gera a lista)
            searchInput.addEventListener('input', () => {
                const termo = searchInput.value.toLowerCase();
                searchSuggestions.innerHTML = ''; 
                focoAtual = -1; 

                if (termo.length === 0) {
                    searchSuggestions.style.display = 'none';
                    return;
                }

                const resultados = blindados.filter(tanque => 
                    tanque.nome.toLowerCase().includes(termo)
                );

                if (resultados.length > 0) {
                    searchSuggestions.style.display = 'block';
                    
                    resultados.forEach((tanque) => {
                        const li = document.createElement('li');
                        
                        li.innerHTML = `
                            <a href="${caminhoBase}${tanque.url}" class="suggestion-link">
                                <img src="${caminhoBase}${tanque.icone}" alt="Ícone" class="suggestion-icon">
                                <span>${tanque.nome}</span>
                            </a>
                        `;
                        
                        li.addEventListener('click', () => {
                            window.location.href = `${caminhoBase}${tanque.url}`;
                        });
                        
                        searchSuggestions.appendChild(li);
                    });
                } else {
                    searchSuggestions.style.display = 'none';
                }
            });

            // 3. O Evento de Teclado (Setas e Enter)
            searchInput.addEventListener('keydown', (e) => {
                const itens = searchSuggestions.querySelectorAll('li');
                if (itens.length === 0) return;

                if (e.key === 'ArrowDown') {
                    focoAtual++;
                    if (focoAtual >= itens.length) focoAtual = 0;
                    atualizarFoco(itens);
                } 
                else if (e.key === 'ArrowUp') {
                    focoAtual--;
                    if (focoAtual < 0) focoAtual = itens.length - 1;
                    atualizarFoco(itens);
                } 
                else if (e.key === 'Enter') {
                    e.preventDefault(); 
                    if (focoAtual > -1) {
                        itens[focoAtual].click();
                    }
                }
            });

            function atualizarFoco(itens) {
                itens.forEach(item => item.classList.remove('ativo'));
                itens[focoAtual].classList.add('ativo');
                
                const nomeFocado = itens[focoAtual].querySelector('span').textContent;
                searchInput.value = nomeFocado;
            }

            // Esconde a caixa ao clicar fora
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.style.display = 'none';
                }
            });
        }
    }, 100); // Tenta procurar novamente a cada 100 milissegundos
}