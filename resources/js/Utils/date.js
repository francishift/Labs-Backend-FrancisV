/**
 * Formats a date string specifically for HTML5 date inputs (YYYY-MM-DD).
 * 
 * It bypasses Javascript's timezone-aware Date object to prevent
 * accidental day shifts caused by local time zone offsets.
 * 
 * @param {string|null} dateString The date string from the server (e.g. 2024-01-01T00:00:00.000000Z)
 * @returns {string} The formatted date (YYYY-MM-DD) or an empty string.
 */
export function formatDateForInput(inputDate) {
    if (!inputDate) return '';

    if (typeof inputDate === 'number') {
        // Asume que es un timestamp Unix configurado a la medianoche (00:00:00) en el backend.
        // Sumamos 12 horas (43200 seg) al timestamp para centrarlo de forma segura en el día UTC
        // garantizando que 'toISOString' siempre corte la fecha correcta en cualquier huso horario del cliente.
        const d = new Date((inputDate + 43200) * 1000);
        return d.toISOString().split('T')[0];
    }

    // By splitting the ISO string to just the YYYY-MM-DD part,
    // we bypass entirely the Javascript Date local timezone conversion pitfalls.
    if (typeof inputDate === 'string') {
        const parts = inputDate.split('T');
        if (parts.length > 0 && parts[0].length === 10) {
            return parts[0]; 
        }
    }

    const date = new Date(inputDate);
    if (isNaN(date.getTime())) return '';

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/**
 * Returns today's date in YYYY-MM-DD format using local time.
 * 
 * @returns {string} The formatted today date (YYYY-MM-DD).
 */
export function getTodayDate() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
