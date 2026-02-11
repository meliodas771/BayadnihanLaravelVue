<template>
  <div :style="wrapperStyle">
    <div :style="pageStyle">
      <div :style="containerStyle">
        <div :style="headerStyle">
          <h1 :style="titleStyle">Contact Us</h1>
          <p :style="subtitleStyle">Get in touch with the BayadNihan team</p>
        </div>

      <div :style="contentGridStyle">
        <div :style="contactInfoCardStyle">
          <h2 :style="sectionTitleStyle">Contact Information</h2>
          
          <div :style="contactItemCardStyle">
            <div :style="contactIconStyle">📍</div>
            <div>
              <h3 :style="contactLabelStyle">Address</h3>
              <p :style="contactValueStyle">Caraga State University, Ampayon Butuan City</p>
            </div>
          </div>

          <div :style="contactItemCardStyle">
            <div :style="contactIconStyle">✉️</div>
            <div>
              <h3 :style="contactLabelStyle">Email</h3>
              <p :style="contactValueStyle">bayadnihan@gmail.com</p>
            </div>
          </div>

          <div :style="contactItemCardStyle">
            <div :style="contactIconStyle">📞</div>
            <div>
              <h3 :style="contactLabelStyle">Phone</h3>
              <p :style="contactValueStyle">(+63) 912 345 6789</p>
            </div>
          </div>

          <div :style="socialSectionStyle">
            <h3 :style="socialTitleStyle">Follow Us</h3>
            <div :style="socialLinksStyle">
              <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" :style="socialLinkStyle">📘 Facebook</a>
              <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" :style="socialLinkStyle">X Twitter</a>
              <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" :style="socialLinkStyle">📷 Instagram</a>
            </div>
          </div>
        </div>

        <div :style="formCardStyle">
          <h2 :style="sectionTitleStyle">Send us a Message</h2>
          <form @submit.prevent="handleSubmit" :style="formStyle">
            <div :style="formGroupStyle">
              <label :style="labelStyle">Name</label>
              <input 
                v-model="formData.name" 
                type="text" 
                required 
                :style="inputStyle"
                placeholder="Your name"
              />
            </div>

            <div :style="formGroupStyle">
              <label :style="labelStyle">Email</label>
              <input 
                v-model="formData.email" 
                type="email" 
                required 
                :style="inputStyle"
                placeholder="your.email@carsu.edu.ph"
              />
            </div>

            <div :style="formGroupStyle">
              <label :style="labelStyle">Subject</label>
              <input 
                v-model="formData.subject" 
                type="text" 
                required 
                :style="inputStyle"
                placeholder="What is this about?"
              />
            </div>

            <div :style="formGroupStyle">
              <label :style="labelStyle">Message</label>
              <textarea 
                v-model="formData.message" 
                required 
                :style="textareaStyle"
                rows="6"
                placeholder="Tell us how we can help..."
              ></textarea>
            </div>

            <button type="submit" :style="submitButtonStyle" :disabled="isSubmitting">
              {{ isSubmitting ? 'Sending...' : 'Send Message' }}
            </button>

            <div v-if="submitMessage" :style="messageStyle">
              {{ submitMessage }}
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const formData = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
});

const isSubmitting = ref(false);
const submitMessage = ref('');
const isMobile = ref(false);

const checkMobile = () => {
  if (process.client) {
    isMobile.value = window.innerWidth <= 768;
  }
};

onMounted(() => {
  checkMobile();
  if (process.client) {
    window.addEventListener('resize', checkMobile);
  }
});

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('resize', checkMobile);
  }
});

const handleSubmit = async () => {
  isSubmitting.value = true;
  submitMessage.value = '';

  try {
    const config = useRuntimeConfig();
    const apiBase = config.public.apiBaseUrl || '';

    const response = await fetch(`${apiBase}/contact`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: formData.value.name,
        email: formData.value.email,
        subject: formData.value.subject,
        message: formData.value.message
      })
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data?.message || data?.statusMessage || 'Failed to send message');
    }

    submitMessage.value = 'Thank you for contacting us! We\'ll get back to you soon.';
    formData.value = {
      name: '',
      email: '',
      subject: '',
      message: ''
    };

    setTimeout(() => {
      submitMessage.value = '';
    }, 5000);
  } catch (error) {
    console.error('Contact form error:', error);
    submitMessage.value = 'Failed to send message. Please try again later.';
  } finally {
    isSubmitting.value = false;
  }
};

// Styles
const wrapperStyle = {
  width: '100%',
  maxWidth: '100vw',
  overflowX: 'hidden',
  position: 'relative'
};

const pageStyle = computed(() => ({
  minHeight: '100vh',
  background: '#f8f9fc',
  padding: isMobile.value ? '80px 0 30px' : '100px 0 50px',
  width: '100%',
  overflowX: 'hidden'
}));

const containerStyle = computed(() => ({
  maxWidth: '1200px',
  margin: '0 auto',
  padding: isMobile.value ? '0 15px' : '0 20px',
  width: '100%',
  boxSizing: 'border-box'
}));

const headerStyle = computed(() => ({
  textAlign: 'center',
  marginBottom: isMobile.value ? '2rem' : '3rem'
}));

const titleStyle = computed(() => ({
  fontSize: isMobile.value ? '1.8rem' : '2.5rem',
  color: '#2e3a59',
  marginBottom: '1rem',
  fontWeight: '600'
}));

const subtitleStyle = computed(() => ({
  fontSize: isMobile.value ? '1rem' : '1.2rem',
  color: '#858796'
}));

const contentGridStyle = computed(() => ({
  display: 'grid',
  gridTemplateColumns: isMobile.value ? '1fr' : 'repeat(auto-fit, minmax(400px, 1fr))',
  gap: isMobile.value ? '1.5rem' : '2rem'
}));

const contactInfoCardStyle = computed(() => ({
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  padding: isMobile.value ? '1.5rem' : '2rem'
}));

const sectionTitleStyle = computed(() => ({
  margin: isMobile.value ? '0 0 1.5rem 0' : '0 0 2rem 0',
  color: '#2e3a59',
  fontSize: isMobile.value ? '1.3rem' : '1.5rem',
  fontWeight: '600'
}));

const contactItemCardStyle = computed(() => ({
  display: 'flex',
  alignItems: 'flex-start',
  gap: isMobile.value ? '0.75rem' : '1rem',
  marginBottom: isMobile.value ? '1.25rem' : '2rem',
  padding: isMobile.value ? '0.875rem' : '1rem',
  background: '#f8f9fc',
  borderRadius: '8px'
}));

const contactIconStyle = computed(() => ({
  fontSize: isMobile.value ? '1.5rem' : '2rem',
  flexShrink: 0
}));

const contactLabelStyle = computed(() => ({
  margin: '0 0 0.5rem 0',
  color: '#2e3a59',
  fontSize: isMobile.value ? '0.95rem' : '1rem',
  fontWeight: '600'
}));

const contactValueStyle = computed(() => ({
  margin: 0,
  color: '#5a5c69',
  fontSize: isMobile.value ? '14px' : '15px',
  lineHeight: '1.6',
  wordBreak: 'break-word'
}));

const socialSectionStyle = computed(() => ({
  marginTop: isMobile.value ? '1.5rem' : '2rem',
  paddingTop: isMobile.value ? '1.5rem' : '2rem',
  borderTop: '1px solid #e3e6f0'
}));

const socialTitleStyle = computed(() => ({
  margin: '0 0 1rem 0',
  color: '#2e3a59',
  fontSize: isMobile.value ? '1.1rem' : '1.2rem',
  fontWeight: '600'
}));

const socialLinksStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '0.75rem'
};

const socialLinkStyle = computed(() => ({
  color: '#4e73df',
  textDecoration: 'none',
  fontSize: isMobile.value ? '14px' : '15px',
  transition: 'color 0.3s',
  padding: '0.5rem',
  borderRadius: '6px',
  display: 'flex',
  alignItems: 'center',
  gap: '0.5rem'
}));

const formCardStyle = computed(() => ({
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  padding: isMobile.value ? '1.5rem' : '2rem'
}));

const formStyle = computed(() => ({
  display: 'flex',
  flexDirection: 'column',
  gap: isMobile.value ? '1.25rem' : '1.5rem'
}));

const formGroupStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '0.5rem'
};

const labelStyle = computed(() => ({
  color: '#2e3a59',
  fontSize: isMobile.value ? '14px' : '15px',
  fontWeight: '600'
}));

const inputStyle = computed(() => ({
  padding: isMobile.value ? '10px 14px' : '12px 16px',
  border: '1px solid #e3e6f0',
  borderRadius: '8px',
  fontSize: isMobile.value ? '14px' : '15px',
  color: '#2e3a59',
  outline: 'none',
  transition: 'border-color 0.3s',
  width: '100%',
  boxSizing: 'border-box'
}));

const textareaStyle = computed(() => ({
  padding: isMobile.value ? '10px 14px' : '12px 16px',
  border: '1px solid #e3e6f0',
  borderRadius: '8px',
  fontSize: isMobile.value ? '14px' : '15px',
  color: '#2e3a59',
  outline: 'none',
  transition: 'border-color 0.3s',
  resize: 'vertical',
  fontFamily: 'inherit',
  width: '100%',
  boxSizing: 'border-box'
}));

const submitButtonStyle = computed(() => ({
  padding: isMobile.value ? '12px 24px' : '14px 32px',
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: 'white',
  border: 'none',
  borderRadius: '8px',
  fontSize: isMobile.value ? '15px' : '16px',
  fontWeight: '600',
  cursor: 'pointer',
  transition: 'all 0.3s ease',
  boxShadow: '0 4px 6px rgba(78, 115, 223, 0.2)',
  width: '100%'
}));

const messageStyle = computed(() => ({
  padding: isMobile.value ? '10px 14px' : '12px 16px',
  borderRadius: '8px',
  background: '#d4edda',
  color: '#155724',
  border: '1px solid #c3e6cb',
  fontSize: isMobile.value ? '13px' : '14px',
  marginTop: '1rem'
}));
</script>

<style scoped>
* {
  box-sizing: border-box;
}
</style>
