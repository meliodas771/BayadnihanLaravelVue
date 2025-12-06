<template>
  <div :style="loadingStyle">
    <div>Processing authentication...</div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';

const route = useRoute();

const loadingStyle = {
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  minHeight: '100vh',
  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
  color: 'white',
  fontSize: '18px'
};

onMounted(() => {
  if (process.client) {
    // Get the data from query parameter
    const data = route.query.data;
    
    if (data) {
      // Decode the base64 data and store in sessionStorage
      try {
        const decodedData = atob(decodeURIComponent(data));
        sessionStorage.setItem('google_user', decodedData);
        navigateTo('/auth/google-role-selection', { replace: true });
      } catch (e) {
        console.error('Error processing Google data:', e);
        navigateTo('/auth/login?error=' + encodeURIComponent('Invalid authentication data. Please try again.'), { replace: true });
      }
    } else {
      // If no data, check sessionStorage
      const storedData = sessionStorage.getItem('google_user');
      if (storedData) {
        navigateTo('/auth/google-role-selection', { replace: true });
      } else {
        navigateTo('/auth/login?error=' + encodeURIComponent('No authentication data found. Please try logging in again.'), { replace: true });
      }
    }
  }
});
</script>

