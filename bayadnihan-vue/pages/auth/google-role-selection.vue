<template>
  <div>
    <div class="react-container" :style="containerStyle">
      <div :style="containerBeforeStyle"></div>
      
      <div :style="logoStyle">
        <div :style="logoIconStyle">🤝</div>
        <h1 :style="logoH1Style">BayadNihan</h1>
      </div>

      <div :style="welcomeMessageStyle">
        <h2 :style="welcomeH2Style">Welcome! Choose Your Role</h2>
        <p :style="welcomePStyle">Select how you'd like to participate in our student community</p>
      </div>

      <div :style="userInfoStyle">
        <div :style="userInfoIconStyle">👤</div>
        <div :style="userInfoTextStyle">
          <div :style="userNameStyle">{{ googleUser.name }}</div>
          <div :style="userEmailStyle">{{ googleUser.email }}</div>
        </div>
      </div>

      <div v-if="serverMessages.error" :style="errorStyle">
        {{ serverMessages.error }}
      </div>

      <form @submit.prevent="handleSubmit" id="roleForm">
        <div :style="roleOptionsStyle">
          <label 
            v-for="role in roleOptions"
            :key="role.value"
            :style="getRoleOptionStyle(role.value)"
            @click="handleRoleSelect(role.value)"
          >
            <input 
              type="radio" 
              name="role" 
              :value="role.value"
              v-model="selectedRole"
              @change="handleRoleSelect(role.value)"
              style="display: none"
              required 
            />
            <div v-if="selectedRole === role.value" :style="roleOptionSelectedAfterStyle">✓</div>
            <div :style="roleIconStyle">{{ role.icon }}</div>
            <div :style="roleNameStyle">{{ role.name }}</div>
            <div :style="roleDescStyle">{{ role.desc }}</div>
          </label>
        </div>

        <button 
          type="submit" 
          :style="submitButtonStyle"
          :disabled="!selectedRole || isLoading"
        >
          {{ isLoading ? 'Creating Your Account...' : 'Continue' }}
        </button>
      </form>
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
            Start Free Trial
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

useHead({
  style: [
    {
      children: globalStyles + keyframes
    }
  ]
});

const router = useRouter();
const route = useRoute();
const { login } = useUser();
const { googleAuthAPI } = useAPI();

const selectedRole = ref(null);
const isLoading = ref(false);
const showTrialModal = ref(false);
const serverMessages = ref({
  error: '',
  success: ''
});

const googleUser = ref({
  name: '',
  email: '',
  google_id: '',
  avatar: ''
});

const roleOptions = [
  { value: 'both', icon: '👥', name: 'Both', desc: 'Post tasks and complete tasks from others' },
  { value: 'poster', icon: '📋', name: 'Poster', desc: 'Create and manage tasks for others' },
  { value: 'doer', icon: '💰', name: 'Doer', desc: 'Complete tasks and earn rewards' }
];

onMounted(() => {
  if (process.client) {
    // First check query parameter (from backend redirect)
    const route = useRoute();
    const data = route.query.data;
    
    if (data) {
      try {
        // Decode base64 data from backend
        const decodedData = atob(decodeURIComponent(data));
        const parsed = JSON.parse(decodedData);
        // Ensure all fields are set
        googleUser.value = {
          name: parsed.name || '',
          email: parsed.email || '',
          google_id: parsed.google_id || parsed.googleId || '',
          avatar: parsed.avatar || ''
        };
        // Store in sessionStorage for persistence
        sessionStorage.setItem('google_user', decodedData);
        console.log('Google user data loaded:', googleUser.value);
      } catch (e) {
        console.error('Error parsing Google data from query:', e);
        // Fall back to sessionStorage
        const storedGoogleUser = sessionStorage.getItem('google_user');
        if (storedGoogleUser) {
          try {
            const parsed = JSON.parse(storedGoogleUser);
            googleUser.value = {
              name: parsed.name || '',
              email: parsed.email || '',
              google_id: parsed.google_id || parsed.googleId || '',
              avatar: parsed.avatar || ''
            };
            console.log('Google user data loaded from sessionStorage:', googleUser.value);
          } catch (e2) {
            console.error('Error parsing stored Google user:', e2);
          }
        }
      }
    } else {
      // Check sessionStorage if no query data
      const storedGoogleUser = sessionStorage.getItem('google_user');
      if (storedGoogleUser) {
        try {
          const parsed = JSON.parse(storedGoogleUser);
          googleUser.value = {
            name: parsed.name || '',
            email: parsed.email || '',
            google_id: parsed.google_id || parsed.googleId || '',
            avatar: parsed.avatar || ''
          };
          console.log('Google user data loaded from sessionStorage:', googleUser.value);
        } catch (e) {
          console.error('Error parsing stored Google user:', e);
        }
      }
    }
  }
});

const handleRoleSelect = (role) => {
  selectedRole.value = role;
};

const handleSubmit = async (e) => {
  e.preventDefault();
  
  if (!selectedRole.value) {
    serverMessages.value = {
      error: 'Please select a role to continue',
      success: ''
    };
    return;
  }

  if (selectedRole.value === 'both') {
    showTrialModal.value = true;
  } else {
    isLoading.value = true;
    await handleFormSubmission();
  }
};

const handleFormSubmission = async () => {
  try {
    // Ensure we have all required Google user data
    if (!googleUser.value.email || !googleUser.value.google_id) {
      serverMessages.value = {
        error: 'Missing Google user data. Please try logging in with Google again.',
        success: ''
      };
      isLoading.value = false;
      return;
    }
    
    const response = await googleAuthAPI.completeRegistration(selectedRole.value, googleUser.value);
    
    if (response.user && response.token) {
      login(response.user, response.token);
      
      if (process.client) {
        sessionStorage.removeItem('google_user');
      }
      
      router.push('/tasks');
    } else {
      serverMessages.value = {
        error: response.message || 'Error completing registration',
        success: ''
      };
      isLoading.value = false;
    }
  } catch (error) {
    console.error('Error completing registration:', error);
    serverMessages.value = {
      error: error.message || 'Error completing registration',
      success: ''
    };
    isLoading.value = false;
  }
};

const handleCancelTrial = () => {
  showTrialModal.value = false;
  selectedRole.value = null;
};

const handleConfirmTrial = async () => {
  showTrialModal.value = false;
  isLoading.value = true;
  await handleFormSubmission();
};

const getRoleOptionStyle = (role) => {
  const baseStyle = { ...roleOptionStyle };
  if (selectedRole.value === role) {
    return { ...baseStyle, ...roleOptionSelectedStyle };
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
  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }
  @keyframes slideUp {
    from {
      transform: translateY(50px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }
  
  @media (max-width: 600px) {
    .react-container {
      padding: 40px 25px;
    }
    
    .role-options {
      grid-template-columns: 1fr;
    }

    .welcome-h2 {
      font-size: 1.4rem;
    }

    .modal-content {
      width: 95%;
    }

    .modal-header, .modal-body, .modal-footer {
      padding: 20px;
    }

    .modal-footer {
      flex-direction: column;
    }

    .price-amount .amount {
      font-size: 2.5rem;
    }

    .trial-days {
      font-size: 2rem;
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
  maxWidth: '600px',
  width: '100%',
  background: 'white',
  padding: '50px 40px',
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
  background: 'linear-gradient(90deg, #4e73df, #1cc88a)'
};

const logoStyle = { textAlign: 'center', marginBottom: '30px' };
const logoIconStyle = { fontSize: '3rem', marginBottom: '10px' };
const logoH1Style = { color: '#2e3a59', fontSize: '2rem', marginBottom: '5px' };
const welcomeMessageStyle = { textAlign: 'center', marginBottom: '40px' };
const welcomeH2Style = { color: '#2e3a59', fontSize: '1.6rem', marginBottom: '10px' };
const welcomePStyle = { color: '#858796', fontSize: '1rem', lineHeight: '1.6' };
const userInfoStyle = { background: '#f8f9fc', padding: '15px 20px', borderRadius: '12px', marginBottom: '30px', display: 'flex', alignItems: 'center', gap: '15px' };
const userInfoIconStyle = { fontSize: '2rem' };
const userInfoTextStyle = { flex: 1 };
const userNameStyle = { fontWeight: '600', color: '#2e3a59', fontSize: '1.1rem' };
const userEmailStyle = { color: '#858796', fontSize: '0.9rem' };
const errorStyle = { background: '#f8d7da', color: '#721c24', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', borderLeft: '4px solid #e74a3b' };
const roleOptionsStyle = { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', marginBottom: '30px' };
const roleOptionStyle = { textAlign: 'center', padding: '25px 15px', border: '3px solid #e8eaf6', borderRadius: '16px', cursor: 'pointer', transition: 'all 0.3s ease', background: '#f8f9fc', position: 'relative' };
const roleOptionSelectedStyle = { borderColor: '#4e73df', background: 'rgba(78, 115, 223, 0.1)', transform: 'translateY(-5px)', boxShadow: '0 10px 25px rgba(78, 115, 223, 0.3)' };
const roleOptionSelectedAfterStyle = { content: '"✓"', position: 'absolute', top: '10px', right: '10px', background: '#4e73df', color: 'white', width: '24px', height: '24px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.9rem', fontWeight: 'bold' };
const roleIconStyle = { fontSize: '2.5rem', marginBottom: '12px' };
const roleNameStyle = { fontWeight: '700', color: '#2e3a59', fontSize: '1.1rem', marginBottom: '8px' };
const roleDescStyle = { fontSize: '0.85rem', color: '#858796', lineHeight: '1.4' };
const btnStyle = { width: '100%', padding: '18px', background: '#4e73df', color: 'white', border: 'none', borderRadius: '12px', fontSize: '1.1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', marginTop: '10px' };
const btnDisabledStyle = { background: '#858796', cursor: 'not-allowed', transform: 'none', boxShadow: 'none', opacity: '0.6' };
const modalStyle = { display: 'flex', position: 'fixed', zIndex: 1000, left: 0, top: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0, 0, 0, 0.6)', backdropFilter: 'blur(5px)', alignItems: 'center', justifyContent: 'center' };
const modalContentStyle = { background: 'white', borderRadius: '20px', maxWidth: '550px', width: '90%', maxHeight: '90vh', overflowY: 'auto', boxShadow: '0 25px 80px rgba(0, 0, 0, 0.3)' };
const modalHeaderStyle = { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', padding: '30px', borderRadius: '20px 20px 0 0', textAlign: 'center' };
const modalBodyStyle = { padding: '30px' };
const trialBadgeStyle = { background: 'linear-gradient(135deg, #1cc88a, #17a673)', color: 'white', display: 'inline-block', padding: '20px 40px', borderRadius: '15px', marginBottom: '25px', boxShadow: '0 10px 30px rgba(28, 200, 138, 0.3)' };
const trialDaysStyle = { display: 'block', fontSize: '2.5rem', fontWeight: '800', lineHeight: '1', marginBottom: '5px' };
const trialLabelStyle = { display: 'block', fontSize: '0.9rem', fontWeight: '600', letterSpacing: '2px', opacity: '0.95' };
const modalFooterStyle = { padding: '20px 30px 30px', display: 'flex', gap: '15px', justifyContent: 'space-between' };
const btnSecondaryStyle = { flex: '1', padding: '15px 25px', background: '#e8eaf6', color: '#2e3a59', border: 'none', borderRadius: '12px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease' };
const btnPrimaryStyle = { flex: '1', padding: '15px 25px', background: 'linear-gradient(135deg, #4e73df, #5a67d8)', color: 'white', border: 'none', borderRadius: '12px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', boxShadow: '0 5px 20px rgba(78, 115, 223, 0.3)' };

// Computed styles for merged objects
const submitButtonStyle = computed(() => ({
  ...btnStyle,
  ...((!selectedRole.value || isLoading.value) && btnDisabledStyle)
}));
</script>

