import { onUnmounted } from 'vue'

export function useBodyScrollLock() {
    const lock = () => {
        document.body.style.overflow = 'hidden'
    }

    const unlock = () => {
        document.body.style.overflow = ''
    }

    // Asegurar que desbloqueamos cuando el componente que usa este composable se desmonte
    onUnmounted(() => {
        unlock()
    })

    return {
        lock,
        unlock
    }
}
