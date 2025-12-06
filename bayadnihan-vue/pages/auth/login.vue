<template>
  <div>
    <div class="react-container" :style="containerStyle">
      <div :style="containerBeforeStyle"></div>
      
      <div :style="logoStyle">
        <div :style="logoIconStyle">🤝</div>
        <h1 :style="logoH1Style">BayadNihan</h1>
        <p :style="logoPStyle">Welcome back to our student community</p>
      </div>

      <h2 :style="h2Style">Sign In to Your Account</h2>

      <div :style="welcomeTextStyle">
        Enter your credentials to access your account and continue helping fellow students
      </div>

      <!-- Error Messages -->
      <div 
        v-for="(error, index) in serverErrors.any" 
        :key="index"
        :id="error.includes('Too many login attempts') ? 'rateLimitMessage' : undefined"
        :style="error.includes('Too many login attempts') ? warningStyle : errorStyle"
      >
        <span v-if="error.includes('Too many login attempts')" :style="warningIconStyle">⚠️</span>
        <template v-if="error.includes('Too many login attempts')">
          Too many login attempts. Please try again in
          <span id="countdown" :style="countdownStyle">{{ countdown }}</span> seconds.
        </template>
        <template v-else>{{ error }}</template>
      </div>

      <div v-if="serverErrors.sessionError" :style="errorStyle">{{ serverErrors.sessionError }}</div>
      <div v-if="serverErrors.sessionSuccess" :style="successStyle">{{ serverErrors.sessionSuccess }}</div>
      <div v-if="serverErrors.sessionStatus" :style="successStyle">{{ serverErrors.sessionStatus }}</div>

      <form @submit.prevent="handleSubmit" id="loginForm">
        <div :style="formGroupStyle">
          <label for="email" :style="labelStyle">Student Email Address</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="your.name" 
            value="@carsu.edu.ph"
            :style="emailInputStyle"
            :class="errors.email ? 'shake' : ''"
            required 
          />
          <div v-if="errors.email" :style="{ color: '#e74a3b', fontSize: '0.8rem', marginTop: '5px' }">{{ errors.email }}</div>
        </div>

        <div :style="formGroupStyle">
          <label for="password" :style="labelStyle">Password</label>
          <div :style="inputWithIconStyle">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              name="password" 
              placeholder="Enter your password" 
              :style="passwordInputStyle"
              :class="errors.password ? 'shake' : ''"
              required 
            />
            <span 
              id="togglePassword"
              :style="inputIconStyle" 
              @click="togglePasswordVisibility"
              role="button"
              tabindex="0"
            >
              {{ showPassword ? 'hide' : 'show' }}
            </span>
          </div>
          <div v-if="errors.password" :style="{ color: '#e74a3b', fontSize: '0.8rem', marginTop: '5px' }">{{ errors.password }}</div>
        </div>

        <div :style="rememberForgotStyle">
          <div :style="rememberMeStyle">
            <input 
              type="checkbox" 
              id="remember" 
              name="remember" 
              v-model="formData.remember"
              :style="rememberMeInputStyle"
            />
            <label for="remember" :style="rememberMeLabelStyle">Remember me</label>
          </div>
          <a 
            href="/forgot-password"
            :style="forgotPasswordStyle"
            @click.prevent="navigateTo('/forgot-password')"
          >
            Forgot password?
          </a>
        </div>

        <button 
          type="submit" 
          class="btn"
          :style="submitButtonStyle"
          :disabled="isLoading || countdown > 0"
        >
          {{ isLoading ? 'Signing In...' : 'Sign In' }}
        </button>

        <div :style="dividerStyle">
          <div :style="dividerLineStyle"></div>
          <span :style="dividerSpanStyle">Or continue with</span>
        </div>
      </form>

      <a 
        href="/api/auth/google" 
        :style="googleButtonStyle"
        @click.prevent="handleGoogleLogin"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M17.64 9.20443C17.64 8.56625 17.5827 7.95262 17.4764 7.36353H9V10.8449H13.8436C13.635 11.9699 13.0009 12.9231 12.0477 13.5613V15.8194H14.9564C16.6582 14.2526 17.64 11.9453 17.64 9.20443Z" fill="#4285F4"/>
          <path d="M8.99976 18C11.4298 18 13.467 17.1941 14.9561 15.8195L12.0475 13.5613C11.2416 14.1013 10.2107 14.4204 8.99976 14.4204C6.65567 14.4204 4.67158 12.8372 3.96385 10.71H0.957031V13.0418C2.43794 15.9831 5.48158 18 8.99976 18Z" fill="#34A853"/>
          <path d="M3.96409 10.7098C3.78409 10.1698 3.68182 9.59301 3.68182 8.99983C3.68182 8.40665 3.78409 7.82983 3.96409 7.28983V4.95801H0.957273C0.347727 6.17301 0 7.54756 0 8.99983C0 10.4521 0.347727 11.8266 0.957273 13.0416L3.96409 10.7098Z" fill="#FBBC05"/>
          <path d="M8.99976 3.57955C10.3211 3.57955 11.5075 4.03364 12.4402 4.92545L15.0216 2.34409C13.4629 0.891818 11.4257 0 8.99976 0C5.48158 0 2.43794 2.01682 0.957031 4.95818L3.96385 7.29C4.67158 5.16273 6.65567 3.57955 8.99976 3.57955Z" fill="#EA4335"/>
        </svg>
        Sign in with Carsu add. (Student Email)
      </a>

      <div :style="registerLinkStyle">
        Don't have an account? <a 
          href="/register"
          :style="registerLinkAStyle"
          @click.prevent="navigateTo('/register')"
        >
          Create one here
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

const router = useRouter();
const route = useRoute();
const { login } = useUser();
const { authAPI, googleAuthAPI } = useAPI();

const showPassword = ref(false);
const isLoading = ref(false);
const countdown = ref(null);
const formData = ref({
  remember: false
});
const errors = ref({
  email: '',
  password: ''
});
const serverErrors = ref({
  any: [],
  sessionError: '',
  sessionSuccess: '',
  sessionStatus: ''
});

// Handle rate limit error and initialize countdown
watch(() => serverErrors.value.any, (newErrors) => {
  const rateLimitError = newErrors.find(error => 
    error.includes('Too many login attempts')
  );
  
  if (rateLimitError) {
    const secondsMatch = rateLimitError.match(/(\d+)\s+seconds?/);
    const seconds = secondsMatch ? parseInt(secondsMatch[1]) : 0;
    countdown.value = seconds;
    
    // Disable login button during countdown
    if (process.client) {
      const loginButton = document.querySelector('button[type="submit"]');
      if (loginButton) {
        loginButton.disabled = true;
        loginButton.style.opacity = '0.6';
        loginButton.style.cursor = 'not-allowed';
      }
    }
  }
}, { deep: true });

// Countdown timer for rate limiting
let countdownTimer = null;
watch(countdown, (newCountdown) => {
  if (newCountdown > 0) {
    countdownTimer = setTimeout(() => {
      countdown.value = newCountdown - 1;
      if (process.client) {
        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
          countdownElement.textContent = newCountdown - 1;
        }
      }
    }, 1000);
  } else if (newCountdown === 0 && newCountdown !== null) {
    // Re-enable button when countdown reaches 0
    if (process.client) {
      const loginButton = document.querySelector('button[type="submit"]');
      if (loginButton) {
        loginButton.disabled = false;
        loginButton.style.opacity = '1';
        loginButton.style.cursor = 'pointer';
      }
      // Hide rate limit message
      const rateLimitMessage = document.getElementById('rateLimitMessage');
      if (rateLimitMessage) {
        rateLimitMessage.style.display = 'none';
      }
    }
  }
});

onMounted(() => {
  // Check for error in query parameters (from Google OAuth redirect)
  if (process.client) {
    const error = route.query.error;
    if (error) {
      serverErrors.value = {
        any: [decodeURIComponent(error)],
        sessionError: '',
        sessionSuccess: '',
        sessionStatus: ''
      };
      // Clean up the URL
      router.replace({ path: '/auth/login', query: {} });
    }
  }
  
  // Prevent going back to cached pages after logout
  if (process.client && window.history && window.history.pushState) {
    window.history.pushState('forward', null, window.location.href);
    window.onpopstate = function() {
      window.history.pushState('forward', null, window.location.href);
    };
  }
});

onUnmounted(() => {
  if (countdownTimer) {
    clearTimeout(countdownTimer);
  }
});

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const validateForm = (email, password) => {
  const newErrors = { email: '', password: '' };
  let isValid = true;

  if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    newErrors.email = 'Please enter a valid email address';
    isValid = false;
    if (process.client) {
      const emailInput = document.getElementById('email');
      if (emailInput) {
        emailInput.classList.add('shake');
        setTimeout(() => {
          emailInput.classList.remove('shake');
        }, 300);
      }
    }
  }

  if (password.length < 1) {
    newErrors.password = 'Please enter your password';
    isValid = false;
    if (process.client) {
      const passwordInput = document.getElementById('password');
      if (passwordInput) {
        passwordInput.classList.add('shake');
        setTimeout(() => {
          passwordInput.classList.remove('shake');
        }, 300);
      }
    }
  }

  errors.value = newErrors;
  return isValid;
};

const handleSubmit = async (e) => {
  const form = e.target;
  const formDataObj = new FormData(form);
  let email = formDataObj.get('email') || '';
  const password = formDataObj.get('password') || '';
  const remember = formDataObj.get('remember') === 'on';

  // Handle email with default value - if user only typed before @, append @carsu.edu.ph
  if (email && !email.includes('@')) {
    email = email + '@carsu.edu.ph';
  }

  // Ensure email and password are not empty
  if (!email || !password) {
    serverErrors.value = {
      any: ['Please enter both email and password'],
      sessionError: '',
      sessionSuccess: '',
      sessionStatus: ''
    };
    return;
  }

  if (!validateForm(email, password)) {
    return;
  }

  isLoading.value = true;
  serverErrors.value = { any: [], sessionError: '', sessionSuccess: '', sessionStatus: '' };

  // Update button to show loading state
  if (process.client) {
    const btn = form.querySelector('.btn');
    if (btn) {
      btn.innerHTML = 'Signing In...';
      btn.disabled = true;
    }
  }

  try {
    const response = await authAPI.login(email, password, remember);
    
    if (response.success && response.user && response.token) {
      login(response.user, response.token);
      router.push('/tasks');
    } else {
      serverErrors.value = {
        any: [response.error || 'Login failed'],
        sessionError: '',
        sessionSuccess: '',
        sessionStatus: ''
      };
      // Re-enable button
      if (process.client) {
        const btn = form.querySelector('.btn');
        if (btn) {
          btn.innerHTML = 'Sign In';
          btn.disabled = false;
        }
      }
    }
  } catch (error) {
    const errorMessage = error.message || 'An error occurred during login';
    
    // Handle rate limiting
    if (errorMessage.includes('Too many login attempts')) {
      const secondsMatch = errorMessage.match(/(\d+)\s+seconds?/);
      const seconds = secondsMatch ? parseInt(secondsMatch[1]) : 0;
      countdown.value = seconds;
      
      // Disable login button during countdown
      if (process.client) {
        const loginButton = document.querySelector('button[type="submit"]');
        if (loginButton) {
          loginButton.disabled = true;
          loginButton.style.opacity = '0.6';
          loginButton.style.cursor = 'not-allowed';
        }
      }
    }
    
    serverErrors.value = {
      any: [errorMessage],
      sessionError: '',
      sessionSuccess: '',
      sessionStatus: ''
    };
    
    // Re-enable button
    if (process.client) {
      const btn = form.querySelector('.btn');
      if (btn) {
        btn.innerHTML = 'Sign In';
        btn.disabled = false;
      }
    }
  } finally {
    isLoading.value = false;
  }
};

const handleGoogleLogin = () => {
  if (process.client) {
    const config = useRuntimeConfig();
    const apiBaseUrl = config.public.apiBaseUrl || 'http://localhost:8000/api';
    window.location.href = `${apiBaseUrl}/auth/google`;
  }
};

const navigateTo = (path) => {
  router.push(path);
};

// Styles
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
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
      transform: scale(1);
    }
    50% {
      opacity: 0.8;
      transform: scale(1.05);
    }
  }
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
  }
  
  @media (max-width: 480px) {
    body {
      background: white !important;
      background-image: none !important;
      backdrop-filter: none !important;
      padding: 0;
      min-height: 100vh;
    }
    .react-container {
      max-width: 100%;
      width: 100vw;
      padding: 40px 20px;
      border-radius: 0;
      box-shadow: none;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .react-container::before {
      display: none;
    }
    h2 {
      font-size: 1.5rem;
    }
    .remember-forgot {
      flex-direction: row;
      gap: 15px;
      align-items: flex-start;
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
  margin: '0 auto',
  background: 'white',
  padding: '40px',
  borderRadius: '20px',
  boxShadow: '0 20px 60px rgba(0, 0, 0, 0.1)',
  position: 'relative',
  overflow: 'hidden'
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

const logoStyle = {
  textAlign: 'center',
  marginBottom: '30px'
};

const logoIconStyle = {
  fontSize: '2.5rem',
  marginBottom: '10px'
};

const logoH1Style = {
  color: '#2e3a59',
  fontSize: '1.8rem',
  marginBottom: '5px'
};

const logoPStyle = {
  color: '#858796',
  fontSize: '0.9rem'
};

const h2Style = {
  color: '#2e3a59',
  textAlign: 'center',
  marginBottom: '30px',
  fontSize: '1.8rem',
  fontWeight: '600'
};

const welcomeTextStyle = {
  textAlign: 'center',
  color: '#858796',
  marginBottom: '30px',
  fontSize: '1rem',
  lineHeight: '1.5'
};

const formGroupStyle = {
  marginBottom: '20px',
  position: 'relative'
};

const labelStyle = {
  display: 'block',
  marginBottom: '8px',
  color: '#2e3a59',
  fontWeight: '500',
  fontSize: '0.9rem'
};

const inputStyle = {
  width: '100%',
  padding: '14px 16px',
  border: '2px solid #e8eaf6',
  borderRadius: '12px',
  fontSize: '1rem',
  transition: 'all 0.3s ease',
  background: '#f8f9fc',
  color: '#080809ff'
};

const inputWithIconStyle = {
  position: 'relative'
};

const inputIconStyle = {
  position: 'absolute',
  right: '16px',
  top: '50%',
  transform: 'translateY(-50%)',
  color: '#858796',
  cursor: 'pointer',
  fontSize: '1.1rem',
  background: 'none',
  border: 'none',
  padding: 0
};

const rememberForgotStyle = {
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  marginBottom: '25px'
};

const rememberMeStyle = {
  display: 'flex',
  alignItems: 'center',
  gap: '8px'
};

const rememberMeInputStyle = {
  width: 'auto',
  margin: 0
};

const rememberMeLabelStyle = {
  color: '#2e3a59',
  fontSize: '0.9rem',
  margin: 0,
  cursor: 'pointer'
};

const forgotPasswordStyle = {
  color: '#4e73df',
  textDecoration: 'none',
  fontSize: '0.9rem',
  fontWeight: '500',
  transition: 'color 0.3s ease'
};

const btnStyle = {
  width: '100%',
  padding: '16px',
  background: '#4e73df',
  color: 'white',
  border: 'none',
  borderRadius: '12px',
  fontSize: '1rem',
  fontWeight: '600',
  cursor: 'pointer',
  transition: 'all 0.3s ease',
  marginBottom: '20px'
};

const btnGoogleStyle = {
  background: 'white',
  color: '#2e3a59',
  border: '2px solid #e8eaf6',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  gap: '10px',
  textDecoration: 'none'
};

const dividerStyle = {
  textAlign: 'center',
  margin: '25px 0',
  position: 'relative',
  color: '#858796',
  fontSize: '0.9rem'
};

const dividerLineStyle = {
  position: 'absolute',
  top: '50%',
  left: 0,
  right: 0,
  height: '1px',
  background: '#e8eaf6',
  zIndex: 0
};

const dividerSpanStyle = {
  background: 'white',
  padding: '0 15px',
  position: 'relative',
  zIndex: 1
};

const errorStyle = {
  background: '#f8d7da',
  color: '#721c24',
  padding: '12px 16px',
  borderRadius: '8px',
  marginBottom: '20px',
  borderLeft: '4px solid #e74a3b',
  animation: 'slideIn 0.3s ease',
  fontWeight: '500'
};

const warningStyle = {
  background: '#fff3cd',
  color: '#856404',
  padding: '14px 16px',
  borderRadius: '8px',
  marginBottom: '20px',
  borderLeft: '4px solid #f6c23e',
  animation: 'slideIn 0.3s ease',
  fontWeight: '600',
  display: 'flex',
  alignItems: 'center',
  gap: '10px'
};

const warningIconStyle = {
  fontSize: '1.2rem'
};

const successStyle = {
  background: '#d1edff',
  color: '#0c5460',
  padding: '12px 16px',
  borderRadius: '8px',
  marginBottom: '20px',
  borderLeft: '4px solid #4e73df'
};

const registerLinkStyle = {
  textAlign: 'center',
  marginTop: '25px',
  color: '#858796'
};

const registerLinkAStyle = {
  color: '#4e73df',
  textDecoration: 'none',
  fontWeight: '600',
  transition: 'color 0.3s ease'
};

const countdownStyle = {
  fontWeight: '700',
  color: '#d97706',
  fontSize: '1.1em',
  animation: 'pulse 1s ease-in-out infinite'
};

// Computed styles for merged objects
const emailInputStyle = computed(() => ({
  ...inputStyle,
  ...(errors.value.email && { borderColor: '#e74a3b' })
}));

const passwordInputStyle = computed(() => ({
  ...inputStyle,
  ...(errors.value.password && { borderColor: '#e74a3b' })
}));

const submitButtonStyle = computed(() => ({
  ...btnStyle,
  ...(isLoading.value && { opacity: 0.6, cursor: 'not-allowed' }),
  ...(countdown.value > 0 && { opacity: 0.6, cursor: 'not-allowed' })
}));

const googleButtonStyle = computed(() => ({
  ...btnStyle,
  ...btnGoogleStyle
}));
</script>

