import { onUnmounted } from 'vue'

export function useBodyScrollLock() {
    const lock = () => {
        document.body.style.overflow = 'hidden'
    }

    const unlock = () => {
        document.body.style.overflow = ''
    }

    // Ensure we unlock when the component using this composable is unmounted
    onUnmounted(() => {
        unlock()
    })

    return {
        lock,
        unlock
    }
}
