const fs = require('fs');
const path = require('path');

// Função para vasculhar todas as pastas do projeto
function varrerPasta(diretorio) {
    const arquivos = fs.readdirSync(diretorio);
    
    arquivos.forEach(arquivo => {
        const caminhoTotal = path.join(diretorio, arquivo);
        
        // Se for pasta, entra nela. Se for HTML, corrige.
        if (fs.statSync(caminhoTotal).isDirectory()) {
            varrerPasta(caminhoTotal);
        } else if (caminhoTotal.endsWith('.html')) {
            corrigirArquivo(caminhoTotal);
        }
    });
}

function corrigirArquivo(caminho) {
    let conteudoOriginal = fs.readFileSync(caminho, 'utf8');
    
    // Procura por src="..." ou href="..." e troca os espaços por %20
    let novoConteudo = conteudoOriginal.replace(/(src|href)="([^"]+)"/g, (match, atributo, url) => {
        return `${atributo}="${url.replace(/ /g, '%20')}"`;
    });

    // Se houve mudança, salva o arquivo
    if (conteudoOriginal !== novoConteudo) {
        fs.writeFileSync(caminho, novoConteudo, 'utf8');
        console.log(`[✔ CORRIGIDO] ${caminho}`);
    }
}

console.log("Iniciando varredura tática nos arquivos HTML...");
varrerPasta('./');
console.log("Missão cumprida! Todos os espaços ilegais foram neutralizados.");