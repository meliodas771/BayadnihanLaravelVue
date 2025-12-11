<template>
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
</template>

<script setup>
import { ref } from 'vue';

const formData = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
});

const isSubmitting = ref(false);
const submitMessage = ref('');

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
const pageStyle = {
  minHeight: '100vh',
  background: '#f8f9fc',
  padding: '100px 0 50px'
};

const containerStyle = {
  maxWidth: '1200px',
  margin: '0 auto',
  padding: '0 20px'
};

const headerStyle = {
  textAlign: 'center',
  marginBottom: '3rem'
};

const titleStyle = {
  fontSize: '2.5rem',
  color: '#2e3a59',
  marginBottom: '1rem',
  fontWeight: '600'
};

const subtitleStyle = {
  fontSize: '1.2rem',
  color: '#858796'
};

const contentGridStyle = {
  display: 'grid',
  gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
  gap: '2rem'
};

const contactInfoCardStyle = {
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  padding: '2rem'
};

const sectionTitleStyle = {
  margin: '0 0 2rem 0',
  color: '#2e3a59',
  fontSize: '1.5rem',
  fontWeight: '600'
};

const contactItemCardStyle = {
  display: 'flex',
  alignItems: 'flex-start',
  gap: '1rem',
  marginBottom: '2rem',
  padding: '1rem',
  background: '#f8f9fc',
  borderRadius: '8px'
};

const contactIconStyle = {
  fontSize: '2rem',
  flexShrink: 0
};

const contactLabelStyle = {
  margin: '0 0 0.5rem 0',
  color: '#2e3a59',
  fontSize: '1rem',
  fontWeight: '600'
};

const contactValueStyle = {
  margin: 0,
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.6'
};

const socialSectionStyle = {
  marginTop: '2rem',
  paddingTop: '2rem',
  borderTop: '1px solid #e3e6f0'
};

const socialTitleStyle = {
  margin: '0 0 1rem 0',
  color: '#2e3a59',
  fontSize: '1.2rem',
  fontWeight: '600'
};

const socialLinksStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '0.75rem'
};

const socialLinkStyle = {
  color: '#4e73df',
  textDecoration: 'none',
  fontSize: '15px',
  transition: 'color 0.3s',
  padding: '0.5rem',
  borderRadius: '6px'
};

const formCardStyle = {
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  padding: '2rem'
};

const formStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '1.5rem'
};

const formGroupStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '0.5rem'
};

const labelStyle = {
  color: '#2e3a59',
  fontSize: '15px',
  fontWeight: '600'
};

const inputStyle = {
  padding: '12px 16px',
  border: '1px solid #e3e6f0',
  borderRadius: '8px',
  fontSize: '15px',
  color: '#2e3a59',
  outline: 'none',
  transition: 'border-color 0.3s'
};

const textareaStyle = {
  ...inputStyle,
  resize: 'vertical',
  fontFamily: 'inherit'
};

const submitButtonStyle = {
  padding: '14px 32px',
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: 'white',
  border: 'none',
  borderRadius: '8px',
  fontSize: '16px',
  fontWeight: '600',
  cursor: 'pointer',
  transition: 'all 0.3s ease',
  boxShadow: '0 4px 6px rgba(78, 115, 223, 0.2)'
};

const messageStyle = {
  padding: '12px 16px',
  borderRadius: '8px',
  background: '#d4edda',
  color: '#155724',
  border: '1px solid #c3e6cb',
  fontSize: '14px',
  marginTop: '1rem'
};
</script>
