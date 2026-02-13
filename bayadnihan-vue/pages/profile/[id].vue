<template>
  <div>
    <div class="container" :style="containerStyle">
        <!-- Skeleton Loader -->
        <div v-if="isLoading">
          <div class="card" :style="cardStyle">
            <!-- Profile Header Skeleton -->
            <div :style="profileSectionStyle">
              <div :style="skeletonAvatarStyle"></div>
              <div :style="{ flex: 1 }">
                <div :style="skeletonTitleStyle"></div>
                <div :style="skeletonTextStyle"></div>
                <div :style="skeletonTextSmallStyle"></div>
              </div>
            </div>
            
            <!-- Stats Grid Skeleton -->
            <div :style="statsGridStyle">
              <div :style="skeletonStatCardStyle">
                <div :style="skeletonStatValueStyle"></div>
                <div :style="skeletonStatLabelStyle"></div>
              </div>
              <div :style="skeletonStatCardStyle">
                <div :style="skeletonStatValueStyle"></div>
                <div :style="skeletonStatLabelStyle"></div>
              </div>
            </div>
          </div>
          
          <!-- Reviews Section Skeleton -->
          <div class="card" :style="cardStyle">
            <div :style="skeletonSectionTitleStyle"></div>
            <div v-for="i in 3" :key="i" :style="skeletonFeedbackItemStyle">
              <div :style="skeletonStarsStyle"></div>
              <div :style="skeletonTextStyle"></div>
              <div :style="skeletonTextStyle"></div>
            </div>
          </div>
        </div>
        <div v-else-if="user">
          <!-- Profile Header Card -->
          <div class="card" :style="cardStyle">
            <div :style="profileSectionStyle">
              <img 
                :src="profilePicUrl" 
                alt="Profile"
                :style="profilePicStyle"
              />
              <div :style="userInfoStyle">
                <h1 :style="usernameStyle">{{ displayUsername }}</h1>
                
                <!-- Rating as Doer -->
                <div v-if="showCompletedTasks && stats.avgRating" :style="ratingContainerStyle">
                  <div :style="ratingLabelStyle">Rating as Doer:</div>
                  <div :style="ratingDisplayStyle">
                    <div :style="starsContainerStyle">
                      <span v-for="i in 5" :key="i" :style="getProfileStarStyle(i, stats.avgRating)">★</span>
                    </div>
                    <span :style="ratingValueStyle">{{ stats.avgRating.toFixed(1) }}</span>
                    <span :style="ratingCountStyle">({{ stats.totalFeedbacks }} {{ stats.totalFeedbacks === 1 ? 'review' : 'reviews' }})</span>
                  </div>
                </div>
                
                <!-- Rating as Poster -->
                <div v-if="showPostedTasks && stats.avgRatingAsPoster" :style="ratingContainerStyle">
                  <div :style="ratingLabelStyle">Rating as Poster:</div>
                  <div :style="ratingDisplayStyle">
                    <div :style="starsContainerStyle">
                      <span v-for="i in 5" :key="i" :style="getProfileStarStyle(i, stats.avgRatingAsPoster)">★</span>
                    </div>
                    <span :style="ratingValueStyle">{{ stats.avgRatingAsPoster.toFixed(1) }}</span>
                    <span :style="ratingCountStyle">({{ feedbacksAsPoster.length }} {{ feedbacksAsPoster.length === 1 ? 'review' : 'reviews' }})</span>
                  </div>
                </div>
                
                <!-- No Reviews Message -->
                <p v-if="(!showCompletedTasks || !stats.avgRating) && (!showPostedTasks || !stats.avgRatingAsPoster)" :style="noReviewsStyle">
                  <span :style="{ fontSize: '24px', display: 'block', marginBottom: '8px' }">⭐</span>
                  No reviews yet
                </p>
              </div>
            </div>
            
            <div :style="statsGridStyle">
              <div v-if="showCompletedTasks" :style="statCardStyle">
                <h3 :style="statValueStyle">{{ stats.completedTasksCount || completedTasks.length }}</h3>
                <p :style="statTitleStyle">Tasks Completed</p>
              </div>
              <div v-if="showPostedTasks" :style="statCardStyle">
                <h3 :style="statValueStyle">{{ stats.postedTasksCount || postedTasks.length }}</h3>
                <p :style="statTitleStyle">Tasks Posted</p>
              </div>
            </div>
          </div>

          <!-- Reviews as Doer Section -->
          <div v-if="showCompletedTasks && feedbacks.length > 0" class="card" :style="cardStyle">
            <h3 :style="sectionTitleStyle">⭐ Reviews & Feedback (as Doer)</h3>
            <div v-for="feedback in feedbacks" :key="feedback.id" :style="feedbackItemStyle">
              <div :style="feedbackHeaderStyle">
                <div>
                  <div :style="feedbackRatingStyle">
                    <span v-for="i in 5" :key="i" :style="getStarStyle(i, feedback.rating)">★</span>
                  </div>
                  <div :style="feedbackFromStyle">by {{ maskUsername(feedback.from_username) }}</div>
                </div>
              </div>
              <p v-if="feedback.reviews" :style="feedbackTextStyle">{{ feedback.reviews }}</p>
              <!--<div :style="feedbackTaskStyle">Task: {{ feedback.task_title }}</div>-->
            </div>
          </div>
          
          <!-- Reviews as Poster Section -->
          <div v-if="showPostedTasks && feedbacksAsPoster.length > 0" class="card" :style="cardStyle">
            <h3 :style="sectionTitleStyle">⭐ Reviews & Feedback (as Poster)</h3>
            <div v-for="feedback in feedbacksAsPoster" :key="feedback.id" :style="feedbackItemStyle">
              <div :style="feedbackHeaderStyle">
                <div>
                  <div :style="feedbackRatingStyle">
                    <span v-for="i in 5" :key="i" :style="getStarStyle(i, feedback.rating)">★</span>
                  </div>
                  <div :style="feedbackFromStyle">by {{ maskUsername(feedback.from_username) }} (doer)</div>
                </div>
              </div>
              <p v-if="feedback.reviews" :style="feedbackTextStyle">{{ feedback.reviews }}</p>
             <!-- <div :style="feedbackTaskStyle">Task: {{ feedback.task_title }}</div> -->
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!showCompletedTasks && !showPostedTasks" class="card" :style="cardStyle">
            <div :style="emptyStateStyle">
              <div :style="emptyStateIconStyle">👤</div>
              <h3 :style="emptyStateTitleStyle">New User</h3>
              <p :style="emptyStateTextStyle">This user hasn't completed or posted any tasks yet.</p>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

const route = useRoute();
const { user: currentUser } = useUser();
const { userAPI } = useAPI();

const userId = route.params.id;
const user = ref(null);
const isLoading = ref(true);
const isMobile = ref(false);
const stats = ref({
  avgRating: null,
  avgRatingAsPoster: null,
  totalFeedbacks: 0,
  completedTasksCount: 0,
  postedTasksCount: 0
});
const completedTasks = ref([]);
const postedTasks = ref([]);
const feedbacks = ref([]);
const feedbacksAsPoster = ref([]);
const context = computed(() => route.query.context || '');

const showCompletedTasks = computed(() => {
  return context.value === 'applicant' || context.value === '' || completedTasks.value.length > 0;
});

const showPostedTasks = computed(() => {
  return context.value === 'poster' || context.value === '' || postedTasks.value.length > 0;
});

const checkMobile = () => {
  if (process.client) {
    isMobile.value = window.innerWidth <= 768;
  }
};

if (process.client) {
  checkMobile();
}

const maskUsername = (username) => {
  if (!username) return '';
  if ((/^\d+$/.test(username) || /^[\d\-]+$/.test(username)) && username.length > 5) {
    if (username.includes('-')) {
      const parts = username.split('-', 2);
      const firstPart = parts[0];
      const secondPart = parts[1] || '';
      
      let maskedFirst = firstPart;
      if (firstPart.length > 2) {
        maskedFirst = firstPart[0] + '*'.repeat(firstPart.length - 2) + firstPart[firstPart.length - 1];
      } else if (firstPart.length === 2) {
        maskedFirst = firstPart[0] + '*';
      }
      
      let maskedSecond = secondPart;
      if (secondPart.length > 3) {
        maskedSecond = secondPart.substring(0, 2) + '*'.repeat(secondPart.length - 3) + secondPart[secondPart.length - 1];
      } else if (secondPart.length === 3) {
        maskedSecond = secondPart.substring(0, 2) + '*';
      } else if (secondPart.length === 2) {
        maskedSecond = secondPart[0] + '*';
      } else {
        maskedSecond = '*'.repeat(secondPart.length);
      }
      
      return maskedFirst + '-' + maskedSecond;
    } else {
      return username[0] + '*'.repeat(Math.max(0, username.length - 1));
    }
  }
  return username;
};

const displayUsername = computed(() => {
  if (!user.value) return '';
  // Always mask username on public profile page
  return maskUsername(user.value.username);
});

const profilePicUrl = computed(() => {
  if (!user.value) return '';
  const config = useRuntimeConfig();
  const apiBaseUrl = config.public.apiBaseUrl?.replace('/api', '') || 'http://localhost:8000';
  if (user.value.profile_pic) {
    return `${apiBaseUrl}/storage/profile_pics/${user.value.profile_pic}`;
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value.username || 'User')}&size=120&background=4e73df&color=fff`;
});

const formatPrice = (price) => {
  if (!price) return '0.00';
  const numPrice = typeof price === 'number' ? price : parseFloat(price || 0);
  return numPrice.toFixed(2);
};

const truncateText = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

const getStarStyle = (index, rating) => ({
  color: index <= rating ? '#f6c23e' : '#d1d3e2',
  fontSize: '20px'
});

const getProfileStarStyle = (index, rating) => ({
  color: index <= rating ? '#f6c23e' : '#d1d3e2',
  fontSize: '24px',
  marginRight: '2px'
});

onMounted(async () => {
  // Setup resize listener
  if (process.client) {
    window.addEventListener('resize', checkMobile);
  }
  
  // Fetch profile data
  try {
    isLoading.value = true;
    const response = await userAPI.getPublicProfile(userId);
    const profileData = response.user || response;
    user.value = profileData;
    stats.value = {
      avgRating: response.avgRating || null,
      avgRatingAsPoster: response.avgRatingAsPoster || null,
      totalFeedbacks: response.totalFeedbacks || 0,
      completedTasksCount: response.completedTasksCount || 0,
      postedTasksCount: response.postedTasksCount || 0
    };
    if (response.completedTasks) {
      completedTasks.value = response.completedTasks;
    }
    if (response.postedTasks) {
      postedTasks.value = response.postedTasks;
    }
    if (response.feedbacks) {
      feedbacks.value = response.feedbacks;
    }
    if (response.feedbacks_as_poster) {
      feedbacksAsPoster.value = response.feedbacks_as_poster;
    }
  } catch (error) {
    console.error('Error fetching public profile:', error);
  } finally {
    isLoading.value = false;
  }
});

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('resize', checkMobile);
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
  .card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 32px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }
  @keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
  }
  @media (max-width: 768px) {
    .main-content { 
      margin-left: 0; 
      width: 100%; 
      padding: 15px !important;
      padding-top: 70px !important; 
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

const containerStyle = computed(() => {
  if (isMobile.value) {
    return { maxWidth: '100%', margin: '0', padding: '0' };
  }
  return { maxWidth: '1200px', margin: '24px auto', padding: '0 16px' };
});

const cardStyle = computed(() => {
  if (isMobile.value) {
    return { 
      background: 'transparent', 
      padding: '16px', 
      borderRadius: '0', 
      boxShadow: 'none', 
      marginBottom: '0' 
    };
  }
  return { 
    background: '#fff', 
    padding: '32px', 
    borderRadius: '12px', 
    boxShadow: '0 2px 8px rgba(0,0,0,0.08)', 
    marginBottom: '24px' 
  };
});

const profileSectionStyle = computed(() => {
  if (isMobile.value) {
    return { 
      display: 'flex', 
      gap: '16px', 
      marginBottom: '20px', 
      alignItems: 'center',
      paddingBottom: '20px',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return { 
    display: 'flex', 
    gap: '24px', 
    marginBottom: '32px', 
    alignItems: 'center' 
  };
});

const profilePicStyle = computed(() => {
  if (isMobile.value) {
    return { width: '80px', height: '80px', borderRadius: '50%', objectFit: 'cover' };
  }
  return { width: '120px', height: '120px', borderRadius: '50%', objectFit: 'cover' };
});

const userInfoStyle = { flex: 1 };
const usernameStyle = computed(() => {
  if (isMobile.value) {
    return { color: '#2e3a59', fontSize: '20px', marginBottom: '8px', fontWeight: '600' };
  }
  return { color: '#2e3a59', fontSize: '24px', marginBottom: '12px' };
});

const ratingContainerStyle = { marginBottom: '12px' };
const ratingLabelStyle = { color: '#858796', fontSize: '13px', marginBottom: '4px', fontWeight: '600' };
const ratingDisplayStyle = { display: 'flex', alignItems: 'center', gap: '8px' };
const starsContainerStyle = { display: 'flex', alignItems: 'center' };
const ratingValueStyle = { color: '#2e3a59', fontSize: '18px', fontWeight: '700' };
const ratingCountStyle = { color: '#858796', fontSize: '14px' };
const noReviewsStyle = { color: '#858796', marginTop: '12px', textAlign: 'center', padding: '12px', background: '#f8f9fc', borderRadius: '8px' };

const statsGridStyle = computed(() => {
  if (isMobile.value) {
    return { 
      display: 'grid', 
      gridTemplateColumns: 'repeat(2, 1fr)', 
      gap: '12px',
      paddingBottom: '20px',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return { 
    display: 'grid', 
    gridTemplateColumns: 'repeat(2, 1fr)', 
    gap: '20px' 
  };
});

const statCardStyle = { background: '#f8f9fc', padding: '20px', borderRadius: '8px', textAlign: 'center' };
const statTitleStyle = { color: '#858796', fontSize: '14px', marginTop: '8px' };
const statValueStyle = { color: '#2e3a59', fontSize: '24px', fontWeight: '700' };

const sectionTitleStyle = computed(() => {
  if (isMobile.value) {
    return { 
      color: '#2e3a59', 
      fontSize: '18px', 
      marginBottom: '16px',
      marginTop: '20px',
      paddingTop: '20px',
      borderTop: '1px solid #e3e6f0'
    };
  }
  return { 
    color: '#2e3a59', 
    fontSize: '20px', 
    marginBottom: '16px' 
  };
});

const taskListStyle = { display: 'flex', flexDirection: 'column', gap: '12px' };
const taskItemStyle = { padding: '16px', background: '#f8f9fc', borderRadius: '8px' };
const taskTitleStyle = { color: '#2e3a59', fontSize: '16px', marginBottom: '8px', fontWeight: '600' };
const taskMetaStyle = { color: '#5a5c69', fontSize: '14px' };
const taskPriceStyle = { color: '#1cc88a' };
const doerUsernameStyle = { color: '#5a5c69', fontWeight: '500' };
const feedbackRatingInlineStyle = { marginTop: '8px', color: '#858796', fontSize: '14px' };
const feedbackItemStyle = { padding: '16px', background: '#f8f9fc', borderRadius: '8px', marginBottom: '12px' };
const feedbackHeaderStyle = { marginBottom: '8px' };
const feedbackRatingStyle = { display: 'flex', gap: '4px', marginBottom: '4px' };
const feedbackFromStyle = { color: '#858796', fontSize: '13px' };
const feedbackTextStyle = { color: '#5a5c69', fontSize: '14px', marginTop: '8px', lineHeight: '1.6' };
const feedbackTaskStyle = { color: '#858796', fontSize: '12px', marginTop: '8px' };
const emptyStateStyle = { textAlign: 'center', padding: '40px' };
const emptyStateIconStyle = { fontSize: '48px', marginBottom: '16px' };
const emptyStateTitleStyle = { color: '#5a5c69', marginBottom: '8px' };
const emptyStateTextStyle = { fontSize: '16px', color: '#858796' };

// Skeleton loader styles
const skeletonBase = {
  background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
  backgroundSize: '1000px 100%',
  animation: 'shimmer 2s infinite',
  borderRadius: '8px'
};

const skeletonAvatarStyle = {
  ...skeletonBase,
  width: '120px',
  height: '120px',
  borderRadius: '50%'
};

const skeletonTitleStyle = {
  ...skeletonBase,
  height: '32px',
  width: '200px',
  marginBottom: '12px'
};

const skeletonTextStyle = {
  ...skeletonBase,
  height: '20px',
  width: '100%',
  marginBottom: '8px'
};

const skeletonTextSmallStyle = {
  ...skeletonBase,
  height: '16px',
  width: '60%',
  marginBottom: '8px'
};

const skeletonStatCardStyle = {
  background: '#f8f9fc',
  padding: '20px',
  borderRadius: '8px',
  textAlign: 'center'
};

const skeletonStatValueStyle = {
  ...skeletonBase,
  height: '32px',
  width: '60px',
  margin: '0 auto 8px'
};

const skeletonStatLabelStyle = {
  ...skeletonBase,
  height: '16px',
  width: '100px',
  margin: '0 auto'
};

const skeletonSectionTitleStyle = {
  ...skeletonBase,
  height: '28px',
  width: '250px',
  marginBottom: '16px'
};

const skeletonFeedbackItemStyle = {
  padding: '16px',
  background: '#f8f9fc',
  borderRadius: '8px',
  marginBottom: '12px'
};

const skeletonStarsStyle = {
  ...skeletonBase,
  height: '24px',
  width: '150px',
  marginBottom: '8px'
};
</script>

