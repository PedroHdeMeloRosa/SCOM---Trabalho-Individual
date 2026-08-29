function iniciarBusca(caminhoBase) {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');

    // Proteção: Se a barra não existir nesta página, não faz nada
    if (!searchInput || !searchSuggestions) return;

    // Nosso "Banco de Dados" (Adicione os tanques reais que você tem nas pastas)
    const blindados = [
        "M4A3E2 Jumbo",
        "M18 Hellcat",
        "M1A2 Abrams",
        "Tiger H1",
        "Tiger II (P)",
        "Panther D",
        "T-34-85",
        "IS-2",
        "T-90M",
        "Leopard 2A6"
    ];

    searchInput.addEventListener('input', () => {
        const termo = searchInput.value.toLowerCase();
        searchSuggestions.innerHTML = ''; // Limpa as antigas

        if (termo.length === 0) {
            searchSuggestions.style.display = 'none';
            return;
        }

        // ==========================================
        // O REQUISITO: Uso do .filter()
        // ==========================================
        const resultados = blindados.filter(tanque => 
            tanque.toLowerCase().includes(termo)
        );

        if (resultados.length > 0) {
            searchSuggestions.style.display = 'block';
            
            resultados.forEach(tanque => {
                const li = document.createElement('li');
                li.textContent = tanque;
                
                li.addEventListener('click', () => {
                    searchInput.value = tanque;
                    searchSuggestions.style.display = 'none'; 
                });
                
                searchSuggestions.appendChild(li);
            });
        } else {
            searchSuggestions.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
            searchSuggestions.style.display = 'none';
        }
    });
}