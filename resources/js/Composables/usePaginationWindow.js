import { computed } from 'vue';

export function usePaginationWindow(linksProp, windowSize = 1) {
    return computed(() => {
        const links = linksProp.value ?? [];
        if (!links.length) return [];

        const prev = links[0];
        const next = links[links.length - 1];
        const pages = links.slice(1, -1);

        // Find active page index
        let activeIndex = pages.findIndex(link => link.active);

        // If no active page (e.g. out of bounds), default to first page
        if (activeIndex === -1) activeIndex = 0;

        const lastPageIndex = pages.length - 1;

        const startWindow = Math.max(0, activeIndex - windowSize);
        const endWindow = Math.min(lastPageIndex, activeIndex + windowSize);

        // Always include First(0) and Last(lastPageIndex)
        const indicesToShow = new Set([0, lastPageIndex]);

        // Add window neighbors
        for (let i = startWindow; i <= endWindow; i++) {
            indicesToShow.add(i);
        }

        const sortedIndices = Array.from(indicesToShow).sort((a, b) => a - b);

        const out = [prev];

        let lastI = -1;
        for (const i of sortedIndices) {
            // If gap > 1, add ellipsis
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
