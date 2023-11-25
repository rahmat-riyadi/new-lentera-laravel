/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

import Toastify from 'toastify-js'
import "toastify-js/src/toastify.css"

function Toast(){

    this.toastConfig = {
        duration: 2000,
        close: true,
        gravity: "bottom", // `top` or `bottom`
        position: "right", // `left`, `center` or `right`
        stopOnFocus: true, // Prevents dismissing of toast on hover
    }

    this.successToast = Toastify({
        ...this.toastConfig,
        style: {
            background: "#fff",
            borderBottom: '3px solid #36B37E',
            boxShadow: 'rgba(9, 30, 66, 0.25) 0px 4px 8px -2px, rgba(9, 30, 66, 0.08) 0px 0px 0px 1px',
            color: '#36B37E',
            width: '250px',
            fontWeight: '600',
            fontSize: '14px'
        }
    })

    this.errorToast =  Toastify({
        ...this.toastConfig,
        style: {
            backgroundColor: "green",
        },
    })

    this.show = function(type, message){
        if(type == 'success'){
            this.successToast.options.text = message
            this.successToast.showToast()
        } else {
            this.errorToast.options.text = message
            this.errorToast.showToast()
        }

    }

}

window.Toast = Toast