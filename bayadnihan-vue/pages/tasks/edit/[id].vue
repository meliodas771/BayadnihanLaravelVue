<template>
  <div>
    <div class="container" :style="containerStyle">
      <!-- Skeleton Loading -->
      <div v-if="isLoadingTask" class="card" :style="cardStyle">
        <div :style="skeletonTitleStyle"></div>
        <div :style="skeletonFormGroupStyle">
          <div :style="skeletonLabelStyle"></div>
          <div :style="skeletonInputStyle"></div>
        </div>
        <div :style="skeletonFormGroupStyle">
          <div :style="skeletonLabelStyle"></div>
          <div :style="skeletonTextareaStyle"></div>
        </div>
        <div :style="skeletonFormGroupStyle">
          <div :style="skeletonLabelStyle"></div>
          <div :style="skeletonInputStyle"></div>
        </div>
        <div :style="skeletonFormGroupStyle">
          <div :style="skeletonLabelStyle"></div>
          <div :style="skeletonInputStyle"></div>
        </div>
        <div :style="skeletonFormGroupStyle">
          <div :style="skeletonLabelStyle"></div>
          <div :style="skeletonInputStyle"></div>
        </div>
        <div :style="skeletonButtonStyle"></div>
      </div>
      <div v-else-if="errors.length > 0" class="card" :style="cardStyle">
        <h1 class="page-title" :style="pageTitleStyle">✏️ Edit Draft Task</h1>
        <div :style="errorStyle">
          {{ errors[0] }}
        </div>
      </div>
      <div v-else-if="!task" class="card" :style="cardStyle">
        <h1 class="page-title" :style="pageTitleStyle">✏️ Edit Draft Task</h1>
        <div :style="errorStyle">
          Task not found. Please check the task ID and try again.
        </div>
      </div>
      <div v-else class="card" :style="cardStyle">
        <h1 class="page-title" :style="pageTitleStyle">✏️ Edit Draft Task</h1>
        
        <div v-if="errors.length > 0" :style="errorStyle">
          {{ errors[0] }}
        </div>
        
        <div v-if="success" :style="successStyle">
          ✓ {{ success }}
        </div>
        
        <form @submit.prevent>
          <input type="hidden" name="is_draft" id="is_draft_input" v-model="formData.is_draft" />
          
          <div :style="formGroupStyle">
            <label :style="labelStyle">Task Title *</label>
            <input 
              name="title"
              v-model="formData.title"
              @input="handleInputChange"
              placeholder="e.g., Buy groceries from supermarket"
              :style="inputStyle"
              required
            />
          </div>
          
          <div :style="formGroupStyle">
            <label :style="labelStyle">Description *</label>
            <textarea 
              name="description"
              v-model="formData.description"
              @input="handleInputChange"
              placeholder="Provide detailed instructions for the task..."
              :style="textareaStyle"
              required
            />
          </div>
          
          <div :style="formGroupStyle">
            <label :style="labelStyle">Category</label>
            <select 
              name="category"
              v-model="formData.category"
              @change="handleInputChange"
              :style="inputStyle"
            >
              <option value="">Select Category</option>
              <option value="general">📌 General</option>
              <option value="grocery">🛒 Grocery</option>
              <option value="laundry">👕 Laundry</option>
              <option value="tutoring">📚 Tutoring</option>
              <option value="powerpoint">💻 PowerPoint</option>
            </select>
          </div>
          
          <div :style="formGroupStyle">
            <label :style="labelStyle">Price (₱) *</label>
            <input 
              name="price"
              type="number" 
              step="0.01"
              v-model="formData.price"
              @input="handleInputChange"
              placeholder="0.00"
              :style="inputStyle"
              required
            />
          </div>
          
          <div :style="formGroupStyle">
            <label :style="labelStyle">Payment Method</label>
            <select 
              name="payment_method"
              v-model="formData.payment_method"
              @change="handleInputChange"
              :style="inputStyle"
            >
              <option value="cash">💵 Cash</option>
              <option value="gcash">📱 G-cash</option>
            </select>
          </div>
          
          <div v-if="showAttachment" :style="formGroupStyle">
            <label :style="labelStyle">
              Attachment (optional - Images: JPG, PNG, GIF | Documents: PDF, DOC, DOCX | Max 10MB)
            </label>
            <input 
              type="file"
              name="attachment"
              @change="handleInputChange"
              :style="inputStyle"
              accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            />
          </div>
          
          <button 
            type="button" 
            @click="showEditModal"
            :style="submitButtonStyle"
          >
            Update Task
          </button>
        </form>
      </div>
    </div>

    <!-- Edit Task Modal -->
    <div 
      v-if="showModal"
      id="editTaskModal"
      :style="modalStyle"
      @click.self="closeEditModal"
    >
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3 :style="{ margin: 0, fontSize: '20px', color: '#2e3a59', fontWeight: '600' }">Update Task</h3>
          <button 
            @click="closeEditModal"
            :style="{ background: 'none', border: 'none', fontSize: '24px', cursor: 'pointer' }"
          >
            &times;
          </button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="{ margin: 0, color: '#5a5c69', fontSize: '15px', lineHeight: '1.6' }">How would you like to proceed?</p>
        </div>
        <div :style="modalFooterStyle">
          <button :style="btnSecondaryStyle" @click="closeEditModal">Cancel</button>
          <button :style="btnDraftStyle" @click="saveAsDraft">💾 Save as Draft</button>
          <button :style="btnPublishStyle" @click="publishNow">🚀 Publish Now</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';
import { useUser } from '~/composables/useUser';

const route = useRoute();
const router = useRouter();
const { tasksAPI } = useAPI();
const { isAuthenticated, isLoading: userLoading } = useUser();

const taskId = route.params.id;

const task = ref(null);
const formData = ref({
  title: '',
  description: '',
  category: '',
  price: '',
  payment_method: 'cash',
  attachment: null,
  is_draft: '1'
});
const errors = ref([]);
const success = ref('');
const showModal = ref(false);
const showAttachment = ref(true);
const isLoading = ref(false);
const isLoadingTask = ref(true);

watch(() => formData.value.category, (newCategory) => {
  showAttachment.value = newCategory.toLowerCase() !== 'general';
});

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
  
  isLoadingTask.value = true;
  errors.value = [];
  task.value = null;
  
  try {
    const response = await tasksAPI.getEdit(taskId);
    
    // Handle different response formats
    let taskData;
    if (response && response.task) {
      taskData = response.task;
    } else if (response && response.id) {
      taskData = response;
    } else if (response && Array.isArray(response) && response.length > 0) {
      taskData = response[0];
    } else {
      throw new Error('Invalid response format from server.');
    }
    
    if (!taskData) {
      throw new Error('Task data is empty');
    }
    
    task.value = taskData;
    formData.value = {
      title: taskData.title || '',
      description: taskData.description || '',
      category: taskData.category || '',
      price: taskData.price || '',
      payment_method: taskData.payment_method || 'cash',
      attachment: null,
      is_draft: taskData.is_draft ? '1' : '0'
    };
    showAttachment.value = taskData.category?.toLowerCase() !== 'general';
  } catch (error) {
    console.error('Error loading task:', error);
    errors.value = [error.message || 'Failed to load task. Please try again.'];
  } finally {
    isLoadingTask.value = false;
  }
});

const handleInputChange = (e) => {
  const { name, value, files } = e.target;
  formData.value = {
    ...formData.value,
    [name]: files ? files[0] : value
  };
};

const showEditModal = () => {
  if (process.client) {
    const form = document.querySelector('form');
    if (form && form.checkValidity()) {
      showModal.value = true;
    } else if (form) {
      form.reportValidity();
    }
  }
};

const closeEditModal = () => {
  showModal.value = false;
};

const saveAsDraft = () => {
  formData.value.is_draft = '1';
  closeEditModal();
  handleSubmit(true);
};

const publishNow = () => {
  formData.value.is_draft = '0';
  closeEditModal();
  handleSubmit(false);
};

const handleSubmit = async (isDraft = true) => {
  isLoading.value = true;
  errors.value = [];
  success.value = '';
  
  const draftValue = isDraft !== undefined ? (isDraft ? '1' : '0') : formData.value.is_draft;
  
  try {
    const taskData = {
      title: formData.value.title,
      description: formData.value.description,
      category: formData.value.category || null,
      price: formData.value.price ? parseFloat(formData.value.price) : null,
      payment_method: formData.value.payment_method,
      attachment: formData.value.attachment,
      is_draft: draftValue === '1' ? 1 : 0,
    };

    const response = await tasksAPI.update(taskId, taskData);
    
    if (response.success || response.task) {
      success.value = 'Task updated successfully!';
      setTimeout(() => {
        router.push('/tasks');
      }, 1500);
    } else {
      errors.value = [response.error || 'Failed to update task. Please try again.'];
    }
  } catch (error) {
    errors.value = [error.message || 'An error occurred while updating the task.'];
  } finally {
    isLoading.value = false;
  }
};

const containerStyle = { maxWidth: '800px', margin: '24px auto', padding: '0 16px' };
const cardStyle = { background: '#fff', padding: '32px', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.08)', border: 'none' };
const pageTitleStyle = { color: '#2e3a59', fontSize: '28px', marginBottom: '24px' };
const formGroupStyle = { marginBottom: '20px' };
const labelStyle = { display: 'flex', position: 'relative', color: '#5a5c69', fontWeight: '600', marginBottom: '8px', fontSize: '14px' };
const inputStyle = { width: '100%', padding: '12px', border: '1px solid #d1d3e2', borderRadius: '8px', fontSize: '14px', fontFamily: 'inherit', transition: 'border 0.3s', backgroundColor: '#ffffff', color: '#2e3a59' };
const textareaStyle = { ...inputStyle, minHeight: '120px', resize: 'vertical' };
const submitButtonStyle = { width: '100%', background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', color: '#fff', height: '40px', border: 'none', borderRadius: '8px', fontSize: '16px', fontWeight: '600', cursor: 'pointer', transition: 'transform 0.2s' };
const errorStyle = { background: '#f8d7da', color: '#721c24', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #f5c6cb' };
const successStyle = { background: '#d4edda', color: '#155724', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', border: '1px solid #c3e6cb' };
const modalStyle = { position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0,0,0,0.5)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 };
const modalContentStyle = { background: 'white', borderRadius: '8px', width: '90%', maxWidth: '500px', boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)' };
const modalHeaderStyle = { padding: '20px', borderBottom: '1px solid #e3e6f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' };
const modalBodyStyle = { padding: '20px' };
const modalFooterStyle = { padding: '20px', borderTop: '1px solid #e3e6f0', display: 'flex', gap: '10px', justifyContent: 'flex-end' };
const btnSecondaryStyle = { background: '#858796', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' };
const btnDraftStyle = { background: 'linear-gradient(135deg, #858796 0%, #5a5c69 100%)', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' };
const btnPublishStyle = { background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' };

// Skeleton Loading Styles
const skeletonAnimation = '@keyframes skeleton-loading { 0% { background-position: -200px 0; } 100% { background-position: calc(200px + 100%) 0; } }';
const skeletonBaseStyle = {
  background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
  backgroundSize: '200px 100%',
  animation: 'skeleton-loading 1.5s ease-in-out infinite',
  borderRadius: '8px'
};

const skeletonTitleStyle = {
  ...skeletonBaseStyle,
  width: '60%',
  height: '32px',
  marginBottom: '24px'
};

const skeletonFormGroupStyle = {
  marginBottom: '20px'
};

const skeletonLabelStyle = {
  ...skeletonBaseStyle,
  width: '120px',
  height: '16px',
  marginBottom: '8px'
};

const skeletonInputStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '44px'
};

const skeletonTextareaStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '120px'
};

const skeletonButtonStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '40px',
  marginTop: '8px'
};
</script>

<style>
@keyframes skeleton-loading {
  0% { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}
</style>

