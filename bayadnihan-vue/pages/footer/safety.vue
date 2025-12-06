<template>
  <div :style="pageStyle">
    <div :style="containerStyle">
      <div :style="headerStyle">
        <h1 :style="titleStyle">Safety Guidelines</h1>
        <p :style="subtitleStyle">Your safety is our priority. Please follow these guidelines when using BayadNihan.</p>
      </div>

      <div :style="guidelinesContainerStyle">
        <div v-for="(section, index) in guidelines" :key="index" :style="sectionStyle">
          <div :style="sectionHeaderStyle">
            <span :style="sectionIconStyle">{{ section.icon }}</span>
            <h2 :style="sectionTitleStyle">{{ section.title }}</h2>
          </div>
          <div :style="sectionContentStyle">
            <ul :style="guidelinesListStyle">
              <li v-for="(guideline, gIndex) in section.guidelines" :key="gIndex" :style="guidelineItemStyle">
                <span :style="bulletStyle">•</span>
                <span :style="guidelineTextStyle">{{ guideline }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div :style="reportSectionStyle">
        <h2 :style="reportTitleStyle">🚨 Report Issues</h2>
        <p :style="reportTextStyle">
          If you encounter any suspicious behavior, harassment, or safety concerns, please report it immediately through our reporting system. 
          We take all reports seriously and will investigate promptly.
        </p>
        <NuxtLink to="/report/create" :style="reportButtonStyle">Report an Issue</NuxtLink>
      </div>

      <div :style="contactSectionStyle">
        <h2 :style="contactTitleStyle">Need Help?</h2>
        <p :style="contactTextStyle">
          If you have safety concerns or need assistance, contact our support team:
        </p>
        <div :style="contactInfoStyle">
          <p :style="contactItemStyle"><span>✉️</span> support@bayadnihan.com</p>
          <p :style="contactItemStyle"><span>📞</span> (+63) 912 345 6789</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const guidelines = [
  {
    icon: '🔐',
    title: 'Account Security',
    guidelines: [
      'Never share your password with anyone',
      'Use a strong, unique password for your account',
      'Log out from shared or public computers',
      'Report any suspicious activity on your account immediately',
      'Keep your contact information up to date'
    ]
  },
  {
    icon: '💬',
    title: 'Communication Safety',
    guidelines: [
      'Use the in-app messaging system for all communications',
      'Never share personal information like home address or financial details',
      'Be respectful and professional in all interactions',
      'If someone makes you uncomfortable, stop communicating and report them',
      'Meet in public, well-lit areas when tasks require in-person interaction'
    ]
  },
  {
    icon: '💰',
    title: 'Payment Safety',
    guidelines: [
      'Agree on payment terms before starting any task',
      'Use secure payment methods (avoid sharing bank account details unnecessarily)',
      'Get confirmation of payment before completing tasks',
      'Keep records of all transactions',
      'Report any payment disputes through the platform'
    ]
  },
  {
    icon: '📋',
    title: 'Task Safety',
    guidelines: [
      'Only accept tasks you are capable of completing safely',
      'Verify task details with the poster before starting',
      'Don\'t perform illegal or dangerous tasks',
      'If a task seems suspicious, report it',
      'Complete tasks within agreed timeframes'
    ]
  },
  {
    icon: '👥',
    title: 'Meeting Safety',
    guidelines: [
      'Meet in public places during daylight hours when possible',
      'Inform a friend or family member about your meeting',
      'Trust your instincts - if something feels wrong, leave',
      'Don\'t go to private residences alone',
      'Keep your phone charged and accessible'
    ]
  },
  {
    icon: '🛡️',
    title: 'Personal Information',
    guidelines: [
      'Only share necessary information for task completion',
      'Never share your student ID number publicly',
      'Be cautious about sharing your schedule or location',
      'Report users who ask for unnecessary personal information',
      'Review your profile privacy settings regularly'
    ]
  },
  {
    icon: '✅',
    title: 'Verification',
    guidelines: [
      'Only interact with verified student accounts',
      'Verify the identity of users you\'re working with when possible',
      'Check user ratings and reviews before accepting tasks',
      'Report fake or suspicious accounts',
      'Complete your profile verification for better trust'
    ]
  }
];

// Styles
const pageStyle = {
  minHeight: '100vh',
  background: '#f8f9fc',
  padding: '100px 0 50px'
};

const containerStyle = {
  maxWidth: '900px',
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
  color: '#858796',
  maxWidth: '700px',
  margin: '0 auto'
};

const guidelinesContainerStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '2rem',
  marginBottom: '3rem'
};

const sectionStyle = {
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  overflow: 'hidden'
};

const sectionHeaderStyle = {
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  padding: '1.5rem',
  display: 'flex',
  alignItems: 'center',
  gap: '1rem'
};

const sectionIconStyle = {
  fontSize: '2rem'
};

const sectionTitleStyle = {
  margin: 0,
  color: 'white',
  fontSize: '1.5rem',
  fontWeight: '600'
};

const sectionContentStyle = {
  padding: '1.5rem'
};

const guidelinesListStyle = {
  listStyle: 'none',
  padding: 0,
  margin: 0
};

const guidelineItemStyle = {
  display: 'flex',
  alignItems: 'flex-start',
  gap: '0.75rem',
  marginBottom: '1rem',
  padding: '0.75rem',
  background: '#f8f9fc',
  borderRadius: '8px'
};

const bulletStyle = {
  color: '#4e73df',
  fontSize: '1.2rem',
  fontWeight: 'bold',
  flexShrink: 0
};

const guidelineTextStyle = {
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.8',
  flex: 1
};

const reportSectionStyle = {
  background: '#fff3cd',
  border: '2px solid #ffc107',
  borderRadius: '12px',
  padding: '2rem',
  marginBottom: '2rem',
  textAlign: 'center'
};

const reportTitleStyle = {
  margin: '0 0 1rem 0',
  color: '#856404',
  fontSize: '1.5rem',
  fontWeight: '600'
};

const reportTextStyle = {
  margin: '0 0 1.5rem 0',
  color: '#856404',
  fontSize: '15px',
  lineHeight: '1.8'
};

const reportButtonStyle = {
  display: 'inline-block',
  padding: '12px 24px',
  background: '#e74a3b',
  color: 'white',
  textDecoration: 'none',
  borderRadius: '8px',
  fontWeight: '600',
  fontSize: '16px',
  transition: 'all 0.3s ease'
};

const contactSectionStyle = {
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  padding: '2rem',
  textAlign: 'center'
};

const contactTitleStyle = {
  margin: '0 0 1rem 0',
  color: '#2e3a59',
  fontSize: '1.5rem',
  fontWeight: '600'
};

const contactTextStyle = {
  margin: '0 0 1.5rem 0',
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.8'
};

const contactInfoStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '0.75rem',
  alignItems: 'center'
};

const contactItemStyle = {
  margin: 0,
  color: '#2e3a59',
  fontSize: '16px',
  display: 'flex',
  alignItems: 'center',
  gap: '0.5rem'
};
</script>
