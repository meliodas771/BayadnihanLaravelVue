<template>
  <div>
    <div class="container" :style="containerStyle">
        <h1 :style="pageTitleStyle">My Tasks</h1>
        
        <div v-if="loading" :style="loadingStyle">Loading tasks...</div>
        
        <div v-if="postedTasks.length > 0" :style="sectionStyle">
          <h2 :style="sectionTitleStyle">Posted Tasks</h2>
          <div :style="gridStyle">
            <div v-for="task in postedTasks" :key="task.id" :style="cardStyle">
              <h3 :style="cardTitleStyle">{{ task.title }}</h3>
              <p :style="cardMetaStyle">Status: {{ task.status }}</p>
              <NuxtLink :to="`/tasks/${task.id}`" :style="btnStyle">View Task</NuxtLink>
            </div>
          </div>
        </div>
        
        <div v-if="assignedTasks.length > 0" :style="sectionStyle">
          <h2 :style="sectionTitleStyle">Assigned Tasks</h2>
          <div :style="gridStyle">
            <div v-for="task in assignedTasks" :key="task.id" :style="cardStyle">
              <h3 :style="cardTitleStyle">{{ task.title }}</h3>
              <p :style="cardMetaStyle">Status: {{ task.status }}</p>
              <NuxtLink :to="`/tasks/${task.id}`" :style="btnStyle">View Task</NuxtLink>
            </div>
          </div>
        </div>

        <div v-if="cancelledTasks.length > 0" :style="sectionStyle">
          <h2 :style="sectionTitleStyleCancelled">Cancelled Tasks</h2>
          <div :style="gridStyle">
            <div v-for="task in cancelledTasks" :key="task.id" :style="cardStyle">
              <h3 :style="cardTitleStyle">{{ task.title }}</h3>
              <p :style="cardMetaStyle">Status: {{ task.status }}</p>
              <NuxtLink :to="`/tasks/${task.id}`" :style="btnStyleCancelled">View Task</NuxtLink>
            </div>
          </div>
        </div>

        <div v-if="!loading && postedTasks.length === 0 && assignedTasks.length === 0 && cancelledTasks.length === 0" :style="emptyStateStyle">
          <div :style="emptyStateIconStyle">📋</div>
          <p>No tasks found</p>
        </div>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';
import { useUser } from '~/composables/useUser';

const router = useRouter();
const { userAPI } = useAPI();
const { isAuthenticated, isLoading: userLoading } = useUser();

const postedTasks = ref([]);
const assignedTasks = ref([]);
const cancelledTasks = ref([]);
const loading = ref(true);

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
  
  try {
    const response = await userAPI.getMyTasks();
    const data = response.tasks || response;
    if (data.posted_tasks) {
      postedTasks.value = data.posted_tasks;
    }
    if (data.assigned_tasks) {
      assignedTasks.value = data.assigned_tasks;
    }
    if (data.cancelled_tasks) {
      cancelledTasks.value = data.cancelled_tasks;
    }
  } catch (error) {
    console.error('Error fetching my tasks:', error);
  } finally {
    loading.value = false;
  }
});

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
    max-width: 1200px; 
    margin: 24px auto; 
    padding: 0 16px; 
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

const containerStyle = { maxWidth: '1200px', margin: '24px auto', padding: '0 16px' };
const pageTitleStyle = { color: '#2e3a59', fontSize: '28px', marginBottom: '24px' };
const loadingStyle = { textAlign: 'center', padding: '60px 20px', color: '#858796', fontSize: '18px' };
const sectionStyle = { marginTop: '32px' };
const sectionTitleStyle = { color: '#2e3a59', fontSize: '24px', marginBottom: '20px' };
const gridStyle = { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '20px', marginBottom: '32px' };
const cardStyle = { background: '#fff', padding: '20px', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.08)', borderLeft: '4px solid #4e73df' };
const cardTitleStyle = { color: '#2e3a59', fontSize: '18px', marginBottom: '12px', margin: 0 };
const cardMetaStyle = { color: '#858796', fontSize: '14px', marginBottom: '16px', lineHeight: '1.5' };
const btnStyle = { display: 'inline-block', background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', color: '#fff', padding: '10px 20px', borderRadius: '8px', textDecoration: 'none', fontSize: '14px', fontWeight: '600' };
const emptyStateStyle = { textAlign: 'center', padding: '60px 20px', color: '#858796', background: '#fff', borderRadius: '12px' };
const emptyStateIconStyle = { fontSize: '64px', marginBottom: '16px' };
const btnStyleCancelled = { display: 'inline-block', background: '#dc3545', color: '#fff', padding: '10px 20px', borderRadius: '8px', textDecoration: 'none', fontSize: '14px', fontWeight: '600' };
const sectionTitleStyleCancelled = { color: '#dc3545', fontSize: '24px', marginBottom: '20px' };
</script>

