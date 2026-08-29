async function injetarMenu(caminhoBase) {
    // 1. Função para carregar os módulos independentes
    const carregarScript = (url) => {
        return new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = caminhoBase + url;
            script.onload = resolve;
            document.head.appendChild(script);
        });
    };

    // 2. Carrega todos os arquivos que separamos
    await carregarScript('ui.js');
    await carregarScript('clock.js');
    await carregarScript('login.js');
    await carregarScript('scroll.js');

    // 3. Executa as funções principais de cada um
    injetarInterface(caminhoBase);
    iniciarRelogio();
    iniciarLogin(caminhoBase);
    iniciarScrollSuave(caminhoBase);
}