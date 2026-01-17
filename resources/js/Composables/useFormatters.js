export function useFormatters() {
    const formatCurrency = (value) => {
        if (value === null || value === undefined) return '-'
        const number = typeof value === 'string' ? parseFloat(value) : value

        return new Intl.NumberFormat('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(number) + ' €'
    }

    const formatDate = (dateString, format = 'short') => {
        if (!dateString) return '-'
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return '-'

        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: format === 'long' ? 'long' : '2-digit',
            day: '2-digit'
        })
    }

    const formatDuration = (minutes) => {
        if (minutes === null || minutes === undefined) return '-'
        if (minutes === 0) return '0 min'

        const hours = Math.floor(minutes / 60)
        const remainingMinutes = minutes % 60

        let result = ''
        if (hours > 0) result += `${hours}h `
        if (remainingMinutes > 0 || hours === 0) result += `${remainingMinutes}min`

        return result.trim()
    }

    const truncate = (text, length = 10, suffix = '...') => {
        if (!text || text.length <= length) return text
        return text.substring(0, length) + suffix
    }

    return {
        formatCurrency,
        formatDate,
        formatDuration,
        truncate
    }
}
