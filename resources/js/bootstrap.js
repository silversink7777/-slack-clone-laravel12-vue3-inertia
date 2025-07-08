import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['Accept'] = 'application/json';

// CSRFトークンを自動付与
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// SanctumのCSRFトークンを事前取得
const getSanctumCsrfToken = async () => {
    try {
        await axios.get('/sanctum/csrf-cookie');
    } catch (error) {
        console.error('Failed to get Sanctum CSRF token:', error);
    }
};

// ページ読み込み時にSanctum CSRFトークンを取得
getSanctumCsrfToken();

// リクエストインターセプターを追加してCSRFトークンを確認
window.axios.interceptors.request.use(function (config) {
    // POST, PUT, DELETEリクエストでCSRFトークンを確認
    if (['post', 'put', 'delete', 'patch'].includes(config.method.toLowerCase())) {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token.content;
        }
    }
    return config;
}, function (error) {
    return Promise.reject(error);
});
