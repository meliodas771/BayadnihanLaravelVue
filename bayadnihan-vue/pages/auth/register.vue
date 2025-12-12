<template>
  <div>
    <div class="react-container" :style="containerStyle">
      <div :style="containerBeforeStyle"></div>
      
      <div :style="logoStyle">
        <div :style="logoIconStyle">🤝</div>
        <h1 :style="logoH1Style">BayadNihan</h1>
        <p :style="logoPStyle">Join our student community</p>
      </div>

      <h2 :style="h2Style">Create Your Account</h2>

      <div v-if="serverMessages.success" :style="successStyle">
        {{ serverMessages.success }}
      </div>

      <div v-if="serverMessages.error" :style="errorStyle">
        {{ serverMessages.error }}
      </div>

      <form @submit.prevent="handleSubmit" id="registerForm">
        <!-- ID Number -->
        <div :style="formGroupStyle">
          <label for="username" :style="labelStyle">ID Number</label>
          <input 
            type="text" 
            id="username" 
            name="username" 
            placeholder="e.g., 231-00123" 
            v-model="formData.username"
            @input="handleInputChange"
            :class="validation.username.visible ? (validation.username.isValid ? 'valid' : 'invalid') : ''"
            :style="getInputStyle('username')"
            required 
          />
          <div 
            id="usernameIndicator"
            :style="getValidationIndicatorStyle('username')"
          >
            <span class="indicator-icon" :style="indicatorIconStyle">
              {{ validation.username.visible ? (validation.username.isValid ? '✅' : '❌') : '' }}
            </span>
            <span class="indicator-text" :style="indicatorTextStyle">
              {{ validation.username.visible ? validation.username.message : '' }}
            </span>
          </div>
        </div>

        <!-- Email -->
        <div :style="formGroupStyle">
          <label for="email" :style="labelStyle">Student Email (@carsu.edu.ph)</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="your.name" 
            v-model="formData.email"
            @input="handleInputChange"
            :class="validation.email.visible ? (validation.email.isValid ? 'valid' : 'invalid') : ''"
            :style="getInputStyle('email')"
            required 
          />
          <div 
            id="emailIndicator"
            :style="getValidationIndicatorStyle('email')"
          >
            <span class="indicator-icon" :style="indicatorIconStyle">
              {{ validation.email.visible ? (validation.email.isValid ? '✅' : '❌') : '' }}
            </span>
            <span class="indicator-text" :style="indicatorTextStyle">
              {{ validation.email.visible ? validation.email.message : '' }}
            </span>
          </div>
        </div>

        <!-- Password -->
        <div :style="formGroupStyle">
          <label for="password" :style="labelStyle">Password</label>
          <div :style="inputWithIconStyle">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              name="password" 
              placeholder="Create a password" 
              v-model="formData.password"
              @input="handleInputChange"
              :style="getPasswordInputStyle()"
              required 
            />
            <button 
              type="button" 
              id="togglePassword"
              :style="inputIconStyle"
              @click="togglePasswordVisibility"
            >
              {{ showPassword ? 'hide' : 'show' }}
            </button>
          </div>
          <div 
            id="passwordStrength"
            :class="`password-strength ${passwordStrength.class}`"
            :style="passwordStrengthStyle"
          >
            <div 
              class="password-strength-bar"
              :style="getPasswordStrengthBarStyle()"
            ></div>
          </div>
          <div 
            id="passwordRequirement"
            :style="getPasswordRequirementStyle()"
          >
            <span class="requirement-icon" :style="requirementIconStyle">
              {{ passwordStrength.requirementClass === 'valid' ? '✅' : 
                 passwordStrength.requirementClass === 'invalid' ? '❌' : 'ℹ️' }}
            </span>
            <span class="requirement-text" :style="requirementTextStyle">
              {{ passwordStrength.requirement }}
            </span>
          </div>
        </div>

        <!-- Confirm Password -->
        <div :style="formGroupStyle">
          <label for="password_confirmation" :style="labelStyle">Confirm Password</label>
          <div :style="inputWithIconStyle">
            <input 
              :type="showConfirmPassword ? 'text' : 'password'" 
              id="password_confirmation" 
              name="password_confirmation" 
              placeholder="Confirm your password" 
              v-model="formData.password_confirmation"
              @input="handleInputChange"
              :style="getConfirmPasswordInputStyle()"
              required 
            />
            <button 
              type="button" 
              id="toggleConfirmPassword"
              :style="inputIconStyle"
              @click="toggleConfirmPasswordVisibility"
            >
              {{ showConfirmPassword ? 'hide' : 'show' }}
            </button>
          </div>
          <div 
            id="passwordMatch"
            :style="getPasswordMatchStyle()"
          >
            <span class="match-icon" :style="matchIconStyle">
              {{ validation.passwordMatch.visible ? (validation.passwordMatch.isValid ? '✅' : '❌') : '' }}
            </span>
            <span class="match-text" :style="matchTextStyle">
              {{ validation.passwordMatch.visible ? validation.passwordMatch.message : '' }}
            </span>
          </div>
        </div>

        <!-- Role Selection -->
        <div :style="formGroupStyle">
          <label :style="labelStyle">Choose Your Role</label>
          <div :style="roleOptionsStyle">
            <label 
              v-for="role in ['both', 'poster', 'doer']"
              :key="role"
              :class="`role-option ${formData.role === role ? 'selected' : ''}`"
              :for="`role_${role}`"
              :style="getRoleOptionStyle(role)"
              @click="handleRoleChange(role)"
              @mouseenter="handleRoleHover($event, role, true)"
              @mouseleave="handleRoleHover($event, role, false)"
            >
              <input 
                type="radio" 
                :id="`role_${role}`"
                name="role" 
                :value="role"
                v-model="formData.role"
                @change="handleRoleChange(role)"
                style="display: none"
              />
              <div class="role-icon" :style="roleIconStyle">
                {{ role === 'both' ? '👥' : role === 'poster' ? '📋' : '💰' }}
              </div>
              <div class="role-name" :style="roleNameStyle">
                {{ role === 'both' ? 'Both' : role === 'poster' ? 'Poster' : 'Doer' }}
              </div>
              <div class="role-desc" :style="roleDescStyle">
                {{ role === 'both' ? 'Post & Do Tasks' : role === 'poster' ? 'Create Tasks' : 'Complete Tasks' }}
              </div>
            </label>
          </div>
        </div>

        <!-- Phone Number -->
        <div :style="formGroupStyle">
          <label for="phone_number" :style="labelStyle">Phone Number (Optional)</label>
          <input 
            type="tel" 
            id="phone_number" 
            name="phone_number" 
            placeholder="Enter your phone number" 
            v-model="formData.phone_number"
            @input="handleInputChange"
            :style="inputStyle"
          />
        </div>

        <button 
          type="submit" 
          id="submitBtn"
          class="btn"
          :style="getSubmitButtonStyle()"
          :disabled="!isFormValid() || isLoading"
        >
          {{ isLoading ? 'Creating Account...' : 'Create Account' }}
        </button>
      </form>

      <div :style="loginLinkStyle">
        Already have an account? <a 
          href="/auth/login"
          :style="linkStyle"
          @click.prevent="navigateTo('/auth/login')"
        >
          Sign in here
        </a>
      </div>

      <div :style="termsStyle">
        By creating an account, you agree to our <NuxtLink to="/footer/terms" :style="linkStyle">Terms of Service</NuxtLink> and <NuxtLink to="/footer/privacy" :style="linkStyle">Privacy Policy</NuxtLink>
      </div>
    </div>

    <!-- Trial Modal -->
    <div v-if="showTrialModal" :style="modalStyle" @click="handleCancelTrial">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h2 :style="{ margin: 0, color: 'white' }">🎉 7-Day Free Trial</h2>
        </div>
        <div :style="modalBodyStyle">
          <div :style="{ textAlign: 'center' }">
            <div :style="trialBadgeStyle">
              <span :style="trialDaysStyle">7 DAYS</span>
              <span :style="trialLabelStyle">FREE TRIAL</span>
            </div>
            <p :style="{ fontSize: '1.2rem', color: '#2e3a59', marginBottom: '15px' }">
              You've selected to be both a <strong>Poster</strong> and <strong>Doer</strong>!
            </p>
            <p :style="{ color: '#858796', lineHeight: '1.8', marginBottom: '30px' }">
              Enjoy full access to both roles for <strong>7 days</strong>. After the trial period ends, 
              your account will automatically switch to <strong>Poster</strong> role by default.
            </p>
            
            <div :style="{ background: '#f8f9fc', padding: '25px', borderRadius: '15px', marginTop: '25px' }">
              <h3 :style="{ color: '#2e3a59', fontSize: '1.3rem', marginBottom: '20px', textAlign: 'center' }">Continue as Both Roles</h3>
              <div :style="{ background: 'white', padding: '25px', borderRadius: '12px', boxShadow: '0 5px 20px rgba(0, 0, 0, 0.08)', marginBottom: '15px' }">
                <div :style="{ textAlign: 'center', marginBottom: '20px', display: 'flex', alignItems: 'flex-start', justifyContent: 'center', gap: '5px' }">
                  <span :style="{ fontSize: '1.5rem', fontWeight: '700', color: '#4e73df', marginTop: '5px' }">₱</span>
                  <span :style="{ fontSize: '3.5rem', fontWeight: '800', color: '#4e73df', lineHeight: '1' }">99</span>
                  <span :style="{ fontSize: '1rem', color: '#858796', alignSelf: 'flex-end', marginBottom: '10px' }">/month</span>
                </div>
                <ul :style="{ listStyle: 'none', padding: 0, margin: 0 }">
                  <li :style="{ padding: '10px 0', color: '#2e3a59', borderBottom: '1px solid #f0f0f0' }">✓ Post unlimited tasks</li>
                  <li :style="{ padding: '10px 0', color: '#2e3a59', borderBottom: '1px solid #f0f0f0' }">✓ Apply to unlimited tasks</li>
                  <li :style="{ padding: '10px 0', color: '#2e3a59', borderBottom: '1px solid #f0f0f0' }">✓ Priority support</li>
                  <li :style="{ padding: '10px 0', color: '#2e3a59' }">✓ Access to both roles anytime</li>
                </ul>
              </div>
              <p :style="{ textAlign: 'center', color: '#858796', fontSize: '0.85rem', marginTop: '15px' }">
                <small>You can upgrade anytime during or after your trial period.</small>
              </p>
            </div>
          </div>
        </div>
        <div :style="modalFooterStyle">
          <button :style="btnSecondaryStyle" @click="handleCancelTrial">
            Choose Different Role
          </button>
          <button :style="btnPrimaryStyle" @click="handleConfirmTrial">
            Continue to Terms
          </button>
        </div>
      </div>
    </div>

    <!-- Terms Modal -->
    <div v-if="showTermsModal" :style="simpleModalStyle" @click="showTermsModal = false">
      <div :style="simpleModalContentStyle" @click.stop>
        <h3 :style="{ color: '#2e3a59', marginBottom: '15px', fontSize: '1.3rem' }">📋 Terms of Service & Privacy Policy</h3>
        <p :style="{ color: '#858796', lineHeight: '1.6', marginBottom: '15px', fontSize: '0.9rem' }">
          <strong>Terms of Service:</strong> By using BayadNihan, you agree to be a verified CARSU student, post legitimate tasks following school guidelines, handle payments responsibly, and maintain respectful communication. Any fraudulent activity will result in account suspension.
        </p>
        <p :style="{ color: '#858796', lineHeight: '1.6', marginBottom: '15px', fontSize: '0.9rem' }">
          <strong>Privacy Policy:</strong> Your student ID and email are used for verification only. Profile information is visible to other verified users. We do not share your personal information with third parties. You can request account deletion at any time.
        </p>
        <p :style="{ color: '#4e73df', lineHeight: '1.6', marginBottom: '15px', fontSize: '0.9rem', textAlign: 'center' }">
          <NuxtLink to="/footer/terms" target="_blank" :style="{ ...linkStyle, marginRight: '10px' }">Read Full Terms of Service</NuxtLink> | 
          <NuxtLink to="/footer/privacy" target="_blank" :style="{ ...linkStyle, marginLeft: '10px' }">Read Full Privacy Policy</NuxtLink>
        </p>
        <div :style="simpleModalCheckboxStyle">
          <input 
            type="checkbox" 
            id="agreeCheckbox"
            v-model="agreeToTerms"
          />
          <label for="agreeCheckbox" :style="{ color: '#2e3a59', fontSize: '0.9rem', cursor: 'pointer' }">
            I have read and agree to the Terms of Service and Privacy Policy
          </label>
        </div>
        <div :style="simpleModalButtonsStyle">
          <button 
            :style="cancelButtonStyle"
            @click="showTermsModal = false"
          >
            Cancel
          </button>
          <button 
            :style="acceptButtonStyle"
            @click="handleAcceptTerms"
            :disabled="!agreeToTerms"
          >
            Accept & Continue
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';

const router = useRouter();
const { authAPI } = useAPI();

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isLoading = ref(false);
const formData = ref({
  username: '',
  email: '@carsu.edu.ph',
  password: '',
  password_confirmation: '',
  role: 'both',
  phone_number: ''
});
const PASSWORD_PATTERN = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/;
const MIN_PASSWORD_LENGTH = 6;

const validation = ref({
  username: { isValid: false, message: '', visible: false },
  email: { isValid: false, message: '', visible: false },
  password: { isValid: false, message: '' },
  passwordMatch: { isValid: false, message: '', visible: false }
});
const passwordStrength = ref({
  strength: 0,
  class: '',
  requirement: 'At least 6 characters with 1 uppercase, 1 number, and 1 special character',
  requirementClass: ''
});
const showTrialModal = ref(false);
const showTermsModal = ref(false);
const agreeToTerms = ref(false);
const formSubmitting = ref(false);
const serverMessages = ref({
  success: '',
  error: ''
});

const calculatePasswordStrength = (password) => {
  let strength = 0;
  if (password.length >= 8) strength++;
  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
  if (password.match(/\d/)) strength++;
  if (password.match(/[^a-zA-Z\d]/)) strength++;

  let strengthClass = '';
  if (password.length > 0) {
    if (strength <= 2) strengthClass = 'weak';
    else if (strength <= 3) strengthClass = 'medium';
    else strengthClass = 'strong';
  }

  const hasLength = password.length >= MIN_PASSWORD_LENGTH;
  const hasUpper = /[A-Z]/.test(password);
  const hasNumber = /\d/.test(password);
  const hasSpecial = /[^A-Za-z0-9]/.test(password);

  const unmet = [];
  if (!hasLength) {
    unmet.push(`at least ${MIN_PASSWORD_LENGTH} characters (${password.length}/${MIN_PASSWORD_LENGTH})`);
  }
  if (!hasUpper) unmet.push('one uppercase letter');
  if (!hasNumber) unmet.push('one number');
  if (!hasSpecial) unmet.push('one special character');

  let requirement = '';
  let requirementClass = '';

  if (password.length === 0) {
    requirement = 'At least 6 characters with 1 uppercase, 1 number, and 1 special character';
  } else if (unmet.length === 0) {
    requirement = 'A Strong Password';
    requirementClass = 'valid';
  } else {
    requirement = `Needs ${unmet.join(', ')}`;
    requirementClass = 'invalid';
  }

  return { strength, class: strengthClass, requirement, requirementClass };
};

const validateUsername = (username) => {
  if (username.length === 0) {
    return { isValid: false, message: '', visible: false };
  }
  const isValid = /^\d{3}-\d{5}$/.test(username);
  return {
    isValid,
    message: isValid ? 'Valid ID format' : 'Format: 000-00000 (e.g., 231-00123)',
    visible: true
  };
};

const validateEmail = (email) => {
  if (email.length === 0) {
    return { isValid: false, message: '', visible: false };
  }
  const isValid = /^[A-Za-z0-9._%+-]+@carsu\.edu\.ph$/i.test(email);
  return {
    isValid,
    message: isValid ? 'Valid CARSU email' : 'Must use @carsu.edu.ph email',
    visible: true
  };
};

const validatePassword = (password) => {
  if (password.length === 0) {
    return { isValid: false, message: '' };
  }

  const isValid = PASSWORD_PATTERN.test(password);
  return {
    isValid,
    message: isValid 
      ? 'Password meets requirements' 
      : 'Must be 6+ chars with 1 uppercase, 1 number, and 1 special character'
  };
};

const validatePasswordMatch = (password, confirmPassword) => {
  if (confirmPassword.length === 0) {
    return { isValid: false, message: '', visible: false };
  }
  const isValid = password === confirmPassword;
  return {
    isValid,
    message: isValid ? 'Passwords match' : 'Passwords do not match',
    visible: true
  };
};

watch(() => formData.value, () => {
  validation.value = {
    username: validateUsername(formData.value.username),
    email: validateEmail(formData.value.email),
    password: validatePassword(formData.value.password),
    passwordMatch: validatePasswordMatch(formData.value.password, formData.value.password_confirmation)
  };

  passwordStrength.value = calculatePasswordStrength(formData.value.password);
}, { deep: true });

watch(() => [validation.value, isLoading.value], () => {
  if (process.client) {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
      submitBtn.disabled = !isFormValid() || isLoading.value;
    }
  }
});

const handleInputChange = (e) => {
  const { name, value } = e.target;
  formData.value = {
    ...formData.value,
    [name]: value
  };
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const toggleConfirmPasswordVisibility = () => {
  showConfirmPassword.value = !showConfirmPassword.value;
};

const handleRoleChange = (role) => {
  formData.value.role = role;
};

const handleRoleHover = (event, role, isEnter) => {
  if (formData.value.role !== role) {
    if (isEnter) {
      event.currentTarget.style.borderColor = '#4e73df';
      event.currentTarget.style.transform = 'translateY(-2px)';
    } else {
      event.currentTarget.style.borderColor = '#e8eaf6';
      event.currentTarget.style.transform = 'translateY(0)';
    }
  }
};

const isFormValid = () => {
  return validation.value.username.isValid && 
         validation.value.email.isValid && 
         validation.value.password.isValid && 
         validation.value.passwordMatch.isValid;
};

const handleSubmit = (e) => {
  if (formSubmitting.value) return;

  if (!isFormValid()) {
    alert('Please fix the validation errors before submitting.');
    return;
  }

  if (formData.value.role === 'both') {
    showTrialModal.value = true;
  } else {
    showTermsModal.value = true;
  }
};

const handleConfirmTrial = () => {
  showTrialModal.value = false;
  showTermsModal.value = true;
};

const handleCancelTrial = () => {
  showTrialModal.value = false;
};

const handleAcceptTerms = async () => {
  formSubmitting.value = true;
  showTermsModal.value = false;
  isLoading.value = true;
  serverMessages.value = { success: '', error: '' };
  
  try {
    const response = await authAPI.register({
      username: formData.value.username,
      email: formData.value.email,
      password: formData.value.password,
      password_confirmation: formData.value.password_confirmation,
      role: formData.value.role,
      phone_number: formData.value.phone_number || null,
    });
    
    if (response.success || response.message) {
      serverMessages.value = { 
        success: response.message || 'Registration successful! Please check your email to verify your account.',
        error: '' 
      };
      
      setTimeout(() => {
        router.push('/auth/login');
      }, 3000);
    } else {
      serverMessages.value = { 
        success: '', 
        error: response.error || 'Registration failed. Please try again.' 
      };
      isLoading.value = false;
      formSubmitting.value = false;
    }
  } catch (error) {
    const errorMessage = error.message || 'An error occurred during registration';
    serverMessages.value = { 
      success: '', 
      error: errorMessage 
    };
    isLoading.value = false;
    formSubmitting.value = false;
  }
};

const navigateTo = (path) => {
  router.push(path);
};

const getInputStyle = (field) => {
  const baseStyle = { ...inputStyle };
  if (field === 'username' && validation.value.username.visible) {
    if (validation.value.username.isValid) {
      return { ...baseStyle, ...inputValidStyle };
    } else {
      return { ...baseStyle, ...inputInvalidStyle };
    }
  }
  if (field === 'email' && validation.value.email.visible) {
    if (validation.value.email.isValid) {
      return { ...baseStyle, ...inputValidStyle };
    } else {
      return { ...baseStyle, ...inputInvalidStyle };
    }
  }
  return baseStyle;
};

const getPasswordInputStyle = () => {
  const baseStyle = { ...inputStyle };
  if (formData.value.password && validation.value.password.isValid) {
    return { ...baseStyle, ...inputValidStyle };
  } else if (formData.value.password && !validation.value.password.isValid) {
    return { ...baseStyle, ...inputInvalidStyle };
  }
  return baseStyle;
};

const getConfirmPasswordInputStyle = () => {
  const baseStyle = { ...inputStyle };
  if (validation.value.passwordMatch.visible && validation.value.passwordMatch.isValid) {
    return { ...baseStyle, ...inputValidStyle };
  } else if (validation.value.passwordMatch.visible && !validation.value.passwordMatch.isValid) {
    return { ...baseStyle, ...inputInvalidStyle };
  }
  return baseStyle;
};

const getValidationIndicatorStyle = (field) => {
  const baseStyle = { ...validationIndicatorStyle };
  if (field === 'username' && validation.value.username.visible) {
    return {
      ...baseStyle,
      ...validationIndicatorVisibleStyle,
      ...(validation.value.username.isValid ? validationValidStyle : validationInvalidStyle)
    };
  }
  if (field === 'email' && validation.value.email.visible) {
    return {
      ...baseStyle,
      ...validationIndicatorVisibleStyle,
      ...(validation.value.email.isValid ? validationValidStyle : validationInvalidStyle)
    };
  }
  return baseStyle;
};

const getPasswordStrengthBarStyle = () => {
  const baseStyle = { ...passwordStrengthBarStyle };
  if (passwordStrength.value.class === 'weak') {
    return { ...baseStyle, ...passwordStrengthWeakStyle };
  } else if (passwordStrength.value.class === 'medium') {
    return { ...baseStyle, ...passwordStrengthMediumStyle };
  } else if (passwordStrength.value.class === 'strong') {
    return { ...baseStyle, ...passwordStrengthStrongStyle };
  }
  return baseStyle;
};

const getPasswordRequirementStyle = () => {
  const baseStyle = { ...passwordRequirementStyle };
  if (passwordStrength.value.requirementClass === 'valid') {
    return { ...baseStyle, ...passwordRequirementValidStyle };
  } else if (passwordStrength.value.requirementClass === 'invalid') {
    return { ...baseStyle, ...passwordRequirementInvalidStyle };
  }
  return baseStyle;
};

const getPasswordMatchStyle = () => {
  const baseStyle = { ...passwordMatchStyle };
  if (validation.value.passwordMatch.visible) {
    return {
      ...baseStyle,
      ...passwordMatchVisibleStyle,
      ...(validation.value.passwordMatch.isValid ? passwordMatchMatchStyle : passwordMatchMismatchStyle)
    };
  }
  return baseStyle;
};

const getRoleOptionStyle = (role) => {
  const baseStyle = { ...roleOptionStyle };
  if (formData.value.role === role) {
    return { ...baseStyle, ...roleOptionSelectedStyle };
  }
  return baseStyle;
};

const getSubmitButtonStyle = () => {
  const baseStyle = { ...btnStyle };
  if (!isFormValid() || isLoading.value) {
    return { ...baseStyle, ...btnDisabledStyle };
  }
  return baseStyle;
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
    .role-options {
      grid-template-columns: 1fr;
    }
    h2 {
      font-size: 1.5rem;
    }
    .modal-footer {
      flex-direction: column;
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
  maxWidth: '480px',
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
const logoIconStyle = { fontSize: '2.5rem', marginBottom: '10px' };
const logoH1Style = { color: '#2e3a59', fontSize: '1.8rem', marginBottom: '5px' };
const logoPStyle = { color: '#858796', fontSize: '0.9rem' };
const h2Style = { color: '#2e3a59', textAlign: 'center', marginBottom: '30px', fontSize: '1.8rem', fontWeight: '600' };
const formGroupStyle = { marginBottom: '20px', position: 'relative' };
const labelStyle = { display: 'flex', position: 'flex-start', marginBottom: '8px', fontWeight: '500', fontSize: '0.9rem', color: '#080809ff' };
const inputStyle = { width: '100%', padding: '14px 16px', border: '2px solid #e8eaf6', borderRadius: '12px', fontSize: '1rem', transition: 'all 0.3s ease', background: '#f8f9fc', color: '#080809ff' };
const inputValidStyle = { borderColor: '#1cc88a' };
const inputInvalidStyle = { borderColor: '#e74a3b' };
const inputWithIconStyle = { position: 'relative' };
const inputIconStyle = { position: 'absolute', right: '16px', top: '50%', transform: 'translateY(-50%)', color: '#858796', cursor: 'pointer', fontSize: '1.1rem', background: 'none', border: 'none', padding: '5px' };
const passwordStrengthStyle = { marginTop: '8px', height: '4px', background: '#e8eaf6', borderRadius: '2px', overflow: 'hidden' };
const passwordStrengthBarStyle = { height: '100%', width: '0%', transition: 'all 0.3s ease', borderRadius: '2px' };
const passwordStrengthWeakStyle = { background: '#e74a3b', width: '33%' };
const passwordStrengthMediumStyle = { background: '#f6c23e', width: '66%' };
const passwordStrengthStrongStyle = { background: '#1cc88a', width: '100%' };
const passwordRequirementStyle = { marginTop: '8px', fontSize: '0.8rem', color: '#858796', display: 'flex', alignItems: 'center', gap: '5px' };
const passwordRequirementValidStyle = { color: '#1cc88a' };
const passwordRequirementInvalidStyle = { color: '#e74a3b' };
const requirementIconStyle = { fontSize: '0.9rem' };
const requirementTextStyle = { fontSize: '0.8rem' };
const passwordMatchStyle = { marginTop: '5px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '5px', opacity: 0, transition: 'opacity 0.3s ease' };
const passwordMatchVisibleStyle = { opacity: 1 };
const passwordMatchMatchStyle = { color: '#1cc88a' };
const passwordMatchMismatchStyle = { color: '#e74a3b' };
const matchIconStyle = { fontSize: '0.9rem' };
const matchTextStyle = { fontSize: '0.8rem' };
const validationIndicatorStyle = { marginTop: '5px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '5px', opacity: 0, transition: 'opacity 0.3s ease' };
const validationIndicatorVisibleStyle = { opacity: 1 };
const validationValidStyle = { color: '#1cc88a' };
const validationInvalidStyle = { color: '#e74a3b' };
const indicatorIconStyle = { fontSize: '0.9rem' };
const indicatorTextStyle = { fontSize: '0.8rem' };
const roleOptionsStyle = { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '10px', marginBottom: '20px' };
const roleOptionStyle = { textAlign: 'center', padding: '15px 10px', border: '2px solid #e8eaf6', borderRadius: '12px', cursor: 'pointer', transition: 'all 0.3s ease', background: '#f8f9fc' };
const roleOptionSelectedStyle = { borderColor: '#4e73df', background: 'rgba(78, 115, 223, 0.1)', transform: 'translateY(-2px)' };
const roleIconStyle = { fontSize: '1.5rem', marginBottom: '8px' };
const roleNameStyle = { fontWeight: '600', color: '#2e3a59', fontSize: '0.9rem' };
const roleDescStyle = { fontSize: '0.75rem', color: '#858796', marginTop: '4px' };
const btnStyle = { width: '100%', padding: '16px', background: '#4e73df', color: 'white', border: 'none', borderRadius: '12px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', marginTop: '10px' };
const btnDisabledStyle = { background: '#858796', cursor: 'not-allowed', transform: 'none', boxShadow: 'none' };
const errorStyle = { background: '#f8d7da', color: '#721c24', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', borderLeft: '4px solid #e74a3b' };
const successStyle = { background: '#d1edff', color: '#0c5460', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', borderLeft: '4px solid #4e73df' };
const loginLinkStyle = { textAlign: 'center', marginTop: '25px', color: '#858796' };
const termsStyle = { textAlign: 'center', marginTop: '20px', fontSize: '0.8rem', color: '#858796' };
const linkStyle = { color: '#4e73df', textDecoration: 'none', fontWeight: '600' };
const modalStyle = { display: 'flex', position: 'fixed', zIndex: 2000, left: 0, top: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0, 0, 0, 0.6)', backdropFilter: 'blur(5px)', alignItems: 'center', justifyContent: 'center' };
const modalContentStyle = { background: 'white', borderRadius: '20px', maxWidth: '550px', width: '90%', maxHeight: '90vh', overflowY: 'auto', boxShadow: '0 25px 80px rgba(0, 0, 0, 0.3)' };
const modalHeaderStyle = { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', padding: '30px', borderRadius: '20px 20px 0 0', textAlign: 'center' };
const modalBodyStyle = { padding: '30px' };
const trialBadgeStyle = { background: 'linear-gradient(135deg, #1cc88a, #17a673)', color: 'white', display: 'inline-block', padding: '20px 40px', borderRadius: '15px', marginBottom: '25px', boxShadow: '0 10px 30px rgba(28, 200, 138, 0.3)' };
const trialDaysStyle = { display: 'block', fontSize: '2.5rem', fontWeight: '800', lineHeight: '1', marginBottom: '5px' };
const trialLabelStyle = { display: 'block', fontSize: '0.9rem', fontWeight: '600', letterSpacing: '2px', opacity: '0.95' };
const modalFooterStyle = { padding: '20px 30px 30px', display: 'flex', gap: '15px', justifyContent: 'space-between' };
const btnSecondaryStyle = { flex: '1', padding: '15px 25px', background: '#e8eaf6', color: '#2e3a59', border: 'none', borderRadius: '12px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease' };
const btnPrimaryStyle = { flex: '1', padding: '15px 25px', background: 'linear-gradient(135deg, #4e73df, #5a67d8)', color: 'white', border: 'none', borderRadius: '12px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', boxShadow: '0 5px 20px rgba(78, 115, 223, 0.3)' };
const simpleModalStyle = { display: 'flex', position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0, 0, 0, 0.7)', zIndex: 9999, alignItems: 'center', justifyContent: 'center' };
const simpleModalContentStyle = { background: 'white', borderRadius: '12px', maxWidth: '500px', width: '90%', maxHeight: '70vh', overflowY: 'auto', padding: '30px', boxShadow: '0 10px 40px rgba(0, 0, 0, 0.3)' };
const simpleModalCheckboxStyle = { display: 'flex', alignItems: 'center', gap: '10px', margin: '20px 0', padding: '15px', background: '#f8f9fc', borderRadius: '8px' };
const simpleModalButtonsStyle = { display: 'flex', gap: '10px', marginTop: '20px' };
const simpleModalBtnStyle = { flex: '1', padding: '12px', border: 'none', borderRadius: '8px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.2s' };
const simpleModalBtnAcceptStyle = { background: '#4e73df', color: 'white' };
const simpleModalBtnAcceptDisabledStyle = { background: '#858796', cursor: 'not-allowed', opacity: '0.5' };
const simpleModalBtnCancelStyle = { background: '#858796', color: 'white' };

// Computed styles for merged objects
const cancelButtonStyle = computed(() => ({
  ...simpleModalBtnStyle,
  ...simpleModalBtnCancelStyle
}));

const acceptButtonStyle = computed(() => ({
  ...simpleModalBtnStyle,
  ...simpleModalBtnAcceptStyle,
  ...(!agreeToTerms.value && simpleModalBtnAcceptDisabledStyle)
}));
</script>

