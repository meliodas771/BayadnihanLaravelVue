<template>
  <div :style="pageStyle">
    <div :style="containerStyle">
      <div :style="headerStyle">
        <h1 :style="titleStyle">Frequently Asked Questions</h1>
        <p :style="subtitleStyle">Find answers to common questions about BayadNihan</p>
      </div>

      <div :style="faqContainerStyle">
        <div v-for="(faq, index) in faqs" :key="index" :style="faqItemStyle">
          <div :style="faqQuestionStyle" @click="toggleFaq(index)">
            <h3 :style="faqQuestionTextStyle">{{ faq.question }}</h3>
            <span :style="faqIconStyle">{{ openFaqs.includes(index) ? '−' : '+' }}</span>
          </div>
          <div v-if="openFaqs.includes(index)" :style="faqAnswerStyle">
            <p v-for="(paragraph, pIndex) in faq.answer" :key="pIndex" :style="faqAnswerTextStyle">{{ paragraph }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const openFaqs = ref([]);

const toggleFaq = (index) => {
  const idx = openFaqs.value.indexOf(index);
  if (idx > -1) {
    openFaqs.value.splice(idx, 1);
  } else {
    openFaqs.value.push(index);
  }
};

const faqs = [
  {
    question: 'What is BayadNihan?',
    answer: [
      'BayadNihan is a campus-based earning platform that connects students who need errands done with those who can do them. It allows students to post tasks, accept assignments, and earn money within their campus community.'
    ]
  },
  {
    question: 'How do I create an account?',
    answer: [
      'To create an account, click on the "Register" button and provide your student ID (in the format 000-00000), your @carsu.edu.ph email address, and create a password. Your account will be verified through the school system before activation.'
    ]
  },
  {
    question: 'What roles can I choose?',
    answer: [
      'You can choose to be a Poster (post tasks), a Doer (complete tasks), or Both. If you choose "Both", you\'ll get a 7-day free trial to access both roles. After the trial, you may need to subscribe to continue using both roles.'
    ]
  },
  {
    question: 'How do I post a task?',
    answer: [
      'After logging in, go to "Post a Task" and fill in the task details including title, description, location, budget, and deadline. You can save it as a draft or publish it immediately. Note: Posters can have up to 5 active tasks at a time.'
    ]
  },
  {
    question: 'How do I apply for a task?',
    answer: [
      'Browse available tasks on the tasks page, click on a task you\'re interested in, and click "Apply". The poster will review your application and can accept or reject it.'
    ]
  },
  {
    question: 'How does payment work?',
    answer: [
      'Payment methods are agreed upon between the poster and doer through direct messaging. Common methods include cash, digital wallets, or bank transfers. Always discuss payment terms before starting a task.'
    ]
  },
  {
    question: 'What if I have a problem with a task?',
    answer: [
      'If you encounter any issues, you can use the messaging feature to communicate with the other party. For serious concerns, you can report a user through the report feature. Our support team will review reports and take appropriate action.'
    ]
  },
  {
    question: 'Can I cancel a task?',
    answer: [
      'Posters can cancel tasks that are still in "open" status. Once a task is assigned or in progress, cancellation should be discussed with the assigned doer. Doers can pause tasks if needed, but should communicate the reason to the poster.'
    ]
  },
  {
    question: 'How do I verify my email?',
    answer: [
      'After registration, you\'ll receive a verification email. Click the verification link in the email to activate your account. If you didn\'t receive the email, you can request a new verification link.'
    ]
  },
  {
    question: 'Is my information safe?',
    answer: [
      'Yes, we take privacy seriously. Only verified student accounts can use the platform, and we use secure authentication. Your personal information is protected and only shared as necessary for task completion.'
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
  color: '#858796'
};

const faqContainerStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '1rem'
};

const faqItemStyle = {
  background: 'white',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  overflow: 'hidden',
  transition: 'all 0.3s ease'
};

const faqQuestionStyle = {
  padding: '1.5rem',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  cursor: 'pointer',
  background: '#f8f9fc',
  borderBottom: '1px solid #e3e6f0'
};

const faqQuestionTextStyle = {
  margin: 0,
  fontSize: '1.1rem',
  color: '#2e3a59',
  fontWeight: '600',
  flex: 1
};

const faqIconStyle = {
  fontSize: '1.5rem',
  color: '#4e73df',
  fontWeight: 'bold',
  width: '30px',
  textAlign: 'center'
};

const faqAnswerStyle = {
  padding: '1.5rem',
  background: 'white'
};

const faqAnswerTextStyle = {
  margin: '0 0 1rem 0',
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.8'
};
</script>
