<template>
  <div style="width: 100%; max-width: 100%; box-sizing: border-box; padding: 0 16px;">
      <h1 class="page-title" :style="pageTitleStyle">📋 Available Tasks</h1>
      
      <!-- Category Filter -->
      <div class="filter-section" :style="filterSectionStyle">
        <label for="categoryFilter" :style="labelStyle">🔍 Filter by Category:</label>
        <select 
          id="categoryFilter" 
          v-model="selectedCategory"
          @change="handleCategoryChange"
          :style="selectStyle"
        >
          <option value="all">All Categories</option>
          <option value="general">General</option>
          <option value="grocery">Grocery</option>
          <option value="laundry">Laundry</option>
          <option value="powerpoint">Powerpoint</option>
          <option value="tutoring">Tutoring</option>
          <option value="academics">Academics</option>
        </select>
        <span id="taskCount" :style="{ color: '#858796', fontSize: '14px', marginLeft: 'auto' }">
          Showing {{ visibleCount }} task{{ visibleCount !== 1 ? 's' : '' }}
        </span>
      </div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="grid" :style="gridStyle">
        <div 
          v-for="i in 6" 
          :key="i" 
          class="card"
          :style="cardStyle"
        >
          <div :style="{ 
            width: '80%', 
            height: '24px', 
            background: '#e3e6f0', 
            marginBottom: '12px',
            borderRadius: '4px',
            animation: 'pulse 1.5s ease-in-out infinite'
          }"></div>
          <div :style="{ 
            width: '60%', 
            height: '16px', 
            background: '#e3e6f0', 
            marginBottom: '12px',
            borderRadius: '4px',
            animation: 'pulse 1.5s ease-in-out infinite'
          }"></div>
          <div :style="{ 
            width: '45%', 
            height: '36px', 
            background: '#e3e6f0', 
            borderRadius: '6px',
            animation: 'pulse 1.5s ease-in-out infinite'
          }"></div>
        </div>
      </div>

      <!-- Tasks Grid -->
      <template v-else>
        <div v-if="filteredTasks.length > 0" id="tasksGrid" class="grid" :style="gridStyle">
          <div 
            v-for="task in filteredTasks" 
            :key="task.id" 
            class="card task-card"
            :style="cardStyle"
            :data-category="task.category ? task.category.toLowerCase() : ''"
            @mouseenter="handleCardHover($event, true)"
            @mouseleave="handleCardHover($event, false)"
          >
            <h3 :style="cardTitleStyle">{{ task.title }}</h3>
            <div :style="cardMetaStyle">
              {{ task.category || 'Uncategorized' }} · ₱{{ formatPrice(task.price) }} · {{ task.payment_method || 'N/A' }}
            </div>
            <NuxtLink 
              :to="`/tasks/${task.id}`" 
              class="btn"
              :style="buttonStyle"
              @click="handleTaskClick($event, task)"
            >
              View Details
            </NuxtLink>
          </div>
        </div>
        <div v-else id="noTasksMessage" :style="{ textAlign: 'center', padding: '40px', color: '#858796' }">
          <p :style="{ fontSize: '18px' }">📭 No tasks found in this category</p>
        </div>
      </template>
      
      <!-- Draft Tasks Section -->
      <div v-if="draftTasks.length > 0" :style="draftSectionStyle">
        <h2 :style="{ color: '#2e3a59', marginBottom: '24px', fontSize: '24px', display: 'flex' }">
          📝 Draft Tasks
        </h2>
        <p :style="{ color: '#858796', marginBottom: '20px', fontSize: '14px' }">
          Your draft tasks are only visible to you. Edit and publish them when ready.
        </p>
        <div id="draftTasksGrid" class="grid" :style="gridStyle">
          <div 
            v-for="task in draftTasks" 
            :key="task.id" 
            class="card task-card draft-task-card"
            :style="draftCardStyle"
            @mouseenter="handleCardHover($event, true)"
            @mouseleave="handleCardHover($event, false)"
          >
            <div :style="draftBadgeStyle">DRAFT</div>
            <h3 :style="cardTitleStyle">{{ task.title }}</h3>
            <div :style="cardMetaStyle">
              {{ task.category || 'Uncategorized' }} · ₱{{ formatPrice(task.price) }} · {{ task.payment_method || 'N/A' }}
            </div>
            <div :style="{ marginTop: '12px', display: 'flex', gap: '8px' }">
              <NuxtLink 
                :to="`/tasks/${task.id}`" 
                class="btn"
                :style="buttonStyle"
                @click="handleTaskClick($event, task)"
              >
                View Details
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';
import { useUser } from '~/composables/useUser';

const router = useRouter();
const { tasksAPI } = useAPI();
const { isAuthenticated, isLoading: userLoading } = useUser();

const tasks = ref([]);
const draftTasks = ref([]);
const selectedCategory = ref('all');
const visibleCount = ref(0);
const isLoading = ref(true);

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
  
  await fetchTasks();
});

const fetchTasks = async () => {
  try {
    isLoading.value = true;
    const response = await tasksAPI.getAll();
    
    if (response.tasks) {
      tasks.value = response.tasks;
    } else if (Array.isArray(response)) {
      const published = response.filter(task => !task.is_draft);
      tasks.value = published;
    } else {
      tasks.value = [];
    }
    
    if (response.draftTasks) {
      draftTasks.value = response.draftTasks;
    } else {
      draftTasks.value = [];
    }
  } catch (error) {
    console.error('Error fetching tasks:', error);
    tasks.value = [];
    draftTasks.value = [];
  } finally {
    isLoading.value = false;
  }
};

const filteredTasks = computed(() => {
  if (selectedCategory.value === 'all') {
    return tasks.value;
  }
  return tasks.value.filter(task => {
    const taskCategory = task.category ? task.category.toLowerCase() : '';
    return taskCategory === selectedCategory.value;
  });
});

watch(filteredTasks, (newTasks) => {
  visibleCount.value = newTasks.length;
}, { immediate: true });

const handleCategoryChange = () => {
  // Category change is handled by computed property
};

const handleCardHover = (event, isEnter) => {
  if (isEnter) {
    event.currentTarget.style.transform = 'translateY(-4px)';
    event.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
  } else {
    event.currentTarget.style.transform = 'translateY(0)';
    event.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
  }
};

const handleTaskClick = (event, task) => {
  if (!task.id) {
    event.preventDefault();
    console.error('Task ID is missing:', task);
  }
};

const formatPrice = (price) => {
  if (!price) return '0.00';
  if (typeof price === 'number') {
    return price.toFixed(2);
  }
  return parseFloat(price || 0).toFixed(2);
};

// Styles
const layoutStyles = `
  .page-title { 
    color: #2e3a59; 
    font-size: 28px; 
    margin-bottom: 24px; 
  }
  .filter-section { 
    background: #f8f9fc; 
    padding: 20px; 
    border-radius: 12px; 
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
  }
  .filter-section label {
    color: #5a5c69;
    font-weight: 600;
    font-size: 14px;
  }
  .filter-section select {
    padding: 10px 16px;
    border: 1px solid #d1d3e2;
    border-radius: 8px;
    font-size: 14px;
    color: #5a5c69;
    background: white;
    cursor: pointer;
    min-width: 200px;
  }
  .filter-section select:focus {
    outline: none;
    border-color: #4e73df;
  }
  .grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
    gap: 20px; 
  }
  .card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .card h3 { 
    color: #2e3a59; 
    font-size: 18px; 
    margin-bottom: 12px; 
  }
  .card-meta { 
    color: #858796; 
    font-size: 14px; 
    margin-bottom: 12px; 
  }
  .btn { 
    display: inline-block; 
    padding: 10px 20px; 
    border-radius: 8px; 
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
    color: #fff; 
    text-decoration: none; 
    font-weight: 600; 
    border: none; 
    cursor: pointer; 
    transition: transform 0.2s; 
  }
  .btn:hover { 
    transform: translateY(-2px); 
  }
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.5;
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

// Container style removed - using inline styles in template to ensure proper containment

const pageTitleStyle = {
  display: 'flex',
  color: '#2e3a59',
  fontSize: '40px',
  marginBottom: '24px',
};

const filterSectionStyle = {
  background: '#f8f9fc',
  padding: '20px',
  borderRadius: '12px',
  marginBottom: '24px',
  display: 'flex',
  gap: '12px',
  alignItems: 'center',
  flexWrap: 'wrap'
};

const labelStyle = {
  display: 'flex',
  position: 'relative',
  color: '#5a5c69',
  fontWeight: '600',
  fontSize: '14px'
};

const selectStyle = {
  padding: '10px 16px',
  border: '1px solid #d1d3e2',
  borderRadius: '8px',
  fontSize: '14px',
  color: '#5a5c69',
  background: 'white',
  cursor: 'pointer',
  minWidth: '200px'
};

const gridStyle = {
  display: 'grid',
  gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
  gap: '20px',
  marginBottom: '24px',
};

const cardStyle = {
  background: '#fff',
  padding: '20px',
  borderRadius: '12px',
  boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
  transition: 'transform 0.2s, box-shadow 0.2s',
  border: 'none',
  borderLeft: '4px solid #4e73df',
};

const cardTitleStyle = {
  display: 'flex',
  position: 'relative',
  color: '#2e3a59',
  fontSize: '18px',
  marginBottom: '12px'
};

const cardMetaStyle = {
  display: 'flex',
  position: 'relative',
  color: '#858796',
  fontSize: '14px',
  marginBottom: '12px'
};

const buttonStyle = {
  display: 'inline-block',
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: 'white',
  padding: '10px 20px',
  borderRadius: '6px',
  textDecoration: 'none',
  fontSize: '14px',
  fontWeight: '600',
  textAlign: 'center',
  border: 'none',
  cursor: 'pointer',
  width: '45%',
  display: 'flex'
};

const draftSectionStyle = {
  marginTop: '60px',
  paddingTop: '40px',
  borderTop: '3px solid #e3e6f0'
};

const draftBadgeStyle = {
  position: 'absolute',
  top: '5px',
  right: '12px',
  background: '#fff3cd',
  color: '#856404',
  padding: '4px 8px',
  borderRadius: '12px',
  fontSize: '10px',
  fontWeight: '600'
};

const draftCardStyle = {
  ...cardStyle,
  borderLeft: '4px solid #f6c23e',
  position: 'relative'
};

</script>

