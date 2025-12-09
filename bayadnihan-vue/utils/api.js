// API Service - Centralized API calls to Laravel backend

export const useAPI = () => {
  const config = useRuntimeConfig();
  const API_BASE_URL = config.public.apiBaseUrl || 'https://5dab011d317e22.lhr.life/api';

  // Helper function to get auth token
  const getAuthToken = () => {
    if (process.client) {
      return localStorage.getItem('auth_token');
    }
    return null;
  };

  // Helper function to set auth token
  const setAuthToken = (token) => {
    if (process.client) {
      if (token) {
        localStorage.setItem('auth_token', token);
      } else {
        localStorage.removeItem('auth_token');
      }
    }
  };

  // Helper function to get headers
  const getHeaders = (includeAuth = true, contentType = 'application/json') => {
    const headers = {
      'Accept': 'application/json',
    };

    // Only set Content-Type if it's explicitly provided (not null)
    // When using FormData, browser will set Content-Type with boundary automatically
    if (contentType) {
      headers['Content-Type'] = contentType;
    }

    if (includeAuth) {
      const token = getAuthToken();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
    }

    return headers;
  };

  // Generic API request function
  const apiRequest = async (endpoint, options = {}) => {
    const { method = 'GET', body, includeAuth = true, contentType } = options;
    
    // Default to JSON if not specified and body is an object (not FormData)
    const finalContentType = contentType || (body instanceof FormData ? null : 'application/json');
    
    const config = {
      method,
      headers: getHeaders(includeAuth, finalContentType),
    };

    if (body) {
      if (finalContentType === 'application/json' || (!finalContentType && !(body instanceof FormData))) {
        config.body = JSON.stringify(body);
      } else {
        config.body = body; // For FormData or other types
      }
    }

    try {
      // Debug logging for login requests
      if (endpoint === '/login' && method === 'POST') {
        console.log('Login request:', { endpoint: `${API_BASE_URL}${endpoint}`, body: config.body, headers: config.headers });
      }
      
      const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
      const data = await response.json();

      if (!response.ok) {
        // Handle Laravel validation errors (422)
        if (response.status === 422 && data.errors) {
          const errorMessages = Object.values(data.errors).flat();
          throw new Error(errorMessages.join('. ') || data.message || 'Validation error');
        }
        // Handle other errors
        throw new Error(data.error || data.message || data.errors || 'An error occurred');
      }

      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  };

  // Auth API
  const authAPI = {
    login: async (email, password, remember = false) => {
      // Ensure email and password are provided
      if (!email || !password) {
        throw new Error('Email and password are required');
      }
      
      const data = await apiRequest('/login', {
        method: 'POST',
        body: { email: email.trim(), password, remember },
        includeAuth: false,
      });
      
      if (data.token) {
        setAuthToken(data.token);
        if (data.user && process.client) {
          localStorage.setItem('user', JSON.stringify(data.user));
        }
      }
      
      return data;
    },

    register: async (userData) => {
      const data = await apiRequest('/register', {
        method: 'POST',
        body: userData,
        includeAuth: false,
      });
      
      return data;
    },

    logout: async () => {
      try {
        await apiRequest('/logout', {
          method: 'POST',
        });
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        setAuthToken(null);
        if (process.client) {
          localStorage.removeItem('user');
        }
      }
    },

    checkAuth: async () => {
      try {
        // Include auth token if available to check authentication status
        const data = await apiRequest('/auth/check', {
          includeAuth: true, // Changed to true to send token if available
        });
        return data;
      } catch (error) {
        // If there's an error (like 401), user is not authenticated
        return { authenticated: false };
      }
    },

    forgotPassword: async (email) => {
      return await apiRequest('/forgot-password', {
        method: 'POST',
        body: { email },
        includeAuth: false,
      });
    },

    verifyCode: async (email, code) => {
      return await apiRequest('/verify-code', {
        method: 'POST',
        body: { email, code },
        includeAuth: false,
      });
    },

    resendCode: async (email) => {
      return await apiRequest('/resend-code', {
        method: 'POST',
        body: { email },
        includeAuth: false,
      });
    },

    resetPassword: async (email, code, password, password_confirmation) => {
      return await apiRequest('/reset-password', {
        method: 'POST',
        body: { email, code, password, password_confirmation },
        includeAuth: false,
      });
    },
  };

  // Tasks API
  const tasksAPI = {
    getAll: async () => {
      return await apiRequest('/tasks');
    },

    getById: async (id) => {
      return await apiRequest(`/tasks/${id}`);
    },

    getEdit: async (id) => {
      return await apiRequest(`/tasks/${id}/edit`);
    },

    create: async (taskData) => {
      const formData = new FormData();
      
      // Always append required fields
      formData.append('title', taskData.title || '');
      formData.append('description', taskData.description || '');
      formData.append('price', taskData.price !== null && taskData.price !== undefined ? taskData.price.toString() : '0');
      formData.append('payment_method', taskData.payment_method || 'cash');
      formData.append('is_draft', taskData.is_draft !== undefined ? taskData.is_draft.toString() : '0');
      
      // Optional fields
      if (taskData.category) {
        formData.append('category', taskData.category);
      }
      
      // Attachment
      if (taskData.attachment && taskData.attachment instanceof File) {
        formData.append('attachment', taskData.attachment);
      }

      // Debug: Log FormData contents
      console.log('FormData contents:');
      for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
      }

      return await apiRequest('/tasks', {
        method: 'POST',
        body: formData,
        contentType: null, // Let browser set Content-Type with boundary
      });
    },

    update: async (id, taskData) => {
      const formData = new FormData();
      
      Object.keys(taskData).forEach(key => {
        if (key === 'attachment' && taskData[key] instanceof File) {
          formData.append('attachment', taskData[key]);
        } else if (taskData[key] !== null && taskData[key] !== undefined) {
          formData.append(key, taskData[key]);
        }
      });

      return await apiRequest(`/tasks/${id}`, {
        method: 'POST',
        body: formData,
        contentType: null,
      });
    },

    publish: async (id) => {
      return await apiRequest(`/tasks/${id}/publish`, {
        method: 'POST',
      });
    },

    apply: async (id) => {
      return await apiRequest(`/tasks/${id}/apply`, {
        method: 'POST',
      });
    },

    acceptApplication: async (taskId, applicationId) => {
      return await apiRequest(`/tasks/${taskId}/applications/${applicationId}/accept`, {
        method: 'POST',
      });
    },

    updateStatus: async (id, status) => {
      return await apiRequest(`/tasks/${id}/status`, {
        method: 'PATCH',
        body: { status },
      });
    },

    startTask: async (id) => {
      return await apiRequest(`/tasks/${id}/start`, {
        method: 'PATCH',
      });
    },

    pauseTask: async (id, reason) => {
      return await apiRequest(`/tasks/${id}/pause`, {
        method: 'PATCH',
        body: { reason },
      });
    },

    cancel: async (id) => {
      return await apiRequest(`/tasks/${id}/cancel`, {
        method: 'POST',
      });
    },

    getAttachment: (taskId) => {
      const token = getAuthToken();
      return `${API_BASE_URL}/tasks/${taskId}/attachment${token ? `?token=${token}` : ''}`;
    },
  };

  // User API
  const userAPI = {
    getProfile: async () => {
      return await apiRequest('/profile');
    },

    updateProfile: async (profileData) => {
      const formData = new FormData();
      
      // Always append profile_pic if it's a File
      if (profileData.profile_pic && profileData.profile_pic instanceof File) {
        formData.append('profile_pic', profileData.profile_pic);
      }
      
      // Handle other fields
      Object.keys(profileData).forEach(key => {
        // Skip profile_pic as we already handled it
        if (key === 'profile_pic') return;
        
        if (profileData[key] !== null && profileData[key] !== undefined && profileData[key] !== '') {
          // Convert all values to strings for FormData
          if (typeof profileData[key] === 'number') {
            formData.append(key, profileData[key].toString());
          } else if (typeof profileData[key] === 'boolean') {
            formData.append(key, profileData[key] ? '1' : '0');
          } else {
            formData.append(key, profileData[key]);
          }
        }
      });

      // Debug: Log FormData contents
      console.log('Profile update FormData contents:');
      for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name + ' (' + pair[1].type + ')' : pair[1]));
      }

      return await apiRequest('/profile', {
        method: 'POST',
        body: formData,
        contentType: null,
      });
    },

    getPublicProfile: async (userId) => {
      return await apiRequest(`/user/${userId}`);
    },

    getMyTasks: async () => {
      return await apiRequest('/my-tasks');
    },

    updateTaskCompletion: async (taskId, completionPercentage) => {
      return await apiRequest(`/tasks/${taskId}/completion`, {
        method: 'PATCH',
        body: { completion_percentage: completionPercentage },
      });
    },

    updateUsername: async (username, email) => {
      return await apiRequest('/user/update-username', {
        method: 'POST',
        body: { username, email },
      });
    },
  };

  // Notifications API
  const notificationsAPI = {
    getAll: async () => {
      return await apiRequest('/notifications');
    },

    markRead: async (id) => {
      return await apiRequest(`/notifications/${id}/read`, {
        method: 'POST',
      });
    },

    markAllRead: async () => {
      return await apiRequest('/notifications/read-all', {
        method: 'POST',
      });
    },

    delete: async (id) => {
      return await apiRequest(`/notifications/${id}`, {
        method: 'DELETE',
      });
    },

    deleteAll: async () => {
      return await apiRequest('/notifications', {
        method: 'DELETE',
      });
    },
  };

  // Messages API
  const messagesAPI = {
    send: async (taskId, message, image = null) => {
      const formData = new FormData();
      if (message && message.trim()) {
        formData.append('content', message);
      }
      if (image instanceof File) {
        formData.append('image', image);
      }

      return await apiRequest(`/tasks/${taskId}/chat`, {
        method: 'POST',
        body: formData,
        contentType: null,
      });
    },

    getMessages: async (taskId) => {
      return await apiRequest(`/tasks/${taskId}/chat/messages`);
    },

    markAsRead: async (taskId) => {
      return await apiRequest(`/tasks/${taskId}/chat/mark-read`, {
        method: 'POST',
      });
    },
  };

  // Feedback API
  const feedbackAPI = {
    create: async (taskId) => {
      return await apiRequest(`/feedbacks/task/${taskId}/create`);
    },

    store: async (taskId, feedbackData) => {
      return await apiRequest(`/feedbacks/task/${taskId}`, {
        method: 'POST',
        body: feedbackData,
      });
    },

    storeDoerFeedback: async (taskId, feedbackData) => {
      return await apiRequest(`/feedbacks/doer/task/${taskId}`, {
        method: 'POST',
        body: feedbackData,
      });
    },

    get: async (taskId) => {
      return await apiRequest(`/feedbacks/task/${taskId}`);
    },
  };

  // Reports API
  const reportsAPI = {
    create: async () => {
      return await apiRequest('/report');
    },

    store: async (reportData) => {
      return await apiRequest('/report', {
        method: 'POST',
        body: reportData,
      });
    },

    getUserTasks: async (username) => {
      // The backend route in routes/api.php is defined as '/api/user-tasks'
      // Since routes in api.php are automatically prefixed with /api, the actual endpoint is /api/api/user-tasks
      // But let's try both: first /api/user-tasks, if that fails we know the backend route needs to be fixed
      return await apiRequest(`/api/user-tasks?username=${encodeURIComponent(username)}`);
    },

    getInteractedUsers: async () => {
      return await apiRequest('/report/interacted-users');
    },
  };

  // Google Auth API
  const googleAuthAPI = {
    redirect: () => {
      const config = useRuntimeConfig();
      const API_BASE_URL = config.public.apiBaseUrl || 'http://localhost:8000/api';
      if (process.client) {
        window.location.href = `${API_BASE_URL}/auth/google`;
      }
    },

    completeRegistration: async (role, googleUserData) => {
      return await apiRequest('/auth/google/complete-registration', {
        method: 'POST',
        body: {
          role,
          google_id: googleUserData?.google_id || googleUserData?.googleId,
          email: googleUserData?.email,
          name: googleUserData?.name,
          avatar: googleUserData?.avatar
        },
        includeAuth: false,
      });
    },
  };

  return {
    API_BASE_URL,
    getAuthToken,
    setAuthToken,
    apiRequest,
    authAPI,
    tasksAPI,
    userAPI,
    notificationsAPI,
    messagesAPI,
    feedbackAPI,
    reportsAPI,
    googleAuthAPI,
  };
};

