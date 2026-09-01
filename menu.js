async function injetarMenu(caminhoBase) {
    // 1. Carregador de Módulos (COM SISTEMA ANTI-CACHE)
    const carregarScript = (url) => {
        return new Promise((resolve) => {
            const script = document.createElement('script');
            // O timestamp no final da URL faz o navegador achar que é um arquivo novo toda vez
            script.src = `${caminhoBase}${url}?v=${new Date().getTime()}`;
            script.onload = resolve;
            
            // Tratamento de erro caso o script falhe ao carregar
            script.onerror = () => {
                console.error(`Falha tática: Não foi possível carregar o módulo ${url}`);
                resolve(); // Resolve mesmo com erro para não travar o resto do site
            };
            
            document.head.appendChild(script);
        });
    };

    // 2. Carrega todos os arquivos que separamos
    await carregarScript('ui.js');
    await carregarScript('clock.js');
    await carregarScript('login.js');
    await carregarScript('scroll.js');
    await carregarScript('search.js');
    await carregarScript('carousel.js');

    // 3. Executa as funções principais de cada um (com proteção contra erros)
    if (typeof injetarInterface === 'function') injetarInterface(caminhoBase);
    if (typeof iniciarRelogio === 'function') iniciarRelogio();
    if (typeof iniciarLogin === 'function') iniciarLogin(caminhoBase);
    if (typeof iniciarScrollSuave === 'function') iniciarScrollSuave(caminhoBase);
    if (typeof iniciarBusca === 'function') iniciarBusca(caminhoBase);
    if (typeof iniciarCarrossel === 'function') iniciarCarrossel();
}