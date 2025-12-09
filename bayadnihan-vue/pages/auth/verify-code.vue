<template>
  <div>
    <div class="react-container" :style="containerStyle">
      <div :style="containerBeforeStyle"></div>
      
      <div :style="logoStyle">
        <div :style="logoIconStyle">🤝</div>
        <h1 :style="logoH1Style">BayadNihan</h1>
        <p :style="logoPStyle">Verify your identity</p>
      </div>

      <h2 :style="h2Style">Enter Verification Code</h2>

      <div :style="welcomeTextStyle">
        We've sent a 6-digit verification code to your student email.
      </div>

      <div v-if="serverMessages.error" :style="errorStyle">
        {{ serverMessages.error }}
      </div>

      <div v-if="serverMessages.success" :style="successStyle">
        {{ serverMessages.success }}
      </div>

      <form @submit.prevent="handleSubmit" id="verifyCodeForm">
        <div :style="formGroupStyle">
          <label for="code" :style="labelStyle">Verification Code</label>
          <input 
            ref="codeInputRef"
            type="text" 
            id="code" 
            name="code" 
            placeholder="0 0 0 0 0 0" 
            v-model="code"
            @input="handleCodeChange"
            @paste="handlePaste"
            :style="inputStyle"
            maxlength="6"
            required 
          />
          <small :style="smallTextStyle">
            Enter the 6-digit code sent to your email
          </small>
        </div>

        <button 
          type="submit" 
          :style="submitButtonStyle"
          :disabled="isLoading || code.length !== 6"
        >
          {{ isLoading ? 'Verifying...' : 'Verify Code' }}
        </button>
      </form>

      <div :style="resendSectionStyle">
        <p :style="resendTextStyle">Didn't receive the code?</p>
        <button 
          type="button"
          :style="resendButtonStyle"
          @click="handleResendCode"
          :disabled="isResending"
        >
          {{ isResending ? 'Sending...' : 'Resend Code' }}
        </button>
      </div>

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
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAPI } from '~/utils/api';

const router = useRouter();
const route = useRoute();
const { authAPI } = useAPI();

const code = ref('');
const isLoading = ref(false);
const isResending = ref(false);
const codeInputRef = ref(null);
const serverMessages = ref({
  error: '',
  success: ''
});

const email = computed(() => route.query.email || 'user@carsu.edu.ph');

const handleCodeChange = (e) => {
  let value = e.target.value.replace(/\D/g, '');
  
  if (value.length > 6) {
    value = value.substring(0, 6);
  }
  
  code.value = value;
  
  if (value.length === 6) {
    handleSubmit();
  }
};

const handlePaste = (e) => {
  e.preventDefault();
  const pastedText = (e.clipboardData || window.clipboardData).getData('text');
  const numericText = pastedText.replace(/\D/g, '').substring(0, 6);
  code.value = numericText;
  
  if (numericText.length === 6) {
    handleSubmit();
  }
};

const handleSubmit = async (e) => {
  if (e) e.preventDefault();
  
  if (code.value.length !== 6) {
    serverMessages.value = {
      error: 'Please enter a 6-digit verification code',
      success: ''
    };
    return;
  }

  isLoading.value = true;
  serverMessages.value = { error: '', success: '' };
  
  try {
    const response = await authAPI.verifyCode(email.value, code.value);
    
    if (response.success || response.verified) {
      router.push(`/auth/reset-password?email=${encodeURIComponent(email.value)}&code=${code.value}`);
    } else {
      serverMessages.value = {
        error: response.error || 'Invalid verification code. Please try again.',
        success: ''
      };
    }
  } catch (error) {
    serverMessages.value = {
      error: error.message || 'An error occurred. Please try again.',
      success: ''
    };
  } finally {
    isLoading.value = false;
  }
};

const handleResendCode = async (e) => {
  e.preventDefault();
  isResending.value = true;
  serverMessages.value = { error: '', success: '' };
  
  try {
    const response = await authAPI.resendCode(email.value);
    
    if (response.success || response.message) {
      serverMessages.value = {
        success: response.message || 'Verification code sent!',
        error: ''
      };
    } else {
      serverMessages.value = {
        success: '',
        error: response.error || 'Failed to resend code. Please try again.'
      };
    }
  } catch (error) {
    serverMessages.value = {
      success: '',
      error: error.message || 'An error occurred. Please try again.'
    };
  } finally {
    isResending.value = false;
  }
};

onMounted(() => {
  if (codeInputRef.value) {
    codeInputRef.value.focus();
  }
});

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
    
    .code-input {
      letter-spacing: 4px;
      font-size: 16px;
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
const labelStyle = { display: 'block', color: '#2e3a59', fontWeight: '600', marginBottom: '8px', fontSize: '18px' };
const inputStyle = { width: '100%', padding: '15px', border: '2px solid #e3e6f0', borderRadius: '10px', fontSize: '18px', fontWeight: '600', textAlign: 'center', letterSpacing: '8px', transition: 'all 0.3s ease', background: '#f8f9fc', color: '#080809ff' };
const smallTextStyle = { display: 'block', color: '#858796', fontSize: '12px', marginTop: '5px', textAlign: 'center' };
const btnStyle = { width: '100%', padding: '15px', background: 'linear-gradient(135deg, #4e73df, #5a67d8)', color: 'white', border: 'none', borderRadius: '10px', fontSize: '16px', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', marginBottom: '20px' };
const btnDisabledStyle = { opacity: '0.6', cursor: 'not-allowed', transform: 'none' };
const btnLinkStyle = { background: 'none', border: 'none', color: '#4e73df', textDecoration: 'underline', cursor: 'pointer', fontSize: '14px', fontWeight: '500', transition: 'color 0.3s ease' };
const resendSectionStyle = { textAlign: 'center', margin: '20px 0', padding: '15px', background: '#f8f9fc', borderRadius: '10px' };
const resendTextStyle = { color: '#858796', fontSize: '14px', marginBottom: '10px' };
const backToLoginStyle = { textAlign: 'center', marginTop: '20px' };
const backLinkStyle = { color: '#4e73df', textDecoration: 'none', fontSize: '14px', fontWeight: '500', transition: 'color 0.3s ease', cursor: 'pointer' };
const errorStyle = { background: '#f8d7da', color: '#721c24', padding: '12px 15px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #f5c6cb', fontSize: '14px' };
const successStyle = { background: '#d4edda', color: '#155724', padding: '12px 15px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #c3e6cb', fontSize: '14px' };

// Computed styles for merged objects
const submitButtonStyle = computed(() => ({
  ...btnStyle,
  ...(isLoading.value && btnDisabledStyle)
}));

const resendButtonStyle = computed(() => ({
  ...btnLinkStyle,
  ...(isResending.value && { opacity: 0.6, cursor: 'not-allowed' })
}));
</script>

