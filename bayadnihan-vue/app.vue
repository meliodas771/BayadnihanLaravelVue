<template>
  <div>
    <template v-if="shouldShowLayout">
      <ClientOnly>
        <div class="main-layout">
          <Sidebar v-if="shouldShowSidebar" />
          <div class="main-content">
            <NuxtPage />
          </div>
        </div>
        <template #fallback>
          <div class="main-layout">
            <div class="main-content">
              <NuxtPage />
            </div>
          </div>
        </template>
      </ClientOnly>
    </template>
    <template v-else>
      <NuxtPage />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useUser } from '~/composables/useUser';
import Sidebar from '~/components/Sidebar.vue';

const route = useRoute();
const { isAuthenticated, isLoading, loadUser } = useUser();
const isMounted = ref(false);

const shouldShowLayout = computed(() => {
  try {
    if (!route) return false;
    const path = route.path || '';
    const name = route.name || '';
    // Don't show layout for landing page routes
    if (path === '/' || path === '/LandingPage' || name === 'index' || name === 'LandingPage') {
      return false;
    }
    // Don't show layout for auth routes
    if (path.startsWith('/auth/') || path.startsWith('/login') || path.startsWith('/register') || 
        path.startsWith('/forgot-password') || path.startsWith('/verify-code') || 
        path.startsWith('/reset-password') || path.startsWith('/google-role-selection')) {
      return false;
    }
    // Show layout for all other routes
    return true;
  } catch (e) {
    return false;
  }
});

// Show sidebar during loading if we're on a route that should show layout
// This prevents the sidebar from disappearing during page refresh
const shouldShowSidebar = computed(() => {
  if (!shouldShowLayout.value) return false;
  
  // Check authentication
  if (isAuthenticated.value) return true;
  
  // During loading, check if there's a token in localStorage
  if (process.client && isLoading.value) {
    const token = localStorage.getItem('auth_token');
    if (token) return true;
  }
  
  return false;
});

onMounted(() => {
  isMounted.value = true;
  // Load user immediately to prevent sidebar flicker
  if (process.client) {
    loadUser();
  }
});
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
}

.main-layout {
  display: flex;
  min-height: 100vh;
}

.main-content {
  margin-left: 250px !important;
  padding: 24px;
  width: calc(100% - 250px) !important;
  min-width: 0 !important;
  flex: 1;
  min-height: 100vh;
  background: #f8f9fc;
  position: relative;
  overflow-x: hidden;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 0 !important;
    padding-bottom: 90px !important;
  }
  
  .main-layout {
    position: relative;
  }
}
</style>

