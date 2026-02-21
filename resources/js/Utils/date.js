/**
 * Formats a date string specifically for HTML5 date inputs (YYYY-MM-DD).
 * 
 * It bypasses Javascript's timezone-aware Date object to prevent
 * accidental day shifts caused by local time zone offsets.
 * 
 * @param {string|null} dateString The date string from the server (e.g. 2024-01-01T00:00:00.000000Z)
 * @returns {string} The formatted date (YYYY-MM-DD) or an empty string.
 */
export function formatDateForInput(dateString) {
    if (!dateString) return '';

    // Crear un objeto de fecha.
    const date = new Date(dateString);

    // Comprobar si es una fecha válida
    if (isNaN(date.getTime())) return '';

    // By using local getters (getFullYear, etc.), we get the date as the user sees it in their zone,
    // lo que deshace el desplazamiento introducido por la serialización UTC del lado del servidor de las fechas de medianoche.
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
