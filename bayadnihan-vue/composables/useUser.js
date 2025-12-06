// User composable - replaces UserContext
import { ref, computed, onMounted } from 'vue';
import { useAPI } from '~/utils/api';

export const useUser = () => {
  const { getAuthToken, setAuthToken } = useAPI();
  const user = ref(null);
  const isLoading = ref(true);

  // Load user from localStorage on mount
  const loadUser = () => {
    if (process.client) {
      try {
        const storedUser = localStorage.getItem('user');
        const token = getAuthToken();
        
        if (storedUser && token) {
          user.value = JSON.parse(storedUser);
        }
      } catch (error) {
        console.error('Error loading user:', error);
      } finally {
        isLoading.value = false;
      }
    } else {
      isLoading.value = false;
    }
  };

  onMounted(() => {
    loadUser();
  });

  const login = (userData, token) => {
    user.value = userData;
    setAuthToken(token);
    if (process.client) {
      localStorage.setItem('user', JSON.stringify(userData));
    }
  };

  const logout = () => {
    user.value = null;
    setAuthToken(null);
    if (process.client) {
      localStorage.removeItem('user');
    }
  };

  const updateUser = (userData) => {
    user.value = userData;
    if (process.client) {
      localStorage.setItem('user', JSON.stringify(userData));
    }
  };

  const isAuthenticated = computed(() => {
    return !!user.value && !!getAuthToken();
  });

  return {
    user,
    isLoading,
    login,
    logout,
    updateUser,
    isAuthenticated,
    loadUser,
  };
};

