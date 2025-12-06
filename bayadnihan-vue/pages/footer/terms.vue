<template>
  <div :style="pageStyle">
    <div :style="containerStyle">
      <div :style="headerStyle">
        <h1 :style="titleStyle">Terms of Service</h1>
        <p :style="subtitleStyle">Last updated: {{ lastUpdated }}</p>
      </div>

      <div :style="contentStyle">
        <div v-for="(section, index) in sections" :key="index" :style="sectionStyle">
          <h2 :style="sectionTitleStyle">{{ section.title }}</h2>
          <div v-for="(paragraph, pIndex) in section.content" :key="pIndex" :style="paragraphStyle">
            <p v-if="typeof paragraph === 'string'">{{ paragraph }}</p>
            <ul v-else-if="Array.isArray(paragraph)" :style="listStyle">
              <li v-for="(item, iIndex) in paragraph" :key="iIndex" :style="listItemStyle">{{ item }}</li>
            </ul>
          </div>
        </div>

        <div :style="contactSectionStyle">
          <h2 :style="contactTitleStyle">Questions About These Terms?</h2>
          <p :style="contactTextStyle">
            If you have any questions about these Terms of Service, please contact us at:
          </p>
          <p :style="contactInfoStyle">
            <strong>Email:</strong> support@bayadnihan.com<br>
            <strong>Phone:</strong> (+63) 912 345 6789
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const lastUpdated = 'December 2025';

const sections = [
  {
    title: '1. Acceptance of Terms',
    content: [
      'By accessing and using BayadNihan, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.'
    ]
  },
  {
    title: '2. Eligibility and Account Requirements',
    content: [
      'BayadNihan is exclusively for verified students of Caraga State University (CARSU). To use our platform, you must:',
      [
        'Be a currently enrolled student at CARSU',
        'Provide a valid student ID in the format 000-00000',
        'Use a valid @carsu.edu.ph email address',
        'Complete the account verification process',
        'Maintain accurate and up-to-date account information'
      ],
      'Any attempt to create an account with false information will result in immediate account suspension or termination.'
    ]
  },
  {
    title: '3. User Roles and Responsibilities',
    content: [
      'BayadNihan offers three role options:',
      [
        '<strong>Poster:</strong> Users who post tasks and need services completed. Posters are responsible for providing clear task descriptions, setting reasonable budgets, and making timely payments.',
        '<strong>Doer:</strong> Users who complete tasks and earn money. Doers are responsible for completing tasks as described, communicating effectively, and delivering quality work.',
        '<strong>Both:</strong> Users who can both post and complete tasks. This role may require a subscription after a 7-day free trial period.'
      ],
      'You agree to use your selected role(s) responsibly and in accordance with all applicable laws and school policies.'
    ]
  },
  {
    title: '4. Task Posting Guidelines',
    content: [
      'When posting tasks, you agree to:',
      [
        'Post only legitimate tasks that comply with CARSU policies and local laws',
        'Provide accurate and complete task descriptions',
        'Set fair and reasonable budgets',
        'Respect the 5 active task limit (tasks with status: open, assigned, or in_progress)',
        'Not post tasks that are illegal, dangerous, or violate school policies',
        'Not post tasks that involve academic dishonesty or cheating',
        'Respond promptly to applications and messages'
      ],
      'BayadNihan reserves the right to remove any task that violates these guidelines without notice.'
    ]
  },
  {
    title: '5. Task Completion and Payment',
    content: [
      'Payment terms must be agreed upon between the poster and doer before task commencement. Both parties agree to:',
      [
        'Discuss and agree on payment methods before starting work',
        'Complete tasks within agreed timeframes',
        'Handle payments responsibly and honestly',
        'Resolve payment disputes through the platform\'s reporting system',
        'Not engage in fraudulent payment activities'
      ],
      'BayadNihan is not responsible for payment disputes between users. However, we provide reporting mechanisms to address issues.'
    ]
  },
  {
    title: '6. Communication and Conduct',
    content: [
      'All users agree to:',
      [
        'Use the in-app messaging system for all communications related to tasks',
        'Maintain respectful and professional communication',
        'Not share personal information unnecessarily',
        'Report any harassment, abuse, or inappropriate behavior',
        'Not use the platform for spam, solicitation, or unauthorized advertising'
      ],
      'Violation of these communication guidelines may result in account suspension or termination.'
    ]
  },
  {
    title: '7. Account Security',
    content: [
      'You are responsible for:',
      [
        'Maintaining the confidentiality of your account credentials',
        'All activities that occur under your account',
        'Immediately notifying us of any unauthorized use of your account',
        'Logging out from shared or public devices'
      ],
      'BayadNihan is not liable for any loss or damage arising from your failure to comply with these security obligations.'
    ]
  },
  {
    title: '8. Prohibited Activities',
    content: [
      'You agree NOT to:',
      [
        'Create fake or duplicate accounts',
        'Impersonate another person or entity',
        'Post false or misleading information',
        'Engage in fraudulent activities',
        'Use the platform for illegal purposes',
        'Violate any CARSU policies or regulations',
        'Interfere with or disrupt the platform\'s functionality',
        'Attempt to gain unauthorized access to the platform or other users\' accounts'
      ]
    ]
  },
  {
    title: '9. Account Suspension and Termination',
    content: [
      'BayadNihan reserves the right to suspend or terminate your account if you:',
      [
        'Violate these Terms of Service',
        'Engage in fraudulent or illegal activities',
        'Provide false information during registration',
        'Harass, abuse, or harm other users',
        'Repeatedly fail to complete tasks or make payments as agreed'
      ],
      'Account suspension or termination may occur without prior notice. You may appeal a suspension by contacting our support team.'
    ]
  },
  {
    title: '10. Limitation of Liability',
    content: [
      'BayadNihan serves as a platform connecting students. We are not responsible for:',
      [
        'The quality, safety, or legality of tasks posted or completed',
        'Payment disputes between users',
        'Any damages or losses resulting from task completion or failure',
        'User interactions outside of the platform',
        'Any technical issues or service interruptions'
      ],
      'You use the platform at your own risk. BayadNihan provides the platform "as is" without warranties of any kind.'
    ]
  },
  {
    title: '11. Intellectual Property',
    content: [
      'All content on BayadNihan, including but not limited to text, graphics, logos, and software, is the property of BayadNihan or its content suppliers and is protected by copyright and other intellectual property laws.',
      'You may not reproduce, distribute, or create derivative works from any content on the platform without explicit written permission.'
    ]
  },
  {
    title: '12. Changes to Terms',
    content: [
      'BayadNihan reserves the right to modify these Terms of Service at any time. We will notify users of significant changes via email or platform notifications.',
      'Continued use of the platform after changes constitutes acceptance of the new terms. If you do not agree to the changes, you must stop using the platform and may request account deletion.'
    ]
  },
  {
    title: '13. Governing Law',
    content: [
      'These Terms of Service are governed by the laws of the Philippines and the policies of Caraga State University. Any disputes will be resolved in accordance with applicable laws and university policies.'
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
  fontSize: '1rem',
  color: '#858796',
  fontStyle: 'italic'
};

const contentStyle = {
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  padding: '3rem'
};

const sectionStyle = {
  marginBottom: '2.5rem'
};

const sectionTitleStyle = {
  fontSize: '1.5rem',
  color: '#2e3a59',
  marginBottom: '1rem',
  fontWeight: '600',
  paddingBottom: '0.5rem',
  borderBottom: '2px solid #e3e6f0'
};

const paragraphStyle = {
  marginBottom: '1rem'
};

const paragraphTextStyle = {
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.8',
  margin: '0 0 1rem 0'
};

const listStyle = {
  listStyle: 'none',
  padding: 0,
  margin: '1rem 0'
};

const listItemStyle = {
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.8',
  marginBottom: '0.75rem',
  paddingLeft: '1.5rem',
  position: 'relative'
};

const listItemBeforeStyle = {
  content: '"•"',
  position: 'absolute',
  left: '0',
  color: '#4e73df',
  fontWeight: 'bold',
  fontSize: '1.2rem'
};

const contactSectionStyle = {
  marginTop: '3rem',
  padding: '2rem',
  background: '#f8f9fc',
  borderRadius: '12px',
  border: '1px solid #e3e6f0'
};

const contactTitleStyle = {
  fontSize: '1.3rem',
  color: '#2e3a59',
  marginBottom: '1rem',
  fontWeight: '600'
};

const contactTextStyle = {
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.8',
  marginBottom: '1rem'
};

const contactInfoStyle = {
  color: '#2e3a59',
  fontSize: '15px',
  lineHeight: '1.8'
};
</script>

