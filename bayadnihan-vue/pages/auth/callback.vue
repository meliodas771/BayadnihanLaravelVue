<template>
  <div :style="containerStyle">
    <div v-if="isLoading" :style="loadingStyle">
      <div :style="spinnerStyle">⏳</div>
      <p :style="textStyle">Completing authentication...</p>
    </div>
    <div v-else-if="error" :style="errorStyle">
      <div :style="errorIconStyle">❌</div>
      <p :style="textStyle">{{ error }}</p>
      <NuxtLink to="/auth/login" :style="linkStyle">Go to Login</NuxtLink>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

const router = useRouter();
const route = useRoute();
const { login } = useUser();

const isLoading = ref(true);
const error = ref(null);

onMounted(async () => {
  try {
    const token = route.query.token;
    const success = route.query.success;

    if (!token || success !== '1') {
      error.value = 'Invalid authentication token. Please try logging in again.';
      isLoading.value = false;
      return;
    }

    // Store the token
    if (process.client) {
      localStorage.setItem('auth_token', token);
    }

    // Fetch user data
    const { authAPI } = useAPI();
    const userResponse = await authAPI.checkAuth();
    
    if (userResponse.user) {
      login(userResponse.user, token);
      // Redirect to tasks page
      router.push('/tasks');
    } else {
      error.value = 'Failed to fetch user data. Please try logging in again.';
      isLoading.value = false;
    }
  } catch (err) {
    console.error('Auth callback error:', err);
    error.value = 'Authentication failed. Please try logging in again.';
    isLoading.value = false;
  }
});

const containerStyle = {
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  minHeight: '100vh',
  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
};

const loadingStyle = {
  textAlign: 'center',
  color: 'white'
};

const errorStyle = {
  textAlign: 'center',
  color: 'white',
  background: 'rgba(255, 255, 255, 0.1)',
  padding: '40px',
  borderRadius: '12px',
  maxWidth: '400px'
};

const spinnerStyle = {
  fontSize: '48px',
  marginBottom: '20px',
  animation: 'spin 1s linear infinite'
};

const errorIconStyle = {
  fontSize: '48px',
  marginBottom: '20px'
};

const textStyle = {
  fontSize: '18px',
  marginBottom: '20px'
};

const linkStyle = {
  display: 'inline-block',
  padding: '12px 24px',
  background: 'white',
  color: '#667eea',
  textDecoration: 'none',
  borderRadius: '8px',
  fontWeight: '600',
  marginTop: '20px'
};

useHead({
  style: [
    {
      children: `
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `
    }
  ]
});
</script>

