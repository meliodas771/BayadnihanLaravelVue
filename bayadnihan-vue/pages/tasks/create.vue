<template>
  <div>
    <div :style="containerStyle">
        <div class="card" :style="cardStyle">
          <h1 class="page-title" :style="pageTitleStyle">✍️ Post a New Task</h1>
        
          <div v-if="errors.length > 0" :style="errorStyle">
            <div v-for="(error, index) in errors" :key="index">{{ error }}</div>
          </div>
          
          <form @submit.prevent>
            <input 
              type="hidden" 
              name="is_draft" 
              id="is_draft_input"
              v-model="formData.is_draft"
            />
          
            <div :style="formGroupStyle">
              <label :style="labelStyle">Task Title *</label>
              <input 
                name="title"
                placeholder="e.g., Buy groceries from supermarket"
                v-model="formData.title"
                @input="handleInputChange"
                :style="inputStyle"
                required
              />
            </div>
            
            <div :style="formGroupStyle">
              <label :style="labelStyle">Description *</label>
              <textarea 
                name="description"
                placeholder="Provide detailed instructions for the task..."
                v-model="formData.description"
                @input="handleInputChange"
                :style="textareaStyle"
                required
              />
            </div>
            
            <div :style="formGroupStyle">
              <label :style="labelStyle">Category</label>
              <select 
                name="category"
                id="categorySelect"
                v-model="formData.category"
                @change="handleInputChange"
                :style="inputStyle"
              >
                <option value="">Select Category</option>
                <option value="grocery">🛒 Grocery</option>
                <option value="laundry">👕 Laundry</option>
                <option value="tutoring">📚 Tutoring</option>
                <option value="powerpoint">💻 PowerPoint</option>
                <option value="academics">📋 Academics</option>
                <option value="other">📌 Other</option>
              </select>
            </div>
            
            <div :style="formGroupStyle">
              <label :style="labelStyle">Price (₱)</label>
              <input 
                name="price"
                type="number" 
                step="0.01"
                placeholder="0.00"
                v-model="formData.price"
                @input="handleInputChange"
                :style="inputStyle"
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
            
            <div v-if="showAttachment" :style="formGroupStyle" id="attachmentGroup">
              <label :style="labelStyle">
                Attachment (optional - Images: JPG, PNG, GIF | Documents: PDF, DOC, DOCX | Max 10MB)
              </label>
              <input 
                type="file"
                name="attachment"
                id="attachmentInput"
                @change="handleInputChange"
                :style="inputStyle"
                accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
              />
            </div>
            
            <button type="submit" id="formSubmitBtn" style="display: none"></button>
            
            <button 
              type="button" 
              class="submit"
              @click="showCreateModal"
              :style="{
                ...submitButtonStyle,
                ...(isLoading && { opacity: 0.6, cursor: 'not-allowed' })
              }"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Creating...' : 'Create Task' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Create Task Modal -->
    <div 
      id="createTaskModal"
      class="modal"
      :style="modalStyle"
      @click="(e) => {
        if (e.target.id === 'createTaskModal') {
          closeCreateModal();
        }
      }"
    >
      <div :style="modalContentStyle" class="modal-content">
        <div :style="modalHeaderStyle" class="modal-header">
          <h3 :style="modalHeaderH3Style">Create Task</h3>
          <button 
            class="modal-close"
            @click="closeCreateModal"
            :style="modalCloseStyle"
          >
            &times;
          </button>
        </div>
        <div :style="modalBodyStyle" class="modal-body">
          <p :style="{ margin: 0, color: '#5a5c69', fontSize: '15px', lineHeight: '1.6' }">How would you like to proceed?</p>
        </div>
        <div :style="modalFooterStyle" class="modal-footer">
          <button 
            class="btn btn-secondary"
            type="button"
            :style="btnSecondaryStyle"
            @click="closeCreateModal"
          >
            Cancel
          </button>
          <button 
            class="btn"
            type="button"
            :style="{
              ...btnDraftStyle,
              ...(isLoading && { opacity: 0.6, cursor: 'not-allowed' })
            }"
            @click="saveAsDraft"
            :disabled="isLoading"
          >
            💾 Save to Draft
          </button>
          <button 
            class="btn"
            type="button"
            :style="{
              ...btnPublishStyle,
              ...(isLoading && { opacity: 0.6, cursor: 'not-allowed' })
            }"
            @click="publishNow"
            :disabled="isLoading"
          >
            🚀 Publish Now
          </button>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAPI } from '~/utils/api';

const router = useRouter();
const { tasksAPI } = useAPI();

const formData = ref({
  title: '',
  description: '',
  category: '',
  price: '',
  payment_method: 'cash',
  attachment: null,
  is_draft: '0'
});
const errors = ref([]);
const showModal = ref(false);
const showAttachment = ref(true);
const isLoading = ref(false);

watch(() => formData.value.category, (newCategory) => {
  showAttachment.value = newCategory.toLowerCase() !== 'other';
});

const handleInputChange = (e) => {
  const { name, value, files } = e.target;
  
  formData.value = {
    ...formData.value,
    [name]: files ? files[0] : value
  };
};

const validateForm = () => {
  const newErrors = [];
  if (!formData.value.title.trim()) newErrors.push('Title is required');
  if (!formData.value.description.trim()) newErrors.push('Description is required');
  errors.value = newErrors;
  return newErrors.length === 0;
};

const showCreateModal = () => {
  if (process.client) {
    const form = document.querySelector('form');
    if (form && form.checkValidity()) {
      showModal.value = true;
    } else if (form) {
      form.reportValidity();
    }
  }
};

const closeCreateModal = () => {
  showModal.value = false;
};

const saveAsDraft = () => {
  formData.value.is_draft = '1';
  closeCreateModal();
  handleSubmit(true);
};

const publishNow = () => {
  formData.value.is_draft = '0';
  closeCreateModal();
  handleSubmit(false);
};

const handleSubmit = async (isDraft = false) => {
  isLoading.value = true;
  errors.value = [];
  
  const draftValue = isDraft !== undefined ? (isDraft ? '1' : '0') : formData.value.is_draft;
  
  try {
    if (!formData.value.title || !formData.value.title.trim()) {
      errors.value = ['Title is required'];
      isLoading.value = false;
      return;
    }
    
    if (!formData.value.description || !formData.value.description.trim()) {
      errors.value = ['Description is required'];
      isLoading.value = false;
      return;
    }
    
    if (!formData.value.price || formData.value.price === '') {
      errors.value = ['Price is required'];
      isLoading.value = false;
      return;
    }

    const taskData = {
      title: formData.value.title.trim(),
      description: formData.value.description.trim(),
      category: formData.value.category || null,
      price: parseFloat(formData.value.price) || 0,
      payment_method: formData.value.payment_method || 'cash',
      attachment: formData.value.attachment,
      is_draft: draftValue === '1' ? 1 : 0,
    };

    const response = await tasksAPI.create(taskData);
    
    if (response.success || response.task) {
      if (draftValue === '1') {
        router.push('/tasks');
      } else {
        router.push(`/tasks/${response.task?.id || response.id}`);
      }
    } else {
      errors.value = [response.error || 'Failed to create task. Please try again.'];
    }
  } catch (error) {
    const errorMessage = error.message || 'An error occurred while creating the task.';
    errors.value = [errorMessage];
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  if (process.client && showModal.value) {
    const handleClickOutside = (event) => {
      if (showModal.value && event.target.id === 'createTaskModal') {
        closeCreateModal();
      }
    };
    document.addEventListener('click', handleClickOutside);
    
    return () => {
      document.removeEventListener('click', handleClickOutside);
    };
  }
});

const layoutStyles = `
  .container { 
    max-width: 900px; 
    margin: 0 auto; 
    padding: 0 16px; 
  }
  .card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 32px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
    min-height: 600px;
  }
`;

useHead({
  style: [
    {
      children: layoutStyles
    }
  ]
});

const containerStyle = {
  maxWidth: '900px',
  margin: '0 auto',
  padding: '0 16px'
};

const cardStyle = {
  padding: '32px',
  backgroundColor: '#ffffff',
  width: '100%',
  boxSizing: 'border-box',
  minHeight: '600px'
};

const pageTitleStyle = {
  color: '#2e3a59',
  fontSize: '28px',
  marginBottom: '24px'
};

const formGroupStyle = {
  marginBottom: '20px'
};

const labelStyle = {
  display: 'flex',
  position: 'relative',
  color: '#5a5c69',
  fontWeight: '600',
  marginBottom: '8px',
  fontSize: '14px'
};

const inputStyle = {
  width: '100%',
  padding: '12px',
  border: '1px solid #d1d3e2',
  borderRadius: '8px',
  fontSize: '14px',
  fontFamily: 'inherit',
  transition: 'border 0.3s',
  backgroundColor: '#ffffff',
  color: '#000000'
};

const textareaStyle = {
  ...inputStyle,
  minHeight: '120px',
  resize: 'vertical'
};

const submitButtonStyle = {
  width: '100%',
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: '#fff',
  height: '40px',
  border: 'none',
  borderRadius: '8px',
  fontSize: '16px',
  fontWeight: '600',
  cursor: 'pointer',
  transition: 'transform 0.2s'
};

const errorStyle = {
  background: '#f8d7da',
  color: '#721c24',
  padding: '12px 16px',
  borderRadius: '8px',
  marginBottom: '20px',
  border: '1px solid #f5c6cb'
};

const modalStyle = computed(() => ({
  display: showModal.value ? 'flex' : 'none',
  position: 'fixed',
  top: 0,
  left: 0,
  width: '100%',
  height: '100%',
  backgroundColor: 'rgba(0,0,0,0.5)',
  justifyContent: 'center',
  alignItems: 'center',
  zIndex: 1000
}));

const modalContentStyle = {
  background: 'white',
  borderRadius: '8px',
  width: '90%',
  maxWidth: '500px',
  boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
  padding: 0
};

const modalHeaderStyle = {
  padding: '20px',
  borderBottom: '1px solid #e3e6f0',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center'
};

const modalHeaderH3Style = {
  margin: 0,
  color: '#2e3a59',
  fontSize: '20px'
};

const modalCloseStyle = {
  background: 'none',
  border: 'none',
  fontSize: '28px',
  color: '#858796',
  cursor: 'pointer',
  padding: 0,
  width: '30px',
  height: '30px',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center'
};

const modalBodyStyle = {
  padding: '20px'
};

const modalFooterStyle = {
  padding: '20px',
  borderTop: '1px solid #e3e6f0',
  display: 'flex',
  gap: '10px',
  justifyContent: 'flex-end'
};

const btnStyle = {
  padding: '10px 20px',
  border: 'none',
  borderRadius: '4px',
  fontWeight: '600',
  cursor: 'pointer',
  fontSize: '14px'
};

const btnSecondaryStyle = {
  ...btnStyle,
  background: '#858796',
  color: 'white'
};

const btnDraftStyle = {
  ...btnStyle,
  background: 'linear-gradient(135deg, #858796 0%, #5a5c69 100%)',
  color: 'white'
};

const btnPublishStyle = {
  ...btnStyle,
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: 'white'
};
</script>
