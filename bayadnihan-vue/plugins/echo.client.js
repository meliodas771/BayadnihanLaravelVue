import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export default defineNuxtPlugin(() => {
  // Make Pusher available globally for Echo
  window.Pusher = Pusher;

  // Get auth token from localStorage
  const getAuthToken = () => {
    if (process.client) {
      return localStorage.getItem('auth_token');
    }
    return null;
  };

  // Create Echo instance with Reverb configuration
  const echo = new Echo({
    broadcaster: 'reverb',
    key: '8vduxcelwduemnhcsond',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'http://127.0.0.1:8000/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`,
        Accept: 'application/json',
      },
    },
  });

  // Add connection event listeners
  if (echo.connector && echo.connector.pusher) {
    echo.connector.pusher.connection.bind('error', (err) => {
      console.error('Echo connection error:', err);
    });

    echo.connector.pusher.connection.bind('failed', () => {
      console.error('Echo connection failed');
    });
  }

  // Provide echo instance globally
  return {
    provide: {
      echo,
    },
  };
});

