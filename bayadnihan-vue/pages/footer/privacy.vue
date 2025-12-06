<template>
  <div :style="pageStyle">
    <div :style="containerStyle">
      <div :style="headerStyle">
        <h1 :style="titleStyle">Privacy Policy</h1>
        <p :style="subtitleStyle">Last updated: {{ lastUpdated }}</p>
      </div>

      <div :style="contentStyle">
        <div v-for="(section, index) in sections" :key="index" :style="sectionStyle">
          <h2 :style="sectionTitleStyle">{{ section.title }}</h2>
          <div v-for="(paragraph, pIndex) in section.content" :key="pIndex" :style="paragraphStyle">
            <p v-if="typeof paragraph === 'string'">{{ paragraph }}</p>
            <ul v-else-if="Array.isArray(paragraph)" :style="listStyle">
              <li v-for="(item, iIndex) in paragraph" :key="iIndex" :style="listItemStyle">
                <span :style="{ position: 'absolute', left: '0', color: '#4e73df', fontWeight: 'bold', fontSize: '1.2rem' }">•</span>
                <span v-html="item"></span>
              </li>
            </ul>
          </div>
        </div>

        <div :style="contactSectionStyle">
          <h2 :style="contactTitleStyle">Questions About Privacy?</h2>
          <p :style="contactTextStyle">
            If you have any questions about this Privacy Policy or how we handle your data, please contact us at:
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
    title: '1. Introduction',
    content: [
      'BayadNihan ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform. By using BayadNihan, you consent to the data practices described in this policy.'
    ]
  },
  {
    title: '2. Information We Collect',
    content: [
      'We collect information that you provide directly to us and information that is automatically collected when you use our platform:',
      [
        '<strong>Account Information:</strong> Student ID number, email address (@carsu.edu.ph), password (encrypted), phone number (optional), and profile information',
        '<strong>Verification Data:</strong> Information used to verify your student status through the CARSU school system',
        '<strong>Task Information:</strong> Tasks you post, applications you submit, task status, and related communications',
        '<strong>Communication Data:</strong> Messages sent through our in-app messaging system',
        '<strong>Usage Data:</strong> How you interact with the platform, pages visited, features used, and time spent',
        '<strong>Device Information:</strong> IP address, browser type, device type, and operating system'
      ]
    ]
  },
  {
    title: '3. How We Use Your Information',
    content: [
      'We use the information we collect to:',
      [
        'Verify your identity as a CARSU student',
        'Create and manage your account',
        'Facilitate task posting and completion',
        'Enable communication between posters and doers',
        'Process payments and transactions (payment details are handled securely)',
        'Send you notifications about tasks, messages, and account activity',
        'Improve our platform and user experience',
        'Detect and prevent fraud, abuse, and security issues',
        'Comply with legal obligations and enforce our Terms of Service',
        'Send you important updates about the platform (you can opt out of marketing emails)'
      ]
    ]
  },
  {
    title: '4. Information Sharing and Disclosure',
    content: [
      'We do not sell your personal information. We may share your information only in the following circumstances:',
      [
        '<strong>With Other Verified Users:</strong> Your profile information (name, role, ratings) is visible to other verified users on the platform to facilitate task matching and communication',
        '<strong>Task-Related Sharing:</strong> When you post or apply for a task, relevant information is shared with the other party to enable task completion',
        '<strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist in operating our platform (e.g., hosting, email services), subject to confidentiality agreements',
        '<strong>Legal Requirements:</strong> We may disclose information if required by law, court order, or government regulation, or to protect the rights, property, or safety of BayadNihan, our users, or others',
        '<strong>With Your Consent:</strong> We may share information with your explicit consent for specific purposes'
      ],
      'We do not share your student ID number, email address, or phone number with other users unless you explicitly choose to share it in messages.'
    ]
  },
  {
    title: '5. Data Security',
    content: [
      'We implement appropriate technical and organizational measures to protect your personal information:',
      [
        'Passwords are encrypted using industry-standard hashing algorithms',
        'Data transmission is encrypted using SSL/TLS protocols',
        'Access to personal data is restricted to authorized personnel only',
        'Regular security assessments and updates are performed',
        'We follow best practices for secure data storage and handling'
      ],
      'However, no method of transmission over the internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.'
    ]
  },
  {
    title: '6. Your Privacy Rights',
    content: [
      'You have the following rights regarding your personal information:',
      [
        '<strong>Access:</strong> You can access and review your account information at any time through your profile settings',
        '<strong>Correction:</strong> You can update your profile information, email, and phone number through your account settings',
        '<strong>Deletion:</strong> You can request account deletion at any time. We will delete your account and personal data, subject to legal retention requirements',
        '<strong>Data Portability:</strong> You can request a copy of your data in a machine-readable format',
        '<strong>Opt-Out:</strong> You can opt out of non-essential email communications through your account settings'
      ],
      'To exercise these rights, contact us at support@bayadnihan.com. We will respond to your request within a reasonable timeframe.'
    ]
  },
  {
    title: '7. Data Retention',
    content: [
      'We retain your personal information for as long as your account is active or as needed to provide services. We may retain certain information:',
      [
        'To comply with legal obligations',
        'To resolve disputes and enforce agreements',
        'For legitimate business purposes (e.g., preventing fraud)',
        'As required by CARSU policies or regulations'
      ],
      'When you delete your account, we will delete or anonymize your personal information, except where retention is required by law.'
    ]
  },
  {
    title: '8. Cookies and Tracking Technologies',
    content: [
      'We use cookies and similar tracking technologies to:',
      [
        'Maintain your login session',
        'Remember your preferences',
        'Analyze platform usage and improve functionality',
        'Provide personalized experiences'
      ],
      'You can control cookies through your browser settings. However, disabling cookies may limit some platform functionality.'
    ]
  },
  {
    title: '9. Third-Party Links and Services',
    content: [
      'Our platform may contain links to third-party websites or services. We are not responsible for the privacy practices of these external sites. We encourage you to review the privacy policies of any third-party sites you visit.'
    ]
  },
  {
    title: '10. Children\'s Privacy',
    content: [
      'BayadNihan is designed for CARSU students, who are typically adults. We do not knowingly collect personal information from children under 18. If we become aware that we have collected information from a child under 13, we will take steps to delete such information promptly.'
    ]
  },
  {
    title: '11. International Data Transfers',
    content: [
      'Your information is primarily stored and processed in the Philippines. If we transfer your data to other countries, we will ensure appropriate safeguards are in place to protect your information in accordance with this Privacy Policy.'
    ]
  },
  {
    title: '12. Changes to This Privacy Policy',
    content: [
      'We may update this Privacy Policy from time to time. We will notify you of significant changes by:',
      [
        'Posting the updated policy on our platform',
        'Sending an email notification to your registered email address',
        'Displaying a notice on the platform'
      ],
      'Your continued use of the platform after changes become effective constitutes acceptance of the updated Privacy Policy.'
    ]
  },
  {
    title: '13. Contact Information',
    content: [
      'If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:',
      [
        '<strong>Email:</strong> bayadnihan@gmail.com',
        '<strong>Phone:</strong> (+63) 912 345 6789',
        '<strong>Address:</strong> Caraga State University, Ampayon Butuan City'
      ]
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

