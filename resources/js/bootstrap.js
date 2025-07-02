import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRFトークンを自動付与
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

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
