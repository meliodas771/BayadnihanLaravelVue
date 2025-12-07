<template>
  <div>
    <button 
      class="mobile-menu-toggle" 
      :style="mobileMenuToggleStyle"
      @click="toggleSidebar"
    >
      ☰
    </button>
    
    <!-- Sidebar -->
    <div :style="sidebarStyle" id="sidebar" class="sidebar">
      <span :style="brandStyle">🤝 BayadNihan</span>
      
      <nav :style="navStyle">
        <a 
          href="/tasks" 
          :style="getNavLinkStyle('/tasks')"
          @mouseenter="handleNavHover($event, true)"
          @mouseleave="handleNavHover($event, false)"
          @click.prevent="navigateTo('/tasks')"
        >
          📋 Tasks
        </a>
        
        <a 
          v-if="user && user.role !== 'doer'"
          href="/tasks/create" 
          :style="getNavLinkStyle('/tasks/create')"
          @mouseenter="handleNavHover($event, true)"
          @mouseleave="handleNavHover($event, false)"
          @click.prevent="navigateTo('/tasks/create')"
        >
          ✍️ Create Task
        </a>
        
        <a 
          href="/notifications" 
          :style="getNavLinkStyle('/notifications')"
          @mouseenter="handleNavHover($event, true)"
          @mouseleave="handleNavHover($event, false)"
          @click.prevent="navigateTo('/notifications')"
        >
          🔔 Notifications
          <span v-if="unreadNotificationCount > 0" class="notification-badge" :style="notificationBadgeStyle">
            {{ unreadNotificationCount }}
          </span>
        </a>
      </nav>
      
      <div :style="userSectionStyle">
        <a 
          href="/profile" 
          :style="userInfoStyle"
          @mouseenter="handleNavHover($event, true)"
          @mouseleave="handleNavHover($event, false)"
          @click.prevent="navigateTo('/profile')"
        >
          <img 
            :key="user?.profile_pic || 'no-pic'"
            :src="getProfilePicUrl()"
            alt="Profile"
            :style="userAvatarStyle"
            @error="handleImageError"
          />
          <span :style="usernameDisplayStyle">{{ user?.username || 'User' }}</span>
        </a>
        
        <button 
          type="button" 
          :style="settingsBtnStyle"
          @mouseenter="handleNavHover($event, true)"
          @mouseleave="handleNavHover($event, false)"
          @click="toggleSettings"
        >
          ⚙️ Settings
          <span id="settings-arrow">{{ showSettings ? '▲' : '▼' }}</span>
        </button>
        
        <div class="settings-dropdown" :style="getSettingsDropdownStyle()" id="settings-dropdown">
          <a 
            href="/report/create" 
            :style="dropdownLinkStyle"
            @mouseenter="handleNavHover($event, true)"
            @mouseleave="handleNavHover($event, false)"
            @click.prevent="navigateTo('/report/create')"
          >
            ⚠️ Report User
          </a>
          <button 
            type="button" 
            :style="logoutBtnStyle"
            @mouseenter="handleNavHover($event, true)"
            @mouseleave="handleNavHover($event, false)"
            @click="showLogoutModal = true"
          >
            🚺 Logout
          </button>
        </div>
      </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div 
      v-if="showLogoutModal"
      :style="modalStyle"
      @click="showLogoutModal = false"
    >
      <div 
        :style="modalContentStyle"
        @click.stop
      >
        <div :style="modalHeaderStyle">
          <h3 :style="modalHeaderH3Style">Logout</h3>
          <button 
            class="modal-close"
            :style="modalCloseStyle"
            @click="showLogoutModal = false"
            @mouseenter="handleModalCloseHover($event, true)"
            @mouseleave="handleModalCloseHover($event, false)"
          >
            &times;
          </button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="modalBodyPStyle">Are you sure you want to logout?</p>
        </div>
        <div :style="modalFooterStyle">
          <button 
            class="btn btn-secondary"
            :style="cancelButtonStyle"
            @click="showLogoutModal = false"
          >
            Cancel
          </button>
          <button 
            type="submit"
            class="btn btn-danger"
            :style="logoutButtonStyle"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

const router = useRouter();
const route = useRoute();
const { user, logout: logoutUser, updateUser } = useUser();
const { authAPI, notificationsAPI, userAPI } = useAPI();
const { $echo } = useNuxtApp();

const showSettings = ref(false);
const showLogoutModal = ref(false);
const unreadNotificationCount = ref(0);

const keyframes = `
  @keyframes slideDown {
    from {
      transform: translateY(-20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  @media (max-width: 768px) {
    .mobile-menu-toggle { display: block !important; }
    .sidebar { 
      transform: translateX(-100%) !important; 
      width: 0 !important;
      overflow: hidden !important;
      pointer-events: none !important;
    }
    .sidebar.active { 
      transform: translateX(0) !important; 
      width: 250px !important;
      pointer-events: auto !important;
    }
    .main-content { 
      margin-left: 0 !important; 
      width: 100% !important; 
      padding-top: 70px !important; 
    }
    .main-layout {
      margin-left: 0 !important;
    }
  }
`;

useHead({
  style: [
    {
      children: keyframes
    }
  ]
});

// Fetch unread notifications count
const fetchUnreadNotifications = async () => {
  try {
    const response = await notificationsAPI.getAll();
    const notifications = response.notifications || response || [];
    const unreadCount = notifications.filter(n => !n.read).length;
    unreadNotificationCount.value = unreadCount;
  } catch (error) {
    console.error('Error fetching notifications:', error);
    unreadNotificationCount.value = 0;
  }
};

const isMobile = ref(false);
const sidebarActive = ref(false);

const updateMobileState = () => {
  if (process.client) {
    isMobile.value = window.innerWidth <= 768;
    const sidebar = document.getElementById('sidebar');
    if (sidebar && isMobile.value && !sidebar.classList.contains('active')) {
      sidebar.style.transform = 'translateX(-100%)';
    } else if (sidebar && !isMobile.value) {
      sidebar.style.transform = 'translateX(0)';
      sidebar.classList.remove('active');
    }
  }
};

// Fetch user profile to ensure we have latest data including profile_pic
const fetchUserProfile = async () => {
  try {
    const response = await userAPI.getProfile();
    const profileData = response.user || response;
    if (profileData && updateUser) {
      // Update user object with latest profile data including profile_pic
      updateUser({
        ...user.value,
        ...profileData,
        profile_pic: profileData.profile_pic || user.value?.profile_pic
      });
    }
  } catch (error) {
    console.error('Error fetching user profile:', error);
  }
};

onMounted(() => {
  fetchUnreadNotifications();
  
  // Set up real-time notification listener
  if (user.value && user.value.id && $echo) {
    try {
      const channel = $echo.channel(`user.${user.value.id}`);
      
      channel.listen('.notification.created', (data) => {
        // Increment the unread count
        unreadNotificationCount.value++;
        // Optionally fetch all notifications to update the list
        fetchUnreadNotifications();
      });
    } catch (error) {
      console.error('Error setting up notification listener:', error);
    }
  }
  
  // Fetch user profile if profile_pic is missing
  if (user.value && !user.value.profile_pic) {
    fetchUserProfile();
  }
  
  updateMobileState();
  
  const setBurgerVisibility = () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');
    if (!toggle || !sidebar) return;
    if (window.innerWidth <= 768) {
      if (sidebar.classList.contains('active')) {
        toggle.style.display = 'none';
      } else {
        toggle.style.display = 'block';
      }
    } else {
      toggle.style.display = 'none';
      sidebar.classList.remove('active');
    }
  };

  const handleClickOutside = (event) => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');
    if (!sidebar || !toggle) return;
    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
      if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        closeSidebar();
      }
    }
  };

  const handleResize = () => {
    updateMobileState();
    setBurgerVisibility();
  };

  // Listen for notification read events
  const handleNotificationRead = () => {
    fetchUnreadNotifications();
  };

  window.addEventListener('resize', handleResize);
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('notification-read', handleNotificationRead);
  setBurgerVisibility();

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('notification-read', handleNotificationRead);
    
    // Clean up Echo listener
    if (user.value && user.value.id && $echo) {
      try {
        $echo.leave(`user.${user.value.id}`);
      } catch (error) {
        console.error('Error cleaning up notification listener:', error);
      }
    }
  });
});

const toggleSettings = () => {
  showSettings.value = !showSettings.value;
  const dropdown = document.getElementById('settings-dropdown');
  const arrow = document.getElementById('settings-arrow');
  if (dropdown && arrow) {
    dropdown.classList.toggle('active');
    arrow.textContent = dropdown.classList.contains('active') ? '▲' : '▼';
  }
};

const handleLogout = async () => {
  try {
    await authAPI.logout();
    if (logoutUser) {
      logoutUser();
    }
    showLogoutModal.value = false;
    router.push('/login');
  } catch (error) {
    console.error('Logout error:', error);
    if (logoutUser) {
      logoutUser();
    }
    router.push('/login');
  }
};

const isActiveLink = (path) => {
  return route.path === path;
};

const closeSidebar = () => {
  if (!process.client) return;
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('.mobile-menu-toggle');
  if (sidebar) {
    sidebar.classList.remove('active');
    sidebarActive.value = false;
    if (toggle && window.innerWidth <= 768) {
      toggle.style.display = 'block';
    }
  }
};

const toggleSidebar = () => {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('.mobile-menu-toggle');
  if (sidebar) {
    const isActive = sidebar.classList.contains('active');
    if (isActive) {
      closeSidebar();
    } else {
      sidebar.classList.add('active');
      sidebarActive.value = true;
      // Update burger visibility
      if (toggle && process.client && window.innerWidth <= 768) {
        toggle.style.display = 'none';
      }
    }
  }
};

const navigateTo = (path) => {
  // Close sidebar on mobile when navigating
  if (process.client && window.innerWidth <= 768) {
    closeSidebar();
  }
  router.push(path);
};

const handleNavHover = (event, isEnter) => {
  event.target.style.background = isEnter ? 'rgba(255,255,255,0.1)' : 'transparent';
};

const handleModalCloseHover = (event, isEnter) => {
  event.target.style.background = isEnter ? '#f8f9fc' : 'transparent';
  event.target.style.color = isEnter ? '#2e3a59' : '#858796';
};

const getProfilePicUrl = () => {
  if (!user.value) {
    return `https://ui-avatars.com/api/?name=User&size=40&background=fff&color=4e73df`;
  }
  
  if (user.value.profile_pic) {
    return `http://localhost:8000/storage/profile_pics/${user.value.profile_pic}`;
  }
  
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value.username || 'User')}&size=40&background=fff&color=4e73df`;
};

const handleImageError = (event) => {
  event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value?.username || 'User')}&size=40&background=fff&color=4e73df`;
};

const getNavLinkStyle = (path) => {
  return {
    ...navLinkStyle,
    ...(isActiveLink(path) && { background: 'rgba(255,255,255,0.1)' })
  };
};

const getSettingsDropdownStyle = () => {
  return {
    maxHeight: showSettings.value ? '200px' : '0',
    overflow: 'hidden',
    transition: 'max-height 0.3s ease'
  };
};

// Styles
const sidebarStyle = computed(() => {
  const baseStyle = {
    width: '250px',
    background: 'linear-gradient(180deg, #4e73df 0%, #224abe 100%)',
    color: '#fff',
    padding: '24px 0',
    boxShadow: '2px 0 4px rgba(0,0,0,0.1)',
    position: 'fixed',
    height: '100vh',
    left: 0,
    top: 0,
    overflowY: 'auto',
    zIndex: 1000,
    transition: 'transform 0.3s ease',
    display: 'flex',
    flexDirection: 'column'
  };
  
  // On mobile, hide sidebar by default unless it's active
  if (isMobile.value && !sidebarActive.value) {
    return {
      ...baseStyle,
      transform: 'translateX(-100%)',
      width: '0',
      overflow: 'hidden',
      pointerEvents: 'none'
    };
  }
  
  // On mobile when active, ensure full width
  if (isMobile.value && sidebarActive.value) {
    return {
      ...baseStyle,
      transform: 'translateX(0)',
      width: '250px',
      pointerEvents: 'auto'
    };
  }
  
  return baseStyle;
});

const brandStyle = {
  fontSize: '24px',
  fontWeight: 'bold',
  padding: '0 24px 24px',
  borderBottom: '1px solid rgba(255,255,255,0.1)',
  marginBottom: '24px',
  display: 'block'
};

const navStyle = {
  display: 'flex',
  flexDirection: 'column'
};

const navLinkStyle = {
  color: '#fff',
  textDecoration: 'none',
  padding: '14px 24px',
  transition: 'background 0.3s',
  position: 'relative',
  display: 'flex',
  alignItems: 'center',
  gap: '12px',
  borderBottom: '1px solid rgba(255,255,255,0.1)'
};

const notificationBadgeStyle = {
  position: 'absolute',
  top: '12px',
  right: '24px',
  background: '#e74a3b',
  color: 'white',
  borderRadius: '10px',
  padding: '2px 6px',
  fontSize: '11px',
  fontWeight: 'bold',
  minWidth: '18px',
  textAlign: 'center'
};

const userSectionStyle = {
  marginTop: 'auto',
  paddingTop: '24px',
  borderTop: '1px solid rgba(255,255,255,0.1)',
  position: 'relative'
};

const userInfoStyle = {
  padding: '12px 24px',
  display: 'flex',
  alignItems: 'center',
  gap: '12px',
  textDecoration: 'none',
  color: 'white'
};

const userAvatarStyle = {
  width: '40px',
  height: '40px',
  borderRadius: '50%',
  border: '2px solid white'
};

const usernameDisplayStyle = {
  fontWeight: '600',
  fontSize: '14px'
};

const settingsBtnStyle = {
  background: 'none',
  border: 'none',
  color: 'white',
  cursor: 'pointer',
  padding: '14px 24px',
  textAlign: 'left',
  width: '100%',
  fontSize: '14px',
  transition: 'background 0.3s',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'space-between'
};

const dropdownLinkStyle = {
  padding: '12px 24px 12px 48px',
  fontSize: '14px',
  textDecoration: 'none',
  color: 'white',
  display: 'block',
  transition: 'background 0.3s'
};

const logoutBtnStyle = {
  background: 'none',
  border: 'none',
  color: 'white',
  cursor: 'pointer',
  padding: '12px 24px 12px 48px',
  textAlign: 'left',
  width: '100%',
  fontSize: '14px',
  transition: 'background 0.3s'
};

const mobileMenuToggleStyle = computed(() => {
  const baseStyle = {
    position: 'fixed',
    top: '20px',
    left: '20px',
    zIndex: 1001,
    color: 'black',
    border: 'none',
    padding: '12px 16px',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '20px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
    background: 'white'
  };
  
  if (isMobile.value) {
    return {
      ...baseStyle,
      display: 'block'
    };
  }
  
  return {
    ...baseStyle,
    display: 'none'
  };
});

const modalStyle = {
  display: 'flex',
  position: 'fixed',
  zIndex: 2000,
  left: 0,
  top: 0,
  width: '100%',
  height: '100%',
  backgroundColor: 'rgba(0,0,0,0.5)',
  alignItems: 'center',
  justifyContent: 'center'
};

const modalContentStyle = {
  backgroundColor: 'white',
  borderRadius: '12px',
  boxShadow: '0 8px 32px rgba(0, 0, 0, 0.2)',
  maxWidth: '450px',
  width: '90%',
  animation: 'slideDown 0.3s ease-out'
};

const modalHeaderStyle = {
  padding: '20px 24px',
  borderBottom: '1px solid #e3e6f0',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center'
};

const modalHeaderH3Style = {
  margin: 0,
  fontSize: '20px',
  color: '#2e3a59',
  fontWeight: '600'
};

const modalCloseStyle = {
  background: 'none',
  border: 'none',
  fontSize: '28px',
  color: '#858796',
  cursor: 'pointer',
  padding: 0,
  width: '32px',
  height: '32px',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  borderRadius: '4px',
  transition: 'all 0.2s'
};

const modalBodyStyle = {
  padding: '24px'
};

const modalBodyPStyle = {
  margin: 0,
  color: '#5a5c69',
  fontSize: '15px',
  lineHeight: '1.6'
};

const modalFooterStyle = {
  padding: '16px 24px',
  borderTop: '1px solid #e3e6f0',
  display: 'flex',
  gap: '12px',
  justifyContent: 'flex-end'
};

const btnStyle = {
  padding: '10px 20px',
  border: 'none',
  borderRadius: '8px',
  fontSize: '14px',
  fontWeight: '600',
  cursor: 'pointer',
  transition: 'all 0.3s ease'
};

const btnSecondaryStyle = {
  background: '#858796',
  color: 'white'
};

const btnDangerStyle = {
  background: '#e74a3b',
  color: 'white'
};

// Computed styles for merged objects
const cancelButtonStyle = computed(() => ({
  ...btnStyle,
  ...btnSecondaryStyle,
  margin: 0,
  padding: '10px 20px',
  fontSize: '14px',
  width: 'auto',
  flex: '0 0 auto'
}));

const logoutButtonStyle = computed(() => ({
  ...btnStyle,
  ...btnDangerStyle,
  margin: 0,
  padding: '10px 20px',
  fontSize: '14px',
  width: 'auto',
  flex: '0 0 auto'
}));
</script>

