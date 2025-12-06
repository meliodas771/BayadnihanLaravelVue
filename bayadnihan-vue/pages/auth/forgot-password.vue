<template>
  <div>
    <div class="react-container" :style="containerStyle">
      <div :style="containerBeforeStyle"></div>
      
      <div :style="logoStyle">
        <div :style="logoIconStyle">🤝</div>
        <h1 :style="logoH1Style">BayadNihan</h1>
        <p :style="logoPStyle">Reset your password</p>
      </div>

      <h2 :style="h2Style">Forgot Your Password?</h2>

      <div :style="welcomeTextStyle">
        Enter your student email address and we'll send you a verification code to reset your password.
      </div>

      <div v-if="serverMessages.error" :style="errorStyle">
        {{ serverMessages.error }}
      </div>

      <div v-if="serverMessages.success" :style="successStyle">
        {{ serverMessages.success }}
      </div>

      <form @submit.prevent="handleSubmit" id="forgotPasswordForm">
        <div :style="formGroupStyle">
          <label for="email" :style="labelStyle">Student Email Address</label>
          <input 
            type="text" 
            id="email" 
            name="email" 
            placeholder="your.name" 
            :value="emailValue"
            @input="handleEmailChange"
            @focus="handleEmailFocus"
            :style="inputStyle"
            required 
          />
        </div>

        <button 
          type="submit" 
          :style="submitButtonStyle"
          :disabled="isLoading"
        >
          {{ isLoading ? 'Sending...' : 'Send Verification Code' }}
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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';

const router = useRouter();
const { authAPI } = useAPI();

const isLoading = ref(false);
const email = ref('@carsu.edu.ph');
const serverMessages = ref({
  error: '',
  success: ''
});

const emailValue = computed(() => email.value);

const handleEmailChange = (e) => {
  let value = e.target.value;
  
  if (value.includes('@carsu.edu.ph')) {
    value = value.replace('@carsu.edu.ph', '');
  }
  
  email.value = value + '@carsu.edu.ph';
  
  setTimeout(() => {
    const emailInput = e.target;
    const cursorPos = value.length;
    emailInput.setSelectionRange(cursorPos, cursorPos);
  }, 0);
};

const handleEmailFocus = (e) => {
  const value = e.target.value.replace('@carsu.edu.ph', '');
  e.target.setSelectionRange(0, value.length);
};

onMounted(() => {
  if (process.client) {
    const emailInput = document.getElementById('email');
    if (emailInput) {
      emailInput.focus();
      const emailValue = email.value.replace('@carsu.edu.ph', '');
      emailInput.setSelectionRange(0, emailValue.length);
    }
  }
});

const handleSubmit = async (e) => {
  isLoading.value = true;
  serverMessages.value = { error: '', success: '' };

  if (process.client) {
    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';
    }
  }

  try {
    const response = await authAPI.forgotPassword(email.value);
    
    if (response.success || response.message) {
      serverMessages.value = {
        success: response.message || 'Verification code sent to your email!',
        error: ''
      };
      
      setTimeout(() => {
        router.push({ path: '/auth/verify-code', query: { email: email.value } });
      }, 2000);
    } else {
      serverMessages.value = {
        success: '',
        error: response.error || 'Failed to send verification code. Please try again.'
      };
      if (process.client) {
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Send Verification Code';
        }
      }
    }
  } catch (error) {
    serverMessages.value = {
      success: '',
      error: error.message || 'An error occurred. Please try again.'
    };
    if (process.client) {
      const submitBtn = e.target.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Verification Code';
      }
    }
  } finally {
    isLoading.value = false;
    setTimeout(() => {
      if (process.client) {
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Send Verification Code';
        }
      }
    }, 3000);
  }
};

const navigateTo = (path) => {
  router.push(path);
};

const handleLinkHover = (event, isEnter) => {
  event.target.style.color = isEnter ? '#2e3a59' : '#4e73df';
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
const btnStyle = { width: '100%', padding: '15px', background: 'linear-gradient(135deg, #4e73df, #5a67d8)', color: 'white', border: 'none', borderRadius: '10px', fontSize: '16px', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', marginBottom: '20px' };
const btnDisabledStyle = { opacity: '0.6', cursor: 'not-allowed', transform: 'none' };
const backToLoginStyle = { textAlign: 'center', marginTop: '20px' };
const backLinkStyle = { color: '#4e73df', textDecoration: 'none', fontSize: '14px', fontWeight: '500', transition: 'color 0.3s ease', cursor: 'pointer' };
const errorStyle = { background: '#f8d7da', color: '#721c24', padding: '12px 15px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #f5c6cb', fontSize: '14px' };
const successStyle = { background: '#d4edda', color: '#155724', padding: '12px 15px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #c3e6cb', fontSize: '14px' };

// Computed styles for merged objects
const submitButtonStyle = computed(() => ({
  ...btnStyle,
  ...(isLoading.value && btnDisabledStyle)
}));
</script>

