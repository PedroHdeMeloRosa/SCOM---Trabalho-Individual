// ==========================================
// ORQUESTRADOR TÁTICO DE MÓDULOS (PRODUÇÃO)
// ==========================================
async function injetarMenu(caminhoBase) {
    
    const carregarScript = (url) => {
        return new Promise((resolve) => {
            const script = document.createElement('script');
            
            // Aponta diretamente para a nova pasta 'js' e removemos o anti-cache para máxima performance
            script.src = `${caminhoBase}js/${url}`;
            
            script.onload = resolve;
            
            script.onerror = () => {
                console.error(`Falha tática: Não foi possível carregar o módulo ${url}`);
                resolve(); 
            };
            
            document.head.appendChild(script);
        });
    };

    // 1. Carrega todos os arquivos da gaveta correta
    await carregarScript('ui.js');
    await carregarScript('clock.js');
    await carregarScript('login.js');
    await carregarScript('scroll.js');
    await carregarScript('carousel.js');
    await carregarScript('search.js');
    
    // Se o seu search.js existir, descomente a linha abaixo:
    // await carregarScript('search.js');

    // 2. Executa as funções principais (Garantia caso o DOMContentLoaded tenha disparado antes do download terminar)
    if (typeof injetarInterface === 'function') injetarInterface(caminhoBase);
    if (typeof iniciarRelogio === 'function') iniciarRelogio();
    if (typeof iniciarLogin === 'function') iniciarLogin(caminhoBase);
    if (typeof iniciarScrollSuave === 'function') iniciarScrollSuave(caminhoBase);
    if (typeof iniciarCarrossel === 'function') iniciarCarrossel();
    if (typeof iniciarToggleVisualizacao === 'function') iniciarToggleVisualizacao();
    if (typeof iniciarBusca === 'function') iniciarBusca(caminhoBase);
    // Se usar a busca:
    // if (typeof iniciarBusca === 'function') iniciarBusca(caminhoBase);
}