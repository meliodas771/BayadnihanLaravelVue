<template>
  <div>
    <div style="max-width: 700px; margin: 0 auto; padding: 24px;">
      <div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h1 style="font-size: 28px; font-weight: 700; color: #2e3a59; margin-bottom: 8px;">
          Report User
        </h1>
        <p style="color: #858796; margin-bottom: 32px; font-size: 14px;">
          Report inappropriate behavior or violations
        </p>
        
        <!-- Success Message -->
        <div v-if="successMessage" style="padding: 16px; border-radius: 8px; margin-bottom: 24px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
          <div>{{ successMessage }}</div>
        </div>
        
        <!-- Error Messages -->
        <div v-if="errors.length > 0" style="padding: 16px; border-radius: 8px; margin-bottom: 24px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
          <div v-for="(error, index) in errors" :key="index">{{ error }}</div>
        </div>
        
        <!-- Skeleton Loading State -->
        <div v-if="isLoadingInteractedUsers">
          <div :style="skeletonTitleStyle"></div>
          <div :style="skeletonFormGroupStyle">
            <div :style="skeletonLabelStyle"></div>
            <div :style="skeletonInputStyle"></div>
          </div>
          <div :style="skeletonFormGroupStyle">
            <div :style="skeletonLabelStyle"></div>
            <div :style="skeletonInputStyle"></div>
          </div>
          <div :style="skeletonFormGroupStyle">
            <div :style="skeletonLabelStyle"></div>
            <div :style="skeletonTextareaStyle"></div>
          </div>
          <div :style="skeletonButtonStyle"></div>
        </div>
        
        <!-- Form -->
        <form v-if="!isLoadingInteractedUsers" @submit.prevent="handleSubmit">
          <!-- Username Selection -->
          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 600; color: #2e3a59; margin-bottom: 8px; font-size: 14px;">
              Username *
            </label>
            <select 
              v-model="formData.reported_username"
              @change="handleUserSelect"
              style="width: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 8px; font-size: 14px; background-color: #ffffff; color: #000000;"
              required
            >
              <option value="">Select a user to report</option>
              <option v-for="u in interactedUsers" :key="u.id" :value="u.username">
                {{ u.username }}
              </option>
            </select>
          </div>
          
          <!-- User Preview -->
          <div v-if="user" style="background: #f8f9fc; padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
            <img 
              v-if="user.profile_pic" 
              :src="`http://localhost:8000/storage/profile_pics/${user.profile_pic}`" 
              alt="Profile"
              style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"
            />
            <div>
              <strong>{{ user.username }}</strong>
            </div>
          </div>
          
          <!-- Related Task (Optional) -->
          <div v-if="tasks.length > 0" style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 600; color: #2e3a59; margin-bottom: 8px; font-size: 14px;">
              Related Task (Optional)
            </label>
            <select 
              v-model="formData.task_id" 
              style="width: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 8px; font-size: 14px; background-color: #ffffff; color: #000000;"
            >
              <option value="">Select a task</option>
              <option v-for="task in tasks" :key="task.id" :value="task.id">
                {{ task.title }} - {{ task.status }}
              </option>
            </select>
          </div>
          
          <!-- Report Type -->
          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 600; color: #2e3a59; margin-bottom: 8px; font-size: 14px;">
              User Role *
            </label>
            <p style="color: #858796; font-size: 13px; margin-bottom: 8px;">
              What role was this user in when the issue occurred?
            </p>
            <select 
              v-model="formData.report_type" 
              style="width: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 8px; font-size: 14px; background-color: #ffffff; color: #000000;"
              required
            >
              <option value="">Select user role</option>
              <option value="poster">Poster (User who posted a task)</option>
              <option value="doer">Doer (User who completed/did a task)</option>
            </select>
          </div>
          
          <!-- Reason -->
          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 600; color: #2e3a59; margin-bottom: 8px; font-size: 14px;">
              Reason *
            </label>
            <input 
              type="text"
              v-model="formData.reason"
              placeholder="Brief reason for reporting"
              style="width: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 8px; font-size: 14px; background-color: #ffffff; color: #000000;"
              required
            />
          </div>
          
          <!-- Description -->
          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 600; color: #2e3a59; margin-bottom: 8px; font-size: 14px;">
              Description *
            </label>
            <textarea 
              v-model="formData.description"
              placeholder="Provide detailed description of the issue..."
              style="width: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 8px; font-size: 14px; min-height: 120px; resize: vertical; background-color: #ffffff; color: #000000;"
              required
            />
          </div>
          
          <!-- Form Actions -->
          <div style="display: flex; gap: 12px; margin-top: 32px;">
            <NuxtLink 
              to="/tasks" 
              style="flex: 1; padding: 14px; background: #858796; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px; text-decoration: none; text-align: center; display: block;"
            >
              Cancel
            </NuxtLink>
            <button 
              type="button" 
              @click="showConfirmationModal = true"
              :disabled="isLoading"
              style="flex: 1; padding: 14px; background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px;"
            >
              Submit Report
            </button>
          </div>
        </form>
        
        <!-- No Users Message -->
        <div v-if="!isLoadingInteractedUsers && interactedUsers.length === 0 && errors.length === 0" style="text-align: center; padding: 20px; color: #858796; margin-top: 20px;">
          <p>No users available to report. You need to interact with users first (via tasks or messages).</p>
          <NuxtLink 
            to="/tasks" 
            style="display: inline-block; margin-top: 10px; padding: 14px; background: #858796; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px; text-decoration: none;"
          >
            Go to Tasks
          </NuxtLink>
        </div>
      </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div 
      v-if="showConfirmationModal"
      :style="modalOverlayStyle"
      @click="showConfirmationModal = false"
    >
      <div 
        :style="modalContentStyle"
        @click.stop
      >
        <div :style="modalHeaderStyle">
          <h3 :style="modalHeaderH3Style">Confirm Report Submission</h3>
          <button 
            :style="modalCloseStyle"
            @click="showConfirmationModal = false"
            @mouseenter="(e) => e.target.style.background = '#f8f9fc'"
            @mouseleave="(e) => e.target.style.background = 'transparent'"
          >
            &times;
          </button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="modalBodyPStyle">
            Are you sure you want to submit this report?<br>
            <strong>User:</strong> {{ formData.reported_username || 'Not selected' }}<br>
            <strong>Role:</strong> {{ formData.report_type === 'poster' ? 'Poster' : formData.report_type === 'doer' ? 'Doer' : 'Not selected' }}
          </p>
          <p style="margin: 12px 0 0 0; color: #5a5c69; font-size: 15px; line-height: 1.6;">
            Once submitted, our admin team will review your report. You cannot undo this action.
          </p>
        </div>
        <div :style="modalFooterStyle">
          <button 
            :style="modalCancelButtonStyle"
            @click="showConfirmationModal = false"
            :disabled="isLoading"
          >
            Cancel
          </button>
          <button 
            :style="modalConfirmButtonStyle"
            @click="confirmSubmit"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Submitting...' : 'Yes, Submit Report' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAPI } from '~/utils/api';
import { useUser } from '~/composables/useUser';

const router = useRouter();
const route = useRoute();
const { reportsAPI } = useAPI();
const { isAuthenticated, isLoading: userLoading } = useUser();

const formData = ref({
  reported_username: '',
  report_type: '',
  task_id: '',
  reason: '',
  description: ''
});

const errors = ref([]);
const successMessage = ref('');
const user = ref(null);
const tasks = ref([]);
const isLoading = ref(false);
const interactedUsers = ref([]);
const isLoadingInteractedUsers = ref(true);
const showConfirmationModal = ref(false);

onMounted(async () => {
  // Check authentication first
  if (process.client) {
    const checkAuth = () => {
      if (!userLoading.value) {
        if (!isAuthenticated.value) {
          router.push('/login');
          return false;
        }
        return true;
      } else {
        setTimeout(checkAuth, 100);
        return false;
      }
    };
    
    if (!checkAuth()) return;
  }
  
  try {
    await fetchInteractedUsers();
    if (route.query.user) {
      try {
        const userFromQuery = JSON.parse(decodeURIComponent(route.query.user));
        const isValidUser = interactedUsers.value.some(u => u.username === userFromQuery.username || u.id === userFromQuery.id);
        if (isValidUser) {
          user.value = userFromQuery;
          formData.value.reported_username = userFromQuery.username;
          await fetchUserTasks(userFromQuery.username);
        } else {
          errors.value = ['You can only report users you have interacted with.'];
        }
      } catch (parseError) {
        console.error('Error parsing user from query:', parseError);
        errors.value = ['Invalid user data in URL.'];
      }
    }
  } catch (error) {
    console.error('Error in onMounted:', error);
    errors.value = ['An error occurred while loading the page. Please refresh and try again.'];
    isLoadingInteractedUsers.value = false;
  }
});

const fetchInteractedUsers = async () => {
  try {
    isLoadingInteractedUsers.value = true;
    errors.value = [];
    const data = await reportsAPI.getInteractedUsers();
    console.log('Interacted users response:', data);
    
    // Handle different response formats
    if (Array.isArray(data)) {
      interactedUsers.value = data;
    } else if (data && data.users) {
      interactedUsers.value = data.users;
    } else if (data && Array.isArray(data.data)) {
      interactedUsers.value = data.data;
    } else {
      interactedUsers.value = [];
    }
  } catch (error) {
    console.error('Error fetching interacted users:', error);
    interactedUsers.value = [];
    const errorMessage = error?.message || error?.error || 'Failed to load users. Please try again later.';
    errors.value = [errorMessage];
  } finally {
    isLoadingInteractedUsers.value = false;
  }
};

const handleUserSelect = async () => {
  if (formData.value.reported_username) {
    await fetchUserTasks(formData.value.reported_username);
  } else {
    user.value = null;
    tasks.value = [];
    formData.value.task_id = '';
  }
};

const fetchUserTasks = async (username) => {
  try {
    // Clear any previous errors related to user tasks
    const response = await reportsAPI.getUserTasks(username);
    if (response.user_found) {
      user.value = response.user;
      tasks.value = response.tasks || [];
      // Remove any previous user-related errors if successful
      errors.value = errors.value.filter(e => !e.includes('user') && !e.includes('User'));
    } else {
      // Don't show error for user not found - just silently handle it
      user.value = null;
      tasks.value = [];
    }
  } catch (error) {
    // Silently handle error - related tasks are optional
    // The form can still be submitted without this information
    console.warn('Could not fetch user tasks (optional feature):', error.message);
    user.value = null;
    tasks.value = [];
    // Don't add to errors array - this is an optional feature
  }
};

const confirmSubmit = async () => {
  showConfirmationModal.value = false;
  isLoading.value = true;
  errors.value = [];
  successMessage.value = '';
  
  try {
    const response = await reportsAPI.store(formData.value);
    if (response.success || response.message) {
      // Show success message
      successMessage.value = response.message || 'Report submitted successfully. Our admin team will review it.';
      
      // Clear form
      formData.value = {
        reported_username: '',
        report_type: '',
        task_id: '',
        reason: '',
        description: ''
      };
      user.value = null;
      tasks.value = [];
      
      // Auto-hide success message after 5 seconds
      setTimeout(() => {
        successMessage.value = '';
      }, 5000);
    } else {
      errors.value = [response.error || 'Failed to submit report'];
    }
  } catch (error) {
    errors.value = [error.message || 'An error occurred'];
  } finally {
    isLoading.value = false;
  }
};

// Modal styles
const modalOverlayStyle = {
  position: 'fixed',
  top: 0,
  left: 0,
  width: '100%',
  height: '100%',
  backgroundColor: 'rgba(0, 0, 0, 0.5)',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  zIndex: 2000
};

const modalContentStyle = {
  backgroundColor: 'white',
  borderRadius: '12px',
  boxShadow: '0 8px 32px rgba(0, 0, 0, 0.2)',
  maxWidth: '450px',
  width: '90%',
  animation: 'slideDown 0.3s ease-out'
};

const modalHeaderStyle = {
  padding: '20px 24px',
  borderBottom: '1px solid #e3e6f0',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center'
};

const modalHeaderH3Style = {
  margin: 0,
  fontSize: '20px',
  color: '#2e3a59',
  fontWeight: '600'
};

const modalCloseStyle = {
  background: 'transparent',
  border: 'none',
  fontSize: '28px',
  color: '#858796',
  cursor: 'pointer',
  padding: 0,
  width: '32px',
  height: '32px',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  borderRadius: '4px',
  transition: 'all 0.2s'
};

const modalBodyStyle = {
  padding: '24px'
};

const modalBodyPStyle = {
  margin: 0,
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.6'
};

const modalFooterStyle = {
  padding: '16px 24px',
  borderTop: '1px solid #e3e6f0',
  display: 'flex',
  gap: '12px',
  justifyContent: 'flex-end'
};

const modalCancelButtonStyle = {
  padding: '10px 20px',
  border: 'none',
  borderRadius: '8px',
  fontSize: '14px',
  fontWeight: '600',
  cursor: 'pointer',
  background: '#858796',
  color: 'white',
  transition: 'all 0.3s ease'
};

const modalConfirmButtonStyle = {
  padding: '10px 20px',
  border: 'none',
  borderRadius: '8px',
  fontSize: '14px',
  fontWeight: '600',
  cursor: 'pointer',
  background: 'linear-gradient(135deg, #e74a3b 0%, #c0392b 100%)',
  color: 'white',
  transition: 'all 0.3s ease'
};

// Skeleton Loading Styles
const skeletonBaseStyle = {
  background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
  backgroundSize: '200px 100%',
  animation: 'skeleton-loading 1.5s ease-in-out infinite',
  borderRadius: '8px'
};

const skeletonTitleStyle = {
  ...skeletonBaseStyle,
  width: '50%',
  height: '32px',
  marginBottom: '32px'
};

const skeletonFormGroupStyle = {
  marginBottom: '24px'
};

const skeletonLabelStyle = {
  ...skeletonBaseStyle,
  width: '120px',
  height: '16px',
  marginBottom: '8px'
};

const skeletonInputStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '44px'
};

const skeletonTextareaStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '120px'
};

const skeletonButtonStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '40px',
  marginTop: '8px'
};
</script>

<style>
@keyframes skeleton-loading {
  0% { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}
</style>
