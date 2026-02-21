import { computed } from 'vue';

export function usePaginationWindow(linksProp, windowSize = 1) {
    return computed(() => {
        const links = linksProp.value ?? [];
        if (!links.length) return [];

        const prev = links[0];
        const next = links[links.length - 1];
        const pages = links.slice(1, -1);

        // Encontrar el índice de la página activa
        let activeIndex = pages.findIndex(link => link.active);

        // Si no hay página activa (ej. fuera de límites), por defecto a la primera página
        if (activeIndex === -1) activeIndex = 0;

        const lastPageIndex = pages.length - 1;

        const startWindow = Math.max(0, activeIndex - windowSize);
        const endWindow = Math.min(lastPageIndex, activeIndex + windowSize);

        // Incluir siempre Primera(0) y Última(lastPageIndex)
        const indicesToShow = new Set([0, lastPageIndex]);

        // Añadir páginas contiguas a la ventana
        for (let i = startWindow; i <= endWindow; i++) {
            indicesToShow.add(i);
        }

        const sortedIndices = Array.from(indicesToShow).sort((a, b) => a - b);

        const out = [prev];

        let lastI = -1;
        for (const i of sortedIndices) {
            // Si el salto es > 1, añadir elipsis (...)
            if (lastI !== -1 && i - lastI > 1) {
                out.push({ url: null, label: '...', active: false });
            }
            out.push(pages[i]);
            lastI = i;
        }

        out.push(next);
        return out;
    });
}
