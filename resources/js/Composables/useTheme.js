import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

export function useTheme() {
    const theme = ref('system')
    const isDark = ref(false)
    const isLoading = ref(false)

    // システムのダークモード設定を検出
    const getSystemTheme = () => {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    // テーマを適用
    const applyTheme = (newTheme) => {
        const actualTheme = newTheme === 'system' ? getSystemTheme() : newTheme
        
        if (actualTheme === 'dark') {
            document.documentElement.classList.add('dark')
            isDark.value = true
        } else {
            document.documentElement.classList.remove('dark')
            isDark.value = false
        }
    }

    // 認証状態を確認
    const checkAuthStatus = async () => {
        try {
            const response = await axios.get('/api/auth/check')
            console.log('Auth status:', response.data)
            return response.data.authenticated
        } catch (error) {
            console.error('Failed to check auth status:', error)
            return false
        }
    }

    // サーバーからテーマ設定を取得
    const loadTheme = async () => {
        try {
            isLoading.value = true
            console.log('Loading theme...')
            
            // 認証状態を確認
            const isAuthenticated = await checkAuthStatus()
            console.log('Is authenticated:', isAuthenticated)
            
            const response = await axios.get('/theme')
            console.log('Theme loaded successfully:', response.data)
            theme.value = response.data.theme
            applyTheme(theme.value)
        } catch (error) {
            console.error('Failed to load theme:', error)
            console.error('Error response:', error.response?.data)
            console.error('Error status:', error.response?.status)
            // デフォルトはシステム設定
            theme.value = 'system'
            applyTheme('system')
        } finally {
            isLoading.value = false
        }
    }

    // テーマを更新
    const updateTheme = async (newTheme) => {
        try {
            isLoading.value = true
            console.log('Updating theme to:', newTheme)
            
            // 認証状態を確認
            const isAuthenticated = await checkAuthStatus()
            console.log('Is authenticated:', isAuthenticated)
            
            await axios.post('/theme', { theme: newTheme })
            console.log('Theme updated successfully')
            theme.value = newTheme
            applyTheme(newTheme)
        } catch (error) {
            console.error('Failed to update theme:', error)
            console.error('Error response:', error.response?.data)
            console.error('Error status:', error.response?.status)
            throw error
        } finally {
            isLoading.value = false
        }
    }

    // システムのテーマ変更を監視
    const watchSystemTheme = () => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
        mediaQuery.addEventListener('change', () => {
            if (theme.value === 'system') {
                applyTheme('system')
            }
        })
    }

    // テーマの変更を監視
    watch(theme, (newTheme) => {
        applyTheme(newTheme)
    })

    onMounted(() => {
        loadTheme()
        watchSystemTheme()
    })

    return {
        theme,
        isDark,
        isLoading,
        updateTheme,
        loadTheme
    }
} 