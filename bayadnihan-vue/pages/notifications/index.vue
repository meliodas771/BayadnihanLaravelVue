<template>
  <div>
    <div class="container" :style="containerStyle">
        <div class="card" :style="cardStyle">
          <div class="notifications-header" :style="headerStyle">
            <h1 :style="pageTitleStyle">Notifications</h1>
            <div class="notifications-buttons" :style="buttonsContainerStyle">
              <button :style="secondaryButtonStyle" @click="markAllRead">Mark All Read</button>
              <button :style="dangerButtonStyle" @click="showDeleteAllModal = true">Delete All</button>
            </div>
          </div>
          
          <div v-if="isLoading">
            <!-- Skeleton Loading Items -->
            <div 
              v-for="n in 5" 
              :key="`skeleton-${n}`"
              :style="skeletonItemStyle"
            >
              <div :style="skeletonMessageStyle"></div>
              <div :style="skeletonTimeStyle"></div>
            </div>
          </div>
          <div v-else-if="notifications.length === 0" :style="emptyStateStyle">
            <div :style="emptyStateIconStyle">🔔</div>
            <p>No notifications</p>
          </div>
          <div v-else>
            <div 
              v-for="notification in notifications" 
              :key="notification.id" 
              :style="itemStyle(notification)"
              @click="handleNotificationClick(notification)"
              :class="{ 'notification-clickable': notification.task_id }"
            >
              <div :style="getMessageStyle(notification)">
                {{ notification.message }}
              </div>
              <div :style="itemTimeStyle">{{ formatTime(notification.created_at) }}</div>
              <div class="notification-menu" :style="notificationMenuStyle" @click.stop>
                <button :style="menuButtonStyle" @click="toggleMenu(notification.id, $event)">⋯</button>
                <div v-if="openMenuId === notification.id" :style="menuDropdownStyle(notification.id)" @click.stop>
                  <button :style="menuItemStyle" @click="markAsRead(notification.id)">Mark as Read</button>
                  <button :style="deleteMenuItemStyle" @click="openDeleteModal(notification)">Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" :style="modalStyle" @click="showDeleteModal = false">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3>Delete Notification</h3>
          <button @click="showDeleteModal = false" :style="modalCloseStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p>Are you sure you want to delete this notification?</p>
        </div>
        <div :style="modalFooterStyle">
          <button :style="btnSecondaryStyle" @click="showDeleteModal = false">Cancel</button>
          <button :style="btnDangerStyle" @click="confirmDelete">Delete</button>
        </div>
      </div>
    </div>

    <!-- Delete All Modal -->
    <div v-if="showDeleteAllModal" :style="modalStyle" @click="showDeleteAllModal = false">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3>Delete All Notifications</h3>
          <button @click="showDeleteAllModal = false" :style="modalCloseStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p>Are you sure you want to delete all notifications?</p>
        </div>
        <div :style="modalFooterStyle">
          <button :style="btnSecondaryStyle" @click="showDeleteAllModal = false">Cancel</button>
          <button :style="btnDangerStyle" @click="confirmDeleteAll">Delete All</button>
        </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';
import { useUser } from '~/composables/useUser';

const router = useRouter();
const { notificationsAPI } = useAPI();
const { isAuthenticated, isLoading: userLoading } = useUser();

const notifications = ref([]);
const isLoading = ref(true);
const showDeleteModal = ref(false);
const showDeleteAllModal = ref(false);
const notificationToDelete = ref(null);
const openMenuId = ref(null);
const windowWidth = ref(process.client ? window.innerWidth : 1024);

onMounted(async () => {
  // Check authentication first
  if (process.client) {
    const checkAuth = () => {
      if (!userLoading.value) {
        if (!isAuthenticated.value) {
          router.push('/login');
          return false;
        }
        return true;
      } else {
        setTimeout(checkAuth, 100);
        return false;
      }
    };
    
    if (!checkAuth()) return;
  }
  
  await fetchNotifications();
  // Add click outside listener
  if (process.client) {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', handleResize);
    handleResize();
  }
});

onUnmounted(() => {
  if (process.client) {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', handleResize);
  }
});

const handleResize = () => {
  if (process.client) {
    windowWidth.value = window.innerWidth;
  }
};

const fetchNotifications = async (showLoading = true) => {
  try {
    if (showLoading) {
      isLoading.value = true;
    }
    const response = await notificationsAPI.getAll();
    const notificationsData = response.notifications || response || [];
    notifications.value = Array.isArray(notificationsData) ? notificationsData : [];
  } catch (error) {
    console.error('Error fetching notifications:', error);
    notifications.value = [];
  } finally {
    if (showLoading) {
      isLoading.value = false;
    }
  }
};

const toggleMenu = (id, event) => {
  if (event) {
    event.stopPropagation();
  }
  openMenuId.value = openMenuId.value === id ? null : id;
};

const handleClickOutside = (event) => {
  // Close menu if clicking outside
  if (openMenuId.value && !event.target.closest('.notification-menu')) {
    openMenuId.value = null;
  }
};

const handleNotificationClick = async (notification) => {
  // Only redirect if notification has a task_id and we're not clicking on the menu
  if (notification.task_id) {
    // Mark as read if not already read
    if (!notification.read) {
      try {
        await notificationsAPI.markRead(notification.id);
        // Update the notification in the local array
        const index = notifications.value.findIndex(n => n.id === notification.id);
        if (index !== -1) {
          notifications.value[index].read = true;
        }
        // Emit event to update sidebar badge
        if (process.client) {
          window.dispatchEvent(new CustomEvent('notification-read'));
        }
      } catch (error) {
        console.error('Error marking notification as read:', error);
      }
    }
    router.push(`/tasks/${notification.task_id}`);
  }
};

const markAsRead = async (id) => {
  try {
    await notificationsAPI.markRead(id);
    await fetchNotifications(false); // No skeleton loading
    openMenuId.value = null;
    // Emit event to update sidebar badge
    if (process.client) {
      window.dispatchEvent(new CustomEvent('notification-read'));
    }
  } catch (error) {
    console.error('Error marking as read:', error);
  }
};

const markAllRead = async () => {
  try {
    await notificationsAPI.markAllRead();
    await fetchNotifications(false); // No skeleton loading
    // Emit event to update sidebar badge
    if (process.client) {
      window.dispatchEvent(new CustomEvent('notification-read'));
    }
  } catch (error) {
    console.error('Error marking all as read:', error);
  }
};

const openDeleteModal = (notification) => {
  notificationToDelete.value = notification;
  showDeleteModal.value = true;
  openMenuId.value = null;
};

const confirmDelete = async () => {
  if (!notificationToDelete.value) return;
  try {
    await notificationsAPI.delete(notificationToDelete.value.id);
    await fetchNotifications(false); // No skeleton loading
    showDeleteModal.value = false;
    notificationToDelete.value = null;
  } catch (error) {
    console.error('Error deleting notification:', error);
  }
};

const confirmDeleteAll = async () => {
  try {
    await notificationsAPI.deleteAll();
    await fetchNotifications(false); // No skeleton loading
    showDeleteAllModal.value = false;
  } catch (error) {
    console.error('Error deleting all notifications:', error);
  }
};

const formatTime = (dateString) => {
  if (!dateString) return 'Recently';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
  return `${Math.floor(diffInSeconds / 86400)} days ago`;
};

const itemStyle = (notification) => {
  const baseStyle = {
    padding: '16px',
    borderBottom: '1px solid #f8f9fc',
    borderRadius: '8px',
    marginBottom: '8px',
    transition: 'background 0.2s',
    position: 'relative'
  };
  if (!notification.read) {
    return { ...baseStyle, background: '#e7f3ff', borderLeft: '4px solid #4e73df' };
  }
  return baseStyle;
};

const messageStyle = (notification) => {
  if (notification.type === 'report_warning') {
    return { color: '#721c24', fontWeight: '600' };
  }
  return { color: '#2e3a59' };
};

const getMessageStyle = (notification) => {
  return {
    ...itemMessageStyle,
    ...messageStyle(notification)
  };
};

const layoutStyles = `
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background: #f8f9fc;
    display: flex;
    min-height: 100vh;
  }
  .main-content { 
    margin-left: 250px; 
    padding: 24px; 
    width: calc(100% - 250px); 
  }
  .container { 
    max-width: 900px; 
    margin: 24px auto; 
    padding: 0 16px; 
  }
  .card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 32px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }
  .notification-clickable {
    cursor: pointer;
  }
  .notification-clickable:hover {
    background: #f0f4f8 !important;
  }
  .notification-menu {
    z-index: 10;
  }
  @keyframes skeleton-loading {
    0% {
      background-position: 200% 0;
    }
    100% {
      background-position: -200% 0;
    }
  }
  @media (max-width: 768px) {
    .main-content { 
      margin-left: 0; 
      width: 100%; 
      padding-top: 70px; 
    }
  }
`;

useHead({
  style: [
    {
      children: layoutStyles
    }
  ]
});

const isMobile = computed(() => windowWidth.value <= 768);

const containerStyle = { maxWidth: '900px', margin: '24px auto', padding: '0 16px' };
const cardStyle = computed(() => ({
  background: '#fff',
  padding: isMobile.value ? '20px' : '32px',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
}));

const pageTitleStyle = computed(() => ({
  color: '#2e3a59',
  fontSize: isMobile.value ? '22px' : '28px',
  marginBottom: isMobile.value ? '0' : '24px'
}));

const headerStyle = computed(() => ({
  display: 'flex',
  flexDirection: isMobile.value ? 'column' : 'row',
  justifyContent: 'space-between',
  alignItems: isMobile.value ? 'flex-start' : 'center',
  marginBottom: '24px',
  gap: isMobile.value ? '12px' : '0'
}));

const buttonsContainerStyle = computed(() => ({
  display: 'flex',
  gap: isMobile.value ? '6px' : '8px',
  width: isMobile.value ? '100%' : 'auto'
}));

const buttonStyle = computed(() => ({
  padding: isMobile.value ? '6px 10px' : '10px 16px',
  border: 'none',
  borderRadius: '8px',
  fontWeight: '600',
  cursor: 'pointer',
  fontSize: isMobile.value ? '11px' : '14px',
  flex: isMobile.value ? '1' : 'none'
}));

const secondaryButtonStyle = computed(() => ({
  ...buttonStyle.value,
  background: '#858796',
  color: 'white'
}));

const dangerButtonStyle = computed(() => ({
  ...buttonStyle.value,
  background: 'linear-gradient(135deg, #e74a3b 0%, #c0392b 100%)',
  color: 'white'
}));
const itemMessageStyle = { fontSize: '15px', marginBottom: '8px', lineHeight: '1.6' };
const itemTimeStyle = { color: '#858796', fontSize: '13px' };
const emptyStateStyle = { textAlign: 'center', padding: '60px 20px', color: '#858796' };
const emptyStateIconStyle = { fontSize: '64px', marginBottom: '16px' };
const skeletonItemStyle = {
  padding: '16px',
  borderBottom: '1px solid #f8f9fc',
  borderRadius: '8px',
  marginBottom: '8px',
  position: 'relative',
  background: '#fff'
};
const skeletonMessageStyle = {
  height: '20px',
  background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
  backgroundSize: '200% 100%',
  borderRadius: '4px',
  marginBottom: '12px',
  width: '85%',
  animation: 'skeleton-loading 1.5s ease-in-out infinite'
};
const skeletonTimeStyle = {
  height: '14px',
  background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
  backgroundSize: '200% 100%',
  borderRadius: '4px',
  width: '40%',
  animation: 'skeleton-loading 1.5s ease-in-out infinite'
};
const notificationMenuStyle = { position: 'absolute', right: '16px', top: '16px' };
const menuButtonStyle = { background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer', padding: '4px 8px', color: '#858796' };
const menuDropdownStyle = (id) => ({ display: 'block', position: 'absolute', right: 0, top: '100%', background: 'white', border: '1px solid #e3e6f0', borderRadius: '8px', boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)', minWidth: '140px', zIndex: 1000, marginTop: '4px' });
const menuItemStyle = { display: 'block', width: '100%', padding: '10px 16px', border: 'none', background: 'none', textAlign: 'left', cursor: 'pointer', fontSize: '14px', color: '#2e3a59' };
const deleteMenuItemStyle = { ...menuItemStyle, color: '#e74a3b' };
const modalStyle = { display: 'flex', position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center', zIndex: 1000 };
const modalContentStyle = { background: 'white', borderRadius: '12px', width: '90%', maxWidth: '500px', boxShadow: '0 4px 20px rgba(0,0,0,0.3)' };
const modalHeaderStyle = { padding: '20px 24px', borderBottom: '1px solid #e3e6f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' };
const modalBodyStyle = { padding: '20px 24px' };
const modalFooterStyle = { padding: '20px 24px', borderTop: '1px solid #e3e6f0', display: 'flex', gap: '10px', justifyContent: 'flex-end' };
const modalCloseStyle = { background: 'none', border: 'none', fontSize: '24px', cursor: 'pointer' };
const btnSecondaryStyle = { background: '#858796', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' };
const btnDangerStyle = { background: '#e74a3b', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' };
</script>

