<template>
  <div>
    <div class="react-container" :style="containerStyle">
      <div :style="containerBeforeStyle"></div>
      
      <div :style="logoStyle">
        <div :style="logoIconStyle">🤝</div>
        <h1 :style="logoH1Style">BayadNihan</h1>
        <p :style="logoPStyle">Create new password</p>
      </div>

      <h2 :style="h2Style">Reset Your Password</h2>

      <div :style="welcomeTextStyle">
        Enter your new password below. Make sure it's secure and easy for you to remember.
      </div>

      <div v-if="serverMessages.error" :style="errorStyle">
        {{ serverMessages.error }}
      </div>

      <div v-if="serverMessages.success" :style="successStyle">
        {{ serverMessages.success }}
      </div>

      <form @submit.prevent="handleSubmit" id="resetPasswordForm">
        <input type="hidden" name="email" :value="formData.email" />
        <input type="hidden" name="token" :value="formData.token" />
        
        <div :style="formGroupStyle">
          <label for="password" :style="labelStyle">New Password</label>
          <div :style="inputWithIconStyle">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              name="password" 
              placeholder="Enter new password" 
              v-model="formData.password"
              @input="handleInputChange"
              minlength="6"
              :style="passwordInputWithIconStyle"
              required 
            />
            <button 
              type="button"
              :style="inputIconStyle"
              @click="showPassword = !showPassword"
              @mouseenter="handleIconHover($event, true)"
              @mouseleave="handleIconHover($event, false)"
            >
              {{ showPassword ? 'hide' : 'show' }}
            </button>
          </div>
          <small :style="smallTextStyle">
            Password must be at least 6 characters long
          </small>
          <div v-if="formData.password" :style="strengthIndicatorComputedStyle">
            {{ passwordStrength.text }}
          </div>
        </div>

        <div :style="formGroupStyle">
          <label for="password_confirmation" :style="labelStyle">Confirm New Password</label>
          <div :style="inputWithIconStyle">
            <input 
              :type="showConfirmPassword ? 'text' : 'password'" 
              id="password_confirmation" 
              name="password_confirmation" 
              placeholder="Confirm new password" 
              v-model="formData.password_confirmation"
              @input="handleInputChange"
              minlength="6"
              :style="getConfirmPasswordInputStyle()"
              required 
            />
            <button 
              type="button"
              :style="inputIconStyle"
              @click="showConfirmPassword = !showConfirmPassword"
              @mouseenter="handleIconHover($event, true)"
              @mouseleave="handleIconHover($event, false)"
            >
              {{ showConfirmPassword ? 'hide' : 'show' }}
            </button>
          </div>
          <small :style="smallTextStyle">
            Re-enter your new password
          </small>
        </div>

        <button 
          type="submit" 
          :style="submitButtonStyle"
          :disabled="isLoading"
        >
          {{ isLoading ? 'Resetting Password...' : 'Reset Password' }}
        </button>
      </form>

      <div :style="backToLoginStyle">
        <span 
          :style="backLinkStyle"
          @click="navigateTo('/auth/login')"
          @mouseenter="handleLinkHover($event, true)"
          @mouseleave="handleLinkHover($event, false)"
        >
          ← Back to Login
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAPI } from '~/utils/api';

const router = useRouter();
const route = useRoute();
const { authAPI } = useAPI();

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isLoading = ref(false);
const formData = ref({
  password: '',
  password_confirmation: '',
  email: '',
  token: ''
});
const passwordStrength = ref({
  text: '',
  color: '#858796'
});
const serverMessages = ref({
  error: '',
  success: ''
});

onMounted(() => {
  const searchParams = new URLSearchParams(route.query);
  const email = searchParams.get('email') || '';
  const code = searchParams.get('code') || '';
  
  formData.value = {
    ...formData.value,
    email,
    token: code
  };
});

const checkPasswordStrength = (password) => {
  let strength = 0;
  if (password.length >= 6) strength++;
  if (password.match(/[a-z]/)) strength++;
  if (password.match(/[A-Z]/)) strength++;
  if (password.match(/[0-9]/)) strength++;
  if (password.match(/[^a-zA-Z0-9]/)) strength++;

  const strengthTexts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
  const strengthColors = ['#e74a3b', '#f6c23e', '#fd7e14', '#1cc88a', '#28a745'];
  
  passwordStrength.value = {
    text: strengthTexts[strength - 1] || '',
    color: strengthColors[strength - 1] || '#858796'
  };
};

watch(() => formData.value.password, (newPassword) => {
  checkPasswordStrength(newPassword);
});

const handleInputChange = (e) => {
  const { name, value } = e.target;
  formData.value = {
    ...formData.value,
    [name]: value
  };
};

const validateForm = () => {
  if (formData.value.password.length < 6) {
    serverMessages.value = {
      error: 'Password must be at least 6 characters long.',
      success: ''
    };
    return false;
  }

  if (formData.value.password !== formData.value.password_confirmation) {
    serverMessages.value = {
      error: 'Passwords do not match. Please check and try again.',
      success: ''
    };
    return false;
  }

  return true;
};

const handleSubmit = async (e) => {
  e.preventDefault();
  
  if (!validateForm()) {
    return;
  }

  isLoading.value = true;
  serverMessages.value = { error: '', success: '' };
  
  try {
    const response = await authAPI.resetPassword(
      formData.value.email,
      formData.value.token,
      formData.value.password,
      formData.value.password_confirmation
    );
    
    if (response.success || response.message) {
      serverMessages.value = {
        success: response.message || 'Password reset successfully! Redirecting to login...',
        error: ''
      };
      
      setTimeout(() => {
        router.push('/auth/login');
      }, 2000);
    } else {
      serverMessages.value = {
        success: '',
        error: response.error || 'Failed to reset password. Please try again.'
      };
    }
  } catch (error) {
    serverMessages.value = {
      success: '',
      error: error.message || 'An error occurred. Please try again.'
    };
  } finally {
    isLoading.value = false;
  }
};

const navigateTo = (path) => {
  router.push(path);
};

const handleLinkHover = (event, isEnter) => {
  event.target.style.color = isEnter ? '#2e3a59' : '#4e73df';
};

const handleIconHover = (event, isEnter) => {
  event.target.style.color = isEnter ? '#4e73df' : '#858796';
};

const getConfirmPasswordInputStyle = () => {
  const baseStyle = { ...inputStyle, ...inputWithIconInputStyle };
  if (formData.value.password_confirmation && formData.value.password !== formData.value.password_confirmation) {
    return { ...baseStyle, ...inputErrorStyle };
  }
  return baseStyle;
};

const globalStyles = `
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background-image: url('/images/landing/loginCSU.jpg');
    background-size: cover;
    background-position: center;
    backdrop-filter: blur(10px);
  }
`;

const keyframes = `
  @media (max-width: 480px) {
    .react-container {
      padding: 30px 20px;
      margin: 10px;
    }
    
    .logo-icon {
      font-size: 40px;
    }
    
    .logo-h1 {
      font-size: 24px;
    }
    
    .h2 {
      font-size: 20px;
    }
  }
`;

useHead({
  style: [
    {
      children: globalStyles + keyframes
    }
  ]
});

const containerStyle = {
  maxWidth: '440px',
  width: '100%',
  background: 'white',
  padding: '40px',
  borderRadius: '20px',
  boxShadow: '0 20px 60px rgba(0, 0, 0, 0.1)',
  position: 'relative',
  overflow: 'hidden',
  margin: '20px auto'
};

const containerBeforeStyle = {
  position: 'absolute',
  top: 0,
  left: 0,
  right: 0,
  height: '4px',
  background: 'linear-gradient(90deg, #4e73df, #1cc88a)',
  zIndex: 1
};

const logoStyle = { textAlign: 'center', marginBottom: '30px' };
const logoIconStyle = { fontSize: '48px', marginBottom: '10px' };
const logoH1Style = { color: '#2e3a59', fontSize: '28px', fontWeight: '700', marginBottom: '5px' };
const logoPStyle = { color: '#858796', fontSize: '14px' };
const h2Style = { color: '#2e3a59', fontSize: '24px', fontWeight: '600', textAlign: 'center', marginBottom: '15px' };
const welcomeTextStyle = { textAlign: 'center', color: '#858796', fontSize: '14px', marginBottom: '30px', lineHeight: '1.5' };
const formGroupStyle = { marginBottom: '25px' };
const labelStyle = { display: 'block', color: '#2e3a59', fontWeight: '600', marginBottom: '8px', fontSize: '14px' };
const inputStyle = { width: '100%', padding: '15px', border: '2px solid #e3e6f0', borderRadius: '10px', fontSize: '16px', transition: 'all 0.3s ease', background: '#f8f9fc', color: '#080809ff' };
const inputErrorStyle = { borderColor: '#e74a3b' };
const smallTextStyle = { display: 'block', color: '#858796', fontSize: '12px', marginTop: '5px' };
const strengthIndicatorStyle = { fontSize: '12px', fontWeight: '600', marginTop: '5px' };
const inputWithIconStyle = { position: 'relative' };
const inputWithIconInputStyle = { paddingRight: '50px' };
const inputIconStyle = { position: 'absolute', right: '15px', top: '50%', transform: 'translateY(-50%)', color: '#858796', cursor: 'pointer', fontSize: '12px', fontWeight: '500', userSelect: 'none', transition: 'color 0.3s ease', background: 'none', border: 'none' };
const btnStyle = { width: '100%', padding: '15px', background: 'linear-gradient(135deg, #4e73df, #5a67d8)', color: 'white', border: 'none', borderRadius: '10px', fontSize: '16px', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', marginBottom: '20px' };
const btnDisabledStyle = { opacity: '0.6', cursor: 'not-allowed', transform: 'none' };
const backToLoginStyle = { textAlign: 'center', marginTop: '20px' };
const backLinkStyle = { color: '#4e73df', textDecoration: 'none', fontSize: '14px', fontWeight: '500', transition: 'color 0.3s ease', cursor: 'pointer' };
const errorStyle = { background: '#f8d7da', color: '#721c24', padding: '12px 15px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #f5c6cb', fontSize: '14px' };
const successStyle = { background: '#d4edda', color: '#155724', padding: '12px 15px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #c3e6cb', fontSize: '14px' };

// Computed styles for merged objects
const passwordInputWithIconStyle = computed(() => ({
  ...inputStyle,
  ...inputWithIconInputStyle
}));

const strengthIndicatorComputedStyle = computed(() => ({
  ...strengthIndicatorStyle,
  color: passwordStrength.value.color
}));

const submitButtonStyle = computed(() => ({
  ...btnStyle,
  ...(isLoading.value && btnDisabledStyle)
}));
</script>

