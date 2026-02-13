<template>
  <div>
    <!-- Desktop Sidebar -->
    <div :style="sidebarStyle" id="sidebar" class="sidebar desktop-sidebar">
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

    <!-- Mobile Bottom Navigation -->
    <div :style="bottomNavStyle" class="bottom-nav">
      <a 
        href="/tasks" 
        :style="getBottomNavItemStyle('/tasks')"
        @click.prevent="navigateTo('/tasks')"
        class="bottom-nav-item"
      >
        <svg :style="bottomNavIconStyle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="9" y1="9" x2="15" y2="9"></line>
          <line x1="9" y1="15" x2="15" y2="15"></line>
        </svg>
        <span :style="bottomNavLabelStyle">Tasks</span>
      </a>
      
      <a 
        v-if="user && user.role !== 'doer'"
        href="/tasks/create" 
        :style="getBottomNavItemStyle('/tasks/create')"
        @click.prevent="navigateTo('/tasks/create')"
        class="bottom-nav-item"
      >
        <svg :style="bottomNavIconStyle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 20h9"></path>
          <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
        </svg>
        <span :style="bottomNavLabelStyle">Create</span>
      </a>
      
      <a 
        href="/notifications" 
        :style="getBottomNavItemStyle('/notifications')"
        @click.prevent="navigateTo('/notifications')"
        class="bottom-nav-item"
      >
        <div style="position: relative; display: inline-block;">
          <svg :style="bottomNavIconStyle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          <span v-if="unreadNotificationCount > 0" :style="bottomNavBadgeStyle">
            {{ unreadNotificationCount }}
          </span>
        </div>
        <span :style="bottomNavLabelStyle">Notification</span>
      </a>
      
      <a 
        href="/profile" 
        :style="getBottomNavItemStyle('/profile')"
        @click.prevent="navigateTo('/profile')"
        class="bottom-nav-item"
      >
        <svg :style="bottomNavIconStyle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span :style="bottomNavLabelStyle">Profile</span>
      </a>
      
      <button
        type="button"
        :style="getBottomNavItemStyle('settings')"
        @click="toggleMobileSettings"
        class="bottom-nav-item"
      >
        <svg :style="bottomNavIconStyle" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m-2 2l-4.2 4.2M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m-2-2l-4.2-4.2"></path>
        </svg>
        <span :style="bottomNavLabelStyle">Settings</span>
      </button>
    </div>

    <!-- Mobile Settings Modal -->
    <div 
      v-if="showMobileSettings"
      :style="mobileSettingsOverlayStyle"
      @click="showMobileSettings = false"
    >
      <div 
        :style="mobileSettingsMenuStyle"
        @click.stop
      >
        <div :style="mobileSettingsHeaderStyle">
          <h3 :style="mobileSettingsHeaderH3Style">Settings</h3>
          <button 
            :style="mobileSettingsCloseStyle"
            @click="showMobileSettings = false"
          >
            &times;
          </button>
        </div>
        <div :style="mobileSettingsContentStyle">
          <a 
            href="/report/create" 
            :style="mobileSettingsLinkStyle"
            @click.prevent="navigateToAndClose('/report/create')"
          >
            Report User
          </a>
          <button 
            type="button" 
            :style="mobileSettingsLogoutStyle"
            @click="showLogoutModal = true; showMobileSettings = false"
          >
            Logout
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
const showMobileSettings = ref(false);
const showLogoutModal = ref(false);
const unreadNotificationCount = ref(0);
const isMobile = ref(false);

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
  @keyframes slideUp {
    from {
      transform: translateY(100%);
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

// Update mobile state
const updateMobileState = () => {
  if (process.client) {
    isMobile.value = window.innerWidth <= 768;
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

  // Update mobile state
  updateMobileState();

  // Listen for notification read events
  const handleNotificationRead = () => {
    fetchUnreadNotifications();
  };

  const handleResize = () => {
    updateMobileState();
  };

  window.addEventListener('notification-read', handleNotificationRead);
  window.addEventListener('resize', handleResize);

  onUnmounted(() => {
    window.removeEventListener('notification-read', handleNotificationRead);
    window.removeEventListener('resize', handleResize);
    
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

const toggleMobileSettings = () => {
  showMobileSettings.value = !showMobileSettings.value;
};

const navigateToAndClose = (path) => {
  showMobileSettings.value = false;
  router.push(path);
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

const navigateTo = (path) => {
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
    return `http://192.168.1.19:8000/storage/profile_pics/${user.value.profile_pic}`;
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

const getBottomNavItemStyle = (path) => {
  const isActive = path === 'settings' ? false : isActiveLink(path);
  return {
    ...bottomNavItemStyle,
    ...(isActive && { 
      color: '#4e73df',
      background: 'rgba(78, 115, 223, 0.1)'
    })
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
const sidebarStyle = computed(() => ({
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
  display: isMobile.value ? 'none' : 'flex',
  flexDirection: 'column'
}));

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

// Bottom Navigation Styles
const bottomNavStyle = computed(() => ({
  display: isMobile.value ? 'flex' : 'none',
  position: 'fixed',
  bottom: 0,
  left: 0,
  right: 0,
  background: 'white',
  borderTop: '1px solid #e3e6f0',
  boxShadow: '0 -2px 10px rgba(0,0,0,0.1)',
  zIndex: 1000,
  padding: '8px 0',
  justifyContent: 'space-around',
  alignItems: 'center'
}));

const bottomNavItemStyle = {
  display: 'flex',
  flexDirection: 'column',
  alignItems: 'center',
  justifyContent: 'center',
  padding: '8px 12px',
  textDecoration: 'none',
  color: '#858796',
  cursor: 'pointer',
  transition: 'all 0.3s',
  flex: 1,
  maxWidth: '80px',
  background: 'transparent',
  border: 'none',
  fontFamily: 'inherit',
  borderRadius: '8px'
};

const bottomNavIconStyle = {
  width: '24px',
  height: '24px',
  marginBottom: '4px'
};

const bottomNavLabelStyle = {
  fontSize: '11px',
  fontWeight: '500'
};

const bottomNavBadgeStyle = {
  position: 'absolute',
  top: '-6px',
  right: '-8px',
  background: '#e74a3b',
  color: 'white',
  borderRadius: '10px',
  padding: '2px 5px',
  fontSize: '9px',
  fontWeight: 'bold',
  minWidth: '16px',
  textAlign: 'center',
  border: '2px solid white'
};

// Mobile Settings Modal Styles
const mobileSettingsOverlayStyle = {
  display: 'flex',
  position: 'fixed',
  zIndex: 2000,
  left: 0,
  top: 0,
  width: '100%',
  height: '100%',
  backgroundColor: 'rgba(0,0,0,0.5)',
  alignItems: 'flex-end',
  justifyContent: 'center',
  animation: 'fadeIn 0.2s ease-out'
};

const mobileSettingsMenuStyle = {
  backgroundColor: 'white',
  borderRadius: '16px 16px 0 0',
  width: '100%',
  maxHeight: '50vh',
  animation: 'slideUp 0.3s ease-out'
};

const mobileSettingsHeaderStyle = {
  padding: '20px 24px',
  borderBottom: '1px solid #e3e6f0',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center'
};

const mobileSettingsHeaderH3Style = {
  margin: 0,
  fontSize: '18px',
  color: '#2e3a59',
  fontWeight: '600'
};

const mobileSettingsCloseStyle = {
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
  justifyContent: 'center'
};

const mobileSettingsContentStyle = {
  padding: '12px 0'
};

const mobileSettingsLinkStyle = {
  display: 'block',
  padding: '16px 24px',
  fontSize: '16px',
  textDecoration: 'none',
  color: '#2e3a59',
  borderBottom: '1px solid #f0f0f0',
  transition: 'background 0.3s'
};

const mobileSettingsLogoutStyle = {
  background: 'none',
  border: 'none',
  color: '#e74a3b',
  cursor: 'pointer',
  padding: '16px 24px',
  textAlign: 'left',
  width: '100%',
  fontSize: '16px',
  fontWeight: '500',
  transition: 'background 0.3s'
};

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

