<template>
  <div>
    <!-- Header -->
    <header :style="headerStyle">
      <div :style="containerStyle">
        <div :style="headerContentStyle">
          <div :style="logoStyle">
            <span :style="logoIconStyle">🤝</span>
            <h1 :style="logoH1Style">BayadNihan</h1>
          </div>
          
          <nav :style="navStyle" v-if="!isMobile">
            <a href="#features" :style="navLinkStyle">Features</a>
            <a href="#how-it-works" :style="navLinkStyle">How It Works</a>
            <a href="#testimonials" :style="navLinkStyle">Testimonials</a>
          </nav>
          
          <div :style="headerActionsStyle" v-if="!isMobile">
            <NuxtLink v-if="isLoggedIn" to="/tasks" :style="btnOutline">Dashboard</NuxtLink>
            <button v-if="isLoggedIn" @click="showLogoutConfirmation" :style="btnPrimary">Logout</button>
            <template v-else>
              <NuxtLink to="/auth/login" :style="btnOutline">Login</NuxtLink>
              <NuxtLink to="/auth/register" :style="btnPrimary">Register</NuxtLink>
            </template>
          </div>
          
          <!-- Burger Menu -->
          <div 
            v-if="isMobile"
            :style="burgerMenuStyle"
            @click="toggleMobileMenu"
            :class="{ active: isMobileMenuOpen }"
          >
            <span :style="burgerSpan1"></span>
            <span :style="burgerSpan2"></span>
            <span :style="burgerSpan3"></span>
          </div>
        </div>
      </div>
    </header>

    <!-- Menu Overlay -->
    <div v-if="isMobile" :style="menuOverlayStyle" @click="closeMobileMenu"></div>
    
    <!-- Mobile Menu -->
    <div v-if="isMobile" :style="getMobileMenuStyle()" :class="{ active: isMobileMenuOpen }">
      <nav :style="mobileNavStyle">
        <a href="#features" :style="mobileNavLinkStyle" @click="closeMobileMenu">Features</a>
        <a href="#how-it-works" :style="mobileNavLinkStyle" @click="closeMobileMenu">How It Works</a>
        <a href="#testimonials" :style="mobileNavLinkStyle" @click="closeMobileMenu">Testimonials</a>
      </nav>
      <div :style="mobileHeaderActionsStyle">
        <NuxtLink v-if="isLoggedIn" to="/tasks" :style="btnOutlineFull" @click="closeMobileMenu">Dashboard</NuxtLink>
        <button v-if="isLoggedIn" @click="handleMobileLogout" :style="btnPrimaryFull">Logout</button>
        <template v-else>
          <NuxtLink to="/auth/login" :style="btnOutlineFull" @click="closeMobileMenu">Login</NuxtLink>
          <NuxtLink to="/auth/register" :style="btnPrimaryFull" @click="closeMobileMenu">Register</NuxtLink>
        </template>
      </div>
    </div>

    <!-- Hero Section -->
    <section :style="heroStyle">
      <div :style="containerStyle">
        <div :style="heroContentStyle">
          <div :style="heroTextStyle">
            <h1 :style="heroH1Style">Earn While You Learn. Help While You Study.</h1>
            <p :style="heroPStyle">
              BayadNihan connects students who need errands done with those who can do them. 
              Post tasks, accept assignments, and earn money - all within your campus community.
            </p>
            <div :style="heroActionsStyle">
              <NuxtLink v-if="isLoggedIn" to="/tasks/create" :style="btnPrimary">Post a Task</NuxtLink>
              <NuxtLink to="/tasks" :style="btnOutline">Browse Tasks</NuxtLink>
              <NuxtLink v-if="!isLoggedIn" to="/auth/register" :style="btnPrimary">Get Started</NuxtLink>
            </div>
            <div :style="heroStatsStyle">
              <div :style="statStyle">
                <h3 :style="statH3Style">500+</h3>
                <p :style="statPStyle">Tasks Completed</p>
              </div>
              <div :style="statStyle">
                <h3 :style="statH3Style">200+</h3>
                <p :style="statPStyle">Active Users</p>
              </div>
              <div :style="statStyle">
                <h3 :style="statH3Style">₱10,000+</h3>
                <p :style="statPStyle">Total Earned</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section :style="featuresStyle" id="features">
      <div :style="containerStyle">
        <div :style="sectionTitleStyle">
          <h2 :style="sectionTitleH2Style">Why Choose BayadNihan?</h2>
          <p :style="sectionTitlePStyle">Our platform makes it easy for students to help each other and earn money</p>
        </div>
        
        <div :style="featuresGridStyle">
          <div v-for="(feature, index) in features" :key="index" :style="featureCardStyle">
            <div :style="featureIconStyle">{{ feature.icon }}</div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section :style="howItWorksStyle" id="how-it-works">
      <div :style="containerStyle">
        <div :style="sectionTitleStyle">
          <h2 :style="sectionTitleH2Style">How It Works</h2>
          <p :style="sectionTitlePStyle">Simple steps to start earning or getting help with errands</p>
        </div>
        
        <div :style="stepsStyle">
          <div v-for="(step, index) in steps" :key="index" :style="stepStyle">
            <div :style="stepIconStyle">{{ step.icon }}</div>
            <div style="flex: 1">
              <h3 :style="{ marginBottom: '0.5rem', color: '#2e3a59' }">{{ step.title }}</h3>
              <p :style="{ color: '#858796', margin: 0 }">{{ step.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer :style="footerStyle">
      <div :style="containerStyle">
        <div :style="footerContentStyle">
          <div :style="{ marginBottom: '1rem' }">
            <div :style="logoStyle">
              <span :style="logoIconStyle">🤝</span>
              <h2 :style="{ color: 'white', margin: 0, fontSize: '1.5rem' }">BayadNihan</h2>
            </div>
            <p :style="{ color: '#ccc', marginBottom: '1rem', marginTop: '0.5rem' }">
              Connecting students to help each other with errands and earn money in the process.
            </p>
          </div>
          
          <div :style="{ textAlign: 'center', paddingTop: '2rem', borderTop: '1px solid #444', color: '#ccc' }">
            <p style="margin: 0">&copy; {{ new Date().getFullYear() }} BayadNihan. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>

    <!-- Logout Modal -->
    <div v-if="showLogoutModal" :style="modalStyle" @click="closeLogoutModal">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3 :style="{ margin: 0, fontSize: '20px', color: '#2e3a59', fontWeight: '600' }">🚪 Logout</h3>
          <button @click="closeLogoutModal" :style="modalCloseStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="{ margin: 0, color: '#5a5c69', fontSize: '15px', lineHeight: '1.6' }">Are you sure you want to logout?</p>
        </div>
        <div :style="modalFooterStyle">
          <button @click="closeLogoutModal" :style="btnOutlineModal">Cancel</button>
          <button @click="handleLogout" :style="btnDangerModal">Logout</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

const router = useRouter();
const { user, isAuthenticated, logout: logoutUser } = useUser();
const { authAPI } = useAPI();

const isMobileMenuOpen = ref(false);
const showLogoutModal = ref(false);
const isMobile = ref(false);

const isLoggedIn = computed(() => isAuthenticated.value);

const features = [
  { icon: '📋', title: 'Post Errands', description: 'Need something done? Post your task and set your price.' },
  { icon: '💰', title: 'Earn Money', description: 'Complete tasks and get paid.' },
  { icon: '💬', title: 'Direct Messaging', description: 'Communicate with posters or doers to clarify task details.' },
  { icon: '🛡️', title: 'Secure Platform', description: 'Verified student accounts ensure a safe community.' },
  { icon: '⏱️', title: 'Save Time', description: 'Get your errands done while focusing on your studies.' },
  { icon: '👥', title: 'Campus Community', description: 'Connect with fellow students on your campus.' }
];

const steps = [
  { icon: '👤', title: '1. Create Account', description: 'Sign up as a Poster, Doer, or both using your student credentials.' },
  { icon: '📝', title: '2. Post or Browse', description: 'Post tasks you need done or browse available tasks in your area.' },
  { icon: '🤝', title: '3. Connect & Agree', description: 'Message the other party for further details.' },
  { icon: '💳', title: '4. Complete & Get Paid', description: 'Complete the task and receive payment through your preferred method.' }
];

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

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
  if (process.client) {
    document.body.style.overflow = isMobileMenuOpen.value ? 'hidden' : '';
  }
};

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
  if (process.client) {
    document.body.style.overflow = '';
  }
};

const showLogoutConfirmation = () => {
  showLogoutModal.value = true;
};

const closeLogoutModal = () => {
  showLogoutModal.value = false;
};

const handleLogout = async () => {
  await authAPI.logout();
  logoutUser();
  showLogoutModal.value = false;
  if (process.client) {
    window.location.href = '/';
  }
};

const handleMobileLogout = () => {
  showLogoutConfirmation();
  closeMobileMenu();
};

const getMobileMenuStyle = () => {
  return {
    position: 'fixed',
    top: 0,
    right: isMobileMenuOpen.value ? 0 : '-100%',
    width: '230px',
    maxWidth: '80%',
    height: '100vh',
    background: 'white',
    boxShadow: '-2px 0 10px rgba(0, 0, 0, 0.1)',
    padding: '5rem 2rem 2rem',
    zIndex: 1001,
    overflowY: 'auto',
    transition: 'right 0.4s ease',
    visibility: isMobileMenuOpen.value ? 'visible' : 'hidden'
  };
};

// Styles (abbreviated for brevity - full styles would be here)
const containerStyle = { maxWidth: '1200px', margin: '0 auto', padding: '0 20px' };
const headerStyle = { background: 'white', boxShadow: '0 2px 10px rgba(0, 0, 0, 0.1)', position: 'fixed', width: '100%', top: 0, zIndex: 1000 };
const headerContentStyle = { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem 0' };
const logoStyle = { display: 'flex', alignItems: 'center', gap: '0.5rem' };
const logoIconStyle = { fontSize: '2rem' };
const logoH1Style = { color: '#4e73df', fontSize: '1.8rem' };
const navStyle = { display: 'flex', gap: '2rem' };
const navLinkStyle = { textDecoration: 'none', color: '#2e3a59', fontWeight: '500', transition: 'color 0.3s' };
const headerActionsStyle = { display: 'flex', gap: '1rem' };
const btnStyle = { padding: '0.7rem 1.5rem', borderRadius: '50px', textDecoration: 'none', fontWeight: '600', transition: 'all 0.3s', display: 'inline-block', border: 'none', cursor: 'pointer', fontSize: '1rem' };
const btnPrimaryStyle = { background: '#4e73df', color: 'white' };
const btnOutlineStyle = { background: 'transparent', border: '2px solid #4e73df', color: '#4e73df' };
const btnDangerStyle = { background: '#e74a3b', color: 'white' };
const burgerMenuStyle = { display: 'flex', flexDirection: 'column', gap: '5px', cursor: 'pointer', padding: '10px', zIndex: 1001 };
const burgerSpanStyle = { width: '25px', height: '3px', backgroundColor: '#2e3a59', transition: 'all 0.3s ease', borderRadius: '3px' };
const menuOverlayStyle = { position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0, 0, 0, 0.5)', zIndex: 1000, opacity: isMobileMenuOpen.value ? 1 : 0, visibility: isMobileMenuOpen.value ? 'visible' : 'hidden', transition: 'opacity 0.3s ease, visibility 0.3s ease' };
const heroStyle = { padding: '8rem 0 5rem', background: 'linear-gradient(135deg, #f8f9fc 0%, #e8eaf6 100%)', marginTop: '80px' };
const heroContentStyle = { display: 'flex', alignItems: 'center', gap: '3rem' };
const heroTextStyle = { flex: 1 };
const heroH1Style = { fontSize: '3rem', marginBottom: '1.5rem', color: '#2e3a59' };
const heroPStyle = { fontSize: '1.2rem', marginBottom: '2rem', color: '#858796' };
const heroActionsStyle = { display: 'flex', gap: '1rem', marginBottom: '3rem' };
const heroStatsStyle = { display: 'flex', gap: '2rem' };
const statStyle = { textAlign: 'center' };
const statH3Style = { fontSize: '2rem', color: '#4e73df', marginBottom: '0.5rem' };
const statPStyle = { fontSize: '0.9rem', margin: 0, color: '#858796' };
const featuresStyle = { padding: '5rem 0', background: 'white', color: '#080809ff' };
const sectionTitleStyle = { textAlign: 'center', marginBottom: '3rem' };
const sectionTitleH2Style = { fontSize: '2.5rem', marginBottom: '1rem', color: '#2e3a59' };
const sectionTitlePStyle = { fontSize: '1.2rem', color: '#858796', maxWidth: '600px', margin: '0 auto' };
const featuresGridStyle = { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '2rem' };
const featureCardStyle = { textAlign: 'center', padding: '2rem', borderRadius: '10px', transition: 'transform 0.3s, box-shadow 0.3s', background: '#f8f9fc' };
const featureIconStyle = { fontSize: '3rem', marginBottom: '1rem' };
const howItWorksStyle = { padding: '5rem 0', background: '#f8f9fc', color: '#080809ff' };
const stepsStyle = { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '2rem' };
const stepStyle = { display: 'flex', alignItems: 'flex-start', gap: '1rem', padding: '1.5rem', background: 'white', borderRadius: '10px', boxShadow: '0 5px 15px rgba(0, 0, 0, 0.1)' };
const stepIconStyle = { fontSize: '2rem', flexShrink: 0 };
const footerStyle = { background: '#2e3a59', color: 'white', padding: '3rem 0 1rem' };
const footerContentStyle = { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '2rem', marginBottom: '2rem' };
const modalStyle = { display: 'flex', position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center', zIndex: 2000 };
const modalContentStyle = { background: 'white', borderRadius: '12px', boxShadow: '0 8px 32px rgba(0, 0, 0, 0.2)', maxWidth: '450px', width: '90%' };
const modalHeaderStyle = { padding: '20px 24px', borderBottom: '1px solid #e3e6f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' };
const modalBodyStyle = { padding: '24px' };
const modalFooterStyle = { padding: '16px 24px', borderTop: '1px solid #e3e6f0', display: 'flex', justifyContent: 'flex-end', gap: '12px' };
const modalCloseStyle = { background: 'none', border: 'none', fontSize: '28px', color: '#858796', cursor: 'pointer', padding: 0, width: '32px', height: '32px', display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: '4px' };

// Computed styles for merged objects
const btnOutline = computed(() => ({ ...btnStyle, ...btnOutlineStyle }));
const btnPrimary = computed(() => ({ ...btnStyle, ...btnPrimaryStyle }));
const btnDanger = computed(() => ({ ...btnStyle, ...btnDangerStyle }));
const btnOutlineFull = computed(() => ({ ...btnStyle, ...btnOutlineStyle, width: '100%', textAlign: 'center' }));
const btnPrimaryFull = computed(() => ({ ...btnStyle, ...btnPrimaryStyle, width: '100%' }));
const btnOutlineModal = computed(() => ({ ...btnStyle, ...btnOutlineStyle, margin: 0, padding: '10px 20px', fontSize: '14px' }));
const btnDangerModal = computed(() => ({ ...btnStyle, ...btnDangerStyle, margin: 0, padding: '10px 20px', fontSize: '14px' }));

const burgerSpan1 = computed(() => ({ ...burgerSpanStyle, transform: isMobileMenuOpen.value ? 'rotate(45deg) translate(8px, 8px)' : 'none' }));
const burgerSpan2 = computed(() => ({ ...burgerSpanStyle, opacity: isMobileMenuOpen.value ? 0 : 1 }));
const burgerSpan3 = computed(() => ({ ...burgerSpanStyle, transform: isMobileMenuOpen.value ? 'rotate(-45deg) translate(7px, -7px)' : 'none' }));

const mobileNavStyle = computed(() => ({ ...navStyle, flexDirection: 'column', gap: '1rem', marginBottom: '1rem' }));
const mobileNavLinkStyle = computed(() => ({ ...navLinkStyle, padding: '0.5rem 0', borderBottom: '1px solid #eee' }));
const mobileHeaderActionsStyle = computed(() => ({ ...headerActionsStyle, flexDirection: 'column', gap: '0.5rem' }));
</script>

