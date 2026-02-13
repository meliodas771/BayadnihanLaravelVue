<template>
  <div>
    <div class="container" :style="containerStyle">
        <!-- Success Message -->
        <div v-if="success" class="alert success" :style="successStyle">{{ success }}</div>
        
        <!-- Skeleton Loading -->
        <div v-if="isLoading" class="card" :style="cardStyle">
          <div :style="skeletonTitleStyle"></div>
          <div :style="skeletonMetaStyle">
            <div :style="skeletonMetaItemStyle"></div>
            <div :style="skeletonMetaItemStyle"></div>
            <div :style="skeletonMetaItemStyle"></div>
            <div :style="skeletonMetaItemStyle"></div>
          </div>
          <div :style="skeletonDescriptionStyle"></div>
          <div :style="skeletonDescriptionStyle"></div>
          <div :style="skeletonDescriptionStyleLast"></div>
        </div>
        <div v-else-if="!task" class="card" :style="cardStyle">
          <h2 :style="{ color: '#2e3a59', marginBottom: '16px' }">Task Not Found</h2>
          <p :style="{ color: '#5a5c69', marginBottom: '24px' }">
            The task you're looking for doesn't exist or you don't have permission to view it.
          </p>
          <NuxtLink to="/tasks" :style="buttonStyle">Back to Tasks</NuxtLink>
        </div>
        <div v-else class="card" :style="cardStyle">
          <h1 class="task-title" :style="taskTitleStyle">{{ task.title }}</h1>
          
          <div class="task-meta" :style="taskMetaStyle">
            <!-- Row 1: Status -->
            <div class="meta-row" :style="metaRowStyle">
              <span :class="`status-badge status-${task.status}`" :style="statusBadgeStyle(task.status)">
                {{ capitalize(task.status) }}
              </span>
            </div>
            
            <!-- Row 2: Category, Price, Payment -->
            <div class="meta-row" :style="metaRowStyle">
              <div class="meta-item" :style="metaItemStyle">
                <span>📂</span>
                <strong>{{ task.category || 'Uncategorized' }}</strong>
              </div>
              <div class="meta-item" :style="metaItemStyle">
                <span>💰</span>
                <strong>₱{{ formatPrice(task.price) }}</strong>
              </div>
              <div class="meta-item" :style="metaItemStyle">
                <span>{{ task.payment_method === 'cash' ? '💵' : '💳' }}</span>
                <strong>{{ capitalize(task.payment_method) }}</strong>
              </div>
            </div>
            
            <!-- Row 3: Posted by -->
            <div class="meta-row" :style="metaRowStyle" v-if="task.poster">
              <div class="meta-item" :style="metaItemStyle">
                <span>👤</span>
                <strong>
                  Posted by: 
                  <NuxtLink 
                    v-if="task.poster && task.poster.id"
                    :to="`/profile/${task.poster.id}?context=poster`"
                    :style="posterLinkStyle"
                  >
                    {{ displayPosterUsername }}
                    <p :style="posterLinkHintStyle">(click to view profile)</p>
                  </NuxtLink>
                  <span v-else>Unknown User</span>
                </strong>
              </div>
            </div>
          </div>
          
          <p class="task-description" :style="taskDescriptionStyle">{{ task.description }}</p>
          
          <div v-if="applyError" :style="errorBoxStyle">
            ✗ {{ applyError }}
          </div>
          
          <!-- Draft task badge and info -->
          <div v-if="task.is_draft && isPoster" :style="draftInfoBoxStyle">
            <strong :style="draftInfoBoxStrongStyle">📝 This is a draft task</strong>
            <p :style="draftInfoBoxPStyle">This task is only visible to you. Edit it or publish it when ready.</p>
            <button
              v-if="task && task.id"
              @click="navigateToEdit"
              class="btn"
              :style="draftEditButtonStyle"
              type="button"
            >
              ✏️ Edit Task
            </button>
          </div>
          
          <!-- Info message for poster viewing their own task (non-draft) -->
          <div v-if="isPoster && !task.is_draft" :style="posterInfoBoxStyle">
            <strong :style="posterInfoBoxStrongStyle">ℹ️ You are the poster of this task</strong>
            <p :style="posterInfoBoxPStyle">You can manage applications and mark the task as completed once a doer finishes it.</p>
          </div>
          
          <!-- Applications Section (for poster) - Hide for draft tasks -->
          <div v-if="isPoster && !task.is_draft && task.status === 'open' && applications.length > 0" :style="applicationsSectionStyle">
            <h3 :style="applicationsTitleStyle">📋 Applications ({{ applications.length }})</h3>
            <div v-for="app in applications" :key="app.id" :style="applicationItemStyle">
              <div>
                <NuxtLink 
                  :to="`/profile/${app.doer?.id}?context=applicant`"
                  :style="applicantLinkStyle"
                >
                  {{ app.doer?.username || 'Unknown User' }}
                  <p :style="applicantLinkHintStyle">(click to view profile)</p>
                </NuxtLink>
                <div :style="appliedAtStyle">Applied {{ formatAppliedAt(app.applied_at) }}</div>
                <span :style="getApplicationStatusBadgeStyle(app.status)">
                  {{ capitalize(app.status) }}
                </span>
              </div>
              <button 
                v-if="app.status === 'pending'"
                @click="handleAcceptApplication(app.id)"
                class="btn"
                :style="acceptButtonStyle"
                :disabled="isAccepting"
              >
                Accept
              </button>
            </div>
          </div>
          
          <!-- Info for doer when task is assigned to them -->
          <template v-if="['assigned', 'in_progress'].includes(task.status) && hasApplied && task.userApplication && task.userApplication.status === 'accepted'">
            <div :style="doerInfoStyle">
              <strong>📌 You are assigned to this task!</strong>
              <p :style="{ marginTop: '8px', marginBottom: '0' }">Complete the task and wait for the poster to mark it as completed.</p>
            </div>
            <button 
              @click="openChatWindow"
              class="btn"
              :style="doerChatButtonStyle"
            >
              💬 Open Chat
              <span v-if="unreadMessageCount > 0" :style="messageBadgeStyle">{{ unreadMessageCount }}</span>
            </button>
          </template>
          
          <div class="actions" :style="actionsStyle">
            <!-- Cancel button for poster when task is open (exclude drafts) -->
            <template v-if="!task.is_draft && task.status === 'open' && isPoster">
              <button 
                @click="showCancelModal"
                class="btn"
                :style="cancelButtonStyle"
              >
                🚫 Cancel Task
              </button>
            </template>
            <!-- Apply button or application status -->
            <template v-if="!task.is_draft && task.status === 'open' && !isPoster && isDoer">
              <!-- Apply Success Message (subtle) -->
              <div v-if="applySuccess" :style="applySuccessStyle">
                ✓ {{ applySuccess }}
              </div>
              <button 
                v-if="!hasApplied"
                @click="showApplyModal"
                class="btn"
                :style="buttonStyle"
              >
                📝 Apply for this Task
              </button>
              <button 
                v-else
                class="btn"
                disabled
                :style="applicationStatusButtonStyle"
              >
                {{ getApplicationStatusText() }}
              </button>
            </template>
            <!-- Mark as Completed and Open Chat buttons for poster when task is assigned/in_progress -->
            <template v-if="isPoster && ['assigned', 'in_progress'].includes(task.status)">
              <button 
                @click="showCompleteModal"
                class="btn btn-success"
                :style="markCompleteButtonStyle"
              >
                ✓ Mark as Completed
              </button>
              <button 
                @click="openChatWindow"
                class="btn"
                :style="chatButtonStyle"
              >
                💬 Open Chat
                <span v-if="unreadMessageCount > 0" :style="messageBadgeStyle">{{ unreadMessageCount }}</span>
              </button>
            </template>
          </div>
          
          <!-- Feedback Section (for poster after task is completed) -->
          <div v-if="task.status === 'completed' && isPoster" :style="feedbackSectionStyle">
            <div v-if="posterFeedbackSuccess" :style="feedbackSuccessStyle">
              ✅ Feedback submitted successfully!
            </div>
            <div v-if="hasPosterFeedback" :style="existingFeedbackStyle">
              <h3 :style="feedbackTitleStyle">⭐ Your Feedback</h3>
              <div :style="feedbackRatingStyle">
                <strong>Rating:</strong>
                <span v-for="i in 5" :key="i" :style="getStarStyle(i, existingPosterFeedback?.rating, true)">
                  ★
                </span>
              </div>
              <div v-if="existingPosterFeedback?.reviews" :style="feedbackReviewStyle">
                <strong>Review:</strong> {{ existingPosterFeedback.reviews }}
              </div>
            </div>
            <div v-else :style="posterFeedbackFormStyle">
              <h3 :style="posterFeedbackTitleStyle">⭐ Give Feedback to the Doer</h3>
              <form @submit.prevent="handleSubmitPosterFeedback">
                <div :style="feedbackFormGroupStyle">
                  <label :style="feedbackLabelStyle">Rating (1-5 stars) *</label>
                  <div :style="starsContainerStyle">
                    <label 
                      v-for="i in 5" 
                      :key="i" 
                      :style="starLabelStyle"
                      @mouseenter="hoverRating = i"
                      @mouseleave="hoverRating = null"
                      @click="selectedRating = i"
                    >
                      <input 
                        type="radio" 
                        :value="i" 
                        v-model="selectedRating" 
                        required 
                        style="display: none;"
                      />
                      <span :style="getStarStyle(i, selectedRating || hoverRating)">★</span>
                    </label>
                  </div>
                </div>
                <div :style="feedbackFormGroupStyle">
                  <label :style="feedbackLabelStyle">Review (optional)</label>
                  <textarea 
                    v-model="posterFeedbackReview"
                    rows="4"
                    :style="feedbackTextareaStyle"
                    placeholder="Share your experience with this doer..."
                  ></textarea>
                </div>
                <button type="submit" :style="posterFeedbackSubmitStyle" :disabled="isSubmittingFeedback">
                  {{ isSubmittingFeedback ? 'Submitting...' : 'Submit Feedback' }}
                </button>
              </form>
            </div>
          </div>
          
          <!-- Doer Feedback Section (for doer after task is completed) -->
          <div v-if="task.status === 'completed' && isAcceptedDoer" :style="feedbackSectionStyle">
            <div v-if="doerFeedbackSuccess" :style="feedbackSuccessStyle">
              ✅ Feedback submitted successfully!
            </div>
            <div v-if="hasDoerFeedback" :style="existingFeedbackStyle">
              <h3 :style="feedbackTitleStyle">⭐ Your Feedback</h3>
              <div :style="feedbackRatingStyle">
                <strong>Rating:</strong>
                <span v-for="i in 5" :key="i" :style="getStarStyle(i, existingDoerFeedback?.rating, true)">
                  ★
                </span>
              </div>
              <div v-if="existingDoerFeedback?.reviews" :style="feedbackReviewStyle">
                <strong>Review:</strong> {{ existingDoerFeedback.reviews }}
              </div>
            </div>
            <div v-else :style="doerFeedbackFormStyle">
              <h3 :style="doerFeedbackTitleStyle">⭐ Give Feedback to the Poster (Optional)</h3>
              <p :style="doerFeedbackSubtitleStyle">Help other doers by sharing your experience working with this poster.</p>
              <form @submit.prevent="handleSubmitDoerFeedback">
                <div :style="feedbackFormGroupStyle">
                  <label :style="doerFeedbackLabelStyle">Rating (1-5 stars) *</label>
                  <div :style="starsContainerStyle">
                    <label 
                      v-for="i in 5" 
                      :key="i" 
                      :style="starLabelStyle"
                      @mouseenter="hoverDoerRating = i"
                      @mouseleave="hoverDoerRating = null"
                      @click="selectedDoerRating = i"
                    >
                      <input 
                        type="radio" 
                        :value="i" 
                        v-model="selectedDoerRating" 
                        required 
                        style="display: none;"
                      />
                      <span :style="getStarStyle(i, selectedDoerRating || hoverDoerRating)">★</span>
                    </label>
                  </div>
                </div>
                <div :style="feedbackFormGroupStyle">
                  <label :style="doerFeedbackLabelStyle">Review (optional)</label>
                  <textarea 
                    v-model="doerFeedbackReview"
                    rows="4"
                    :style="feedbackTextareaStyle"
                    placeholder="Share your experience working with this poster..."
                  ></textarea>
                </div>
                <button type="submit" :style="doerFeedbackSubmitStyle" :disabled="isSubmittingFeedback">
                  {{ isSubmittingFeedback ? 'Submitting...' : 'Submit Feedback' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Floating Chat Window -->
    <Transition name="chat-window">
      <div v-if="canShowChat && showChatWindow" :style="chatWindowStyle">
      <div :style="chatWindowCardStyle">
        <div :style="chatWindowHeaderStyle">
          <div :style="chatWindowHeaderInfoStyle">
            <div :style="chatWindowTitleStyle">💬 Chat about: {{ task.title }}</div>
            <div :style="chatWindowSubtitleStyle">Only the poster and accepted doer can see this chat</div>
          </div>
          <button @click="closeChatWindow" :style="chatCloseButtonStyle" title="Close chat">✕</button>
        </div>
        <div id="chatBody" :style="chatWindowBodyStyle">
          <div v-if="messages.length === 0" :style="{ textAlign: 'center', padding: '20px', color: '#858796' }">
            No messages yet. Start the conversation!
          </div>
          <div v-for="message in messages" :key="message.id" :style="getMessageStyle(message)">
            <div :style="messageHeaderStyle">
              {{ message.sender_name }} • {{ formatTime(message.sent_at) }}
            </div>
            <div v-if="message.image_url" :style="messageImageStyle">
              <img 
                :src="getImageUrl(message.image_url)" 
                alt="Image" 
                @click="openImageInNewTab(message.image_url)"
                :style="messageImageImgStyle"
              />
            </div>
            <div v-if="message.content" :style="messageContentStyle">
              {{ message.content }}
            </div>
          </div>
        </div>
        <form @submit.prevent="sendMessage" :style="chatWindowFooterStyle" enctype="multipart/form-data">
          <input 
            type="file" 
            id="imageInput" 
            ref="imageInput"
            accept="image/*" 
            style="display:none;"
            @change="handleImageSelect"
          />
          <button 
            type="button" 
            @click="triggerImageInput"
            :style="chatImageButtonStyle"
            title="Attach image"
          >
            📷
          </button>
          <div v-if="imagePreview" :style="chatImagePreviewStyle">
            <img :src="imagePreview" alt="Preview" :style="previewImgStyle" />
            <button type="button" @click="removeImage" :style="chatRemoveImageStyle">✕</button>
          </div>
          <textarea 
            id="chatInput" 
            v-model="chatMessage"
            placeholder="Type your message... "
            :style="chatInputStyle"
            @keydown.enter="handleEnterKey"
          ></textarea>
          <button type="submit" :style="sendButtonStyle">Send</button>
        </form>
      </div>
    </div>
    </Transition>
    
    <!-- Completion Confirmation Modal -->
    <div v-if="showCompleteModalVisible" :style="modalOverlayStyle" @click="closeCompleteModal">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3 :style="modalHeaderH3Style">Mark Task as Completed</h3>
          <button @click="closeCompleteModal" :style="modalCloseButtonStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="modalBodyPStyle">Are you sure you want to mark this task as completed?</p>
        </div>
        <div :style="modalFooterStyle">
          <button @click="closeCompleteModal" class="btn btn-secondary" :style="modalCancelButtonStyle">Cancel</button>
          <button @click="handleMarkAsCompleted" class="btn btn-success" :style="modalConfirmButtonStyle">Yes, Complete</button>
        </div>
      </div>
    </div>
    
    <!-- Cancel Task Confirmation Modal -->
    <div v-if="showCancelModalVisible" :style="modalOverlayStyle" @click="closeCancelModal">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3 :style="modalHeaderH3Style">Cancel Task</h3>
          <button @click="closeCancelModal" :style="modalCloseButtonStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="modalBodyPStyle">Are you sure you want to cancel this task? All pending applications will be rejected.</p>
        </div>
        <div :style="modalFooterStyle">
          <button @click="closeCancelModal" class="btn btn-secondary" :style="modalCancelButtonStyle">No, Keep Task</button>
          <button @click="handleCancelTask" class="btn" :style="cancelConfirmButtonStyle">Yes, Cancel Task</button>
        </div>
      </div>
    </div>
    
    <!-- Apply to Task Confirmation Modal -->
    <div v-if="showApplyModalVisible" :style="modalOverlayStyle" @click="closeApplyModal">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3 :style="modalHeaderH3Style">Apply for Task</h3>
          <button @click="closeApplyModal" :style="modalCloseButtonStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="modalBodyPStyle">Are you sure you want to apply for this task? The poster will be notified of your application.</p>
        </div>
        <div :style="modalFooterStyle">
          <button @click="closeApplyModal" class="btn btn-secondary" :style="modalCancelButtonStyle">Cancel</button>
          <button @click="confirmApply" class="btn btn-success" :style="modalConfirmButtonStyle">
            Yes, Apply
          </button>
        </div>
      </div>
    </div>
    
    <!-- Accept Application Confirmation Modal -->
    <div v-if="showAcceptModalVisible" :style="modalOverlayStyle" @click="closeAcceptModal">
      <div :style="modalContentStyle" @click.stop>
        <div :style="modalHeaderStyle">
          <h3 :style="modalHeaderH3Style">Accept Application</h3>
          <button @click="closeAcceptModal" :style="modalCloseButtonStyle">&times;</button>
        </div>
        <div :style="modalBodyStyle">
          <p :style="modalBodyPStyle">Are you sure you want to accept this application? Once accepted, all other applications will be automatically rejected.</p>
        </div>
        <div :style="modalFooterStyle">
          <button @click="closeAcceptModal" class="btn btn-secondary" :style="modalCancelButtonStyle" :disabled="isAccepting">Cancel</button>
          <button @click="confirmAcceptApplication" class="btn btn-success" :style="modalConfirmButtonStyle" :disabled="isAccepting">
            {{ isAccepting ? 'Accepting...' : 'Yes, Accept Application' }}
          </button>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { navigateTo } from '#app';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';
import { useRuntimeConfig } from '#app';

const route = useRoute();
const router = useRouter();
const { user, isAuthenticated, isLoading: userLoading } = useUser();
const { tasksAPI, messagesAPI, feedbackAPI } = useAPI();
const { $echo } = useNuxtApp();

const taskId = route.params.id;
const task = ref(null);
const isLoading = ref(true);
const success = ref('');
const applySuccess = ref('');
const applyError = ref('');
const unreadMessageCount = ref(0);
const showChatWindow = ref(false);
const showCompleteModalVisible = ref(false);
const messages = ref([]);
const chatMessage = ref('');
const selectedImage = ref(null);
const imagePreview = ref(null);
const imageInput = ref(null);
const isLoadingMessages = ref(false);
const windowWidth = ref(process.client ? window.innerWidth : 1024);
const applications = ref([]);
const isAccepting = ref(false);
const showCancelModalVisible = ref(false);
const showAcceptModalVisible = ref(false);
const showApplyModalVisible = ref(false);
const pendingApplicationId = ref(null);
const existingPosterFeedback = ref(null);
const existingDoerFeedback = ref(null);
const posterFeedbackSuccess = ref(false);
const doerFeedbackSuccess = ref(false);
const selectedRating = ref(null);
const selectedDoerRating = ref(null);
const hoverRating = ref(null);
const hoverDoerRating = ref(null);
const posterFeedbackReview = ref('');
const doerFeedbackReview = ref('');
const isSubmittingFeedback = ref(false);

const isPoster = computed(() => task.value && user.value && task.value.poster_id === user.value.id);
const isDoer = computed(() => user.value && user.value.role !== 'poster');
const hasApplied = computed(() => {
  // Only consider pending or accepted applications as "has applied"
  // Rejected applications allow reapplication if user has completed tasks
  return task.value && task.value.userApplication && 
    ['pending', 'accepted'].includes(task.value.userApplication.status);
});
const isAcceptedDoer = computed(() => task.value && task.value.userApplication && 
  ['accepted', 'completed'].includes(task.value.userApplication.status));
const editTaskUrl = computed(() => {
  return task.value && task.value.id ? `/tasks/${task.value.id}/edit` : '';
});

const navigateToEdit = () => {
  if (task.value && task.value.id) {
    console.log('Navigating to edit page for task:', task.value.id);
    router.push(`/tasks/edit/${task.value.id}`);
  }
};

// Computed properties to check if feedback exists
const hasPosterFeedback = computed(() => {
  return existingPosterFeedback.value && existingPosterFeedback.value.rating;
});

const hasDoerFeedback = computed(() => {
  return existingDoerFeedback.value && existingDoerFeedback.value.rating;
});

const canShowChat = computed(() => {
  if (!task.value) return false;
  const status = task.value.status;
  if (!['assigned', 'in_progress'].includes(status)) return false;
  return isPoster.value || isAcceptedDoer.value;
});

const canApply = computed(() => {
  if (!task.value || task.value.is_draft || task.value.status !== 'open' || isPoster.value || !isDoer.value) {
    return false;
  }
  return !hasApplied.value;
});

const getApplicationStatusText = () => {
  if (!task.value || !task.value.userApplication) return '';
  const status = task.value.userApplication.status;
  if (status === 'pending') {
    return '⏳ Application Pending';
  } else if (['accepted', 'completed'].includes(status)) {
    return '✓ Application Accepted';
  } else if (status === 'rejected') {
    return '✗ Application Rejected';
  }
  return `Application ${status}`;
};

const maskUsername = (username) => {
  if (!username) return '';
  // Check if username is numeric or contains only digits and dashes
  if ((/^\d+$/.test(username) || /^[\d\-]+$/.test(username)) && username.length > 5) {
    if (username.includes('-')) {
      const parts = username.split('-', 2);
      const firstPart = parts[0];
      const secondPart = parts[1] || '';
      
      // First part: keep first char, mask middle, keep last char
      let maskedFirst = firstPart;
      if (firstPart.length > 2) {
        maskedFirst = firstPart[0] + '*'.repeat(firstPart.length - 2) + firstPart[firstPart.length - 1];
      } else if (firstPart.length === 2) {
        maskedFirst = firstPart[0] + '*';
      }
      
      // Second part: keep first 2 chars, mask middle, keep last char
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
      // No dash: keep first character, mask the rest
      return username[0] + '*'.repeat(Math.max(0, username.length - 1));
    }
  }
  return username;
};

const displayPosterUsername = computed(() => {
  if (!task.value || !task.value.poster) return 'Unknown User';
  if (isPoster.value || isAcceptedDoer.value) {
    return task.value.poster.username;
  }
  return maskUsername(task.value.poster.username);
});

const openChatWindow = async () => {
  showChatWindow.value = true;
  await fetchMessages();
  // Mark messages as read when opening chat
  try {
    await messagesAPI.markAsRead(taskId);
    // Update unread count to 0 after marking as read
    unreadMessageCount.value = 0;
  } catch (error) {
    console.error('Error marking messages as read:', error);
  }
  // Scroll to bottom
  nextTick(() => {
    scrollChatToBottom();
  });
};

const closeChatWindow = () => {
  showChatWindow.value = false;
};

const fetchMessages = async () => {
  if (!task.value) return;
  try {
    isLoadingMessages.value = true;
    const response = await messagesAPI.getMessages(taskId);
    messages.value = Array.isArray(response) ? response : [];
  } catch (error) {
    console.error('Error fetching messages:', error);
    messages.value = [];
  } finally {
    isLoadingMessages.value = false;
  }
};

const handleEnterKey = (event) => {
  // If Shift+Enter, allow new line (default behavior)
  if (event.shiftKey) {
    return;
  }
  // Otherwise, prevent new line and send message
  event.preventDefault();
  sendMessage();
};

const sendMessage = async () => {
  if (!chatMessage.value.trim() && !selectedImage.value) return;
  
  try {
    await messagesAPI.send(taskId, chatMessage.value, selectedImage.value);
    chatMessage.value = '';
    selectedImage.value = null;
    imagePreview.value = null;
    await fetchMessages();
    nextTick(() => {
      scrollChatToBottom();
    });
  } catch (error) {
    console.error('Error sending message:', error);
    alert(error.message || 'Failed to send message');
  }
};

const triggerImageInput = () => {
  if (imageInput.value) {
    imageInput.value.click();
  }
};

const handleImageSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    selectedImage.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removeImage = () => {
  selectedImage.value = null;
  imagePreview.value = null;
  if (imageInput.value) {
    imageInput.value.value = '';
  }
};

const formatTime = (timestamp) => {
  if (!timestamp) return 'Just now';
  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  return date.toLocaleDateString();
};

const getImageUrl = (imageUrl) => {
  if (!imageUrl) return '';
  const config = useRuntimeConfig();
  const apiBaseUrl = config.public.apiBaseUrl?.replace('/api', '') || 'http://localhost:8000';
  if (imageUrl.startsWith('http')) return imageUrl;
  return `${apiBaseUrl}/${imageUrl}`;
};

const openImageInNewTab = (imageUrl) => {
  window.open(getImageUrl(imageUrl), '_blank');
};

const scrollChatToBottom = () => {
  const chatBody = document.getElementById('chatBody');
  if (chatBody) {
    chatBody.scrollTop = chatBody.scrollHeight;
  }
};

const getMessageStyle = (message) => {
  const isMe = message.sender_id === user.value?.id;
  return {
    ...messageStyle,
    ...(isMe ? messageMeStyle : messageOtherStyle)
  };
};

const fetchFeedback = async () => {
  if (!task.value || task.value.status !== 'completed') {
    // Clear feedback if task is not completed
    existingPosterFeedback.value = null;
    existingDoerFeedback.value = null;
    return;
  }
  
  try {
    // Fetch poster feedback (feedback to doer)
    if (isPoster.value && user.value) {
      try {
        const feedbackResponse = await feedbackAPI.get(taskId);
        // Backend now returns the correct feedback based on user role
        if (feedbackResponse && feedbackResponse.feedback) {
          existingPosterFeedback.value = feedbackResponse.feedback;
        } else {
          existingPosterFeedback.value = null;
        }
      } catch (err) {
        // If feedback doesn't exist (404), that's okay - no feedback submitted yet
        if (err.response?.status !== 404) {
          console.error('Error fetching poster feedback:', err);
        }
        existingPosterFeedback.value = null;
      }
    }
    
    // Fetch doer feedback (feedback to poster)
    if (isAcceptedDoer.value && user.value) {
      try {
        const feedbackResponse = await feedbackAPI.get(taskId);
        // Backend now returns the correct feedback based on user role
        if (feedbackResponse && feedbackResponse.feedback) {
          const feedback = feedbackResponse.feedback;
          // Backend returns feedback from doer to poster for doers
          existingDoerFeedback.value = feedback;
        } else {
          existingDoerFeedback.value = null;
        }
      } catch (err) {
        // If feedback doesn't exist (404), that's okay - no feedback submitted yet
        if (err.response?.status !== 404) {
          console.error('Error fetching doer feedback:', err);
        }
        existingDoerFeedback.value = null;
      }
    }
  } catch (error) {
    console.error('Error fetching feedback:', error);
    existingPosterFeedback.value = null;
    existingDoerFeedback.value = null;
  }
};

const handleSubmitPosterFeedback = async () => {
  if (!selectedRating.value) {
    alert('Please select a rating');
    return;
  }
  
  try {
    isSubmittingFeedback.value = true;
    const response = await feedbackAPI.store(taskId, {
      rating: selectedRating.value,
      reviews: posterFeedbackReview.value || null
    });
    
    console.log('Feedback submission response:', response);
    
    if (response.success || response.feedback) {
      // Set the existing feedback to hide the form
      const feedback = response.feedback || {
        rating: selectedRating.value,
        reviews: posterFeedbackReview.value || null
      };
      
      // Ensure feedback has required fields
      if (!feedback.rating) {
        feedback.rating = selectedRating.value;
      }
      if (!feedback.reviews && posterFeedbackReview.value) {
        feedback.reviews = posterFeedbackReview.value;
      }
      
      // Set the feedback value
      existingPosterFeedback.value = feedback;
      console.log('Set existingPosterFeedback:', existingPosterFeedback.value);
      console.log('existingPosterFeedback.value is truthy?', !!existingPosterFeedback.value);
      
      // Clear form fields
      selectedRating.value = null;
      hoverRating.value = null;
      posterFeedbackReview.value = '';
      
      // Use nextTick to ensure Vue reactivity updates
      await nextTick();
      
      // Refetch feedback to ensure it's properly loaded from server
      await fetchFeedback();
      
      // Show success message for poster feedback
      posterFeedbackSuccess.value = true;
      setTimeout(() => {
        posterFeedbackSuccess.value = false;
      }, 5000);
    } else {
      alert(response.error || 'Failed to submit feedback');
    }
  } catch (error) {
    console.error('Error submitting feedback:', error);
    console.error('Error response:', error.response);
    alert(error.message || 'Failed to submit feedback');
  } finally {
    isSubmittingFeedback.value = false;
  }
};

const handleSubmitDoerFeedback = async () => {
  if (!selectedDoerRating.value) {
    alert('Please select a rating');
    return;
  }
  
  try {
    isSubmittingFeedback.value = true;
    const response = await feedbackAPI.storeDoerFeedback(taskId, {
      rating: selectedDoerRating.value,
      reviews: doerFeedbackReview.value || null
    });
    
    console.log('Doer feedback submission response:', response);
    
    if (response.success || response.feedback) {
      // Set the existing feedback to hide the form
      const feedback = response.feedback || {
        rating: selectedDoerRating.value,
        reviews: doerFeedbackReview.value || null
      };
      
      // Ensure feedback has required fields
      if (!feedback.rating) {
        feedback.rating = selectedDoerRating.value;
      }
      if (!feedback.reviews && doerFeedbackReview.value) {
        feedback.reviews = doerFeedbackReview.value;
      }
      
      // Set the feedback value
      existingDoerFeedback.value = feedback;
      console.log('Set existingDoerFeedback:', existingDoerFeedback.value);
      console.log('existingDoerFeedback.value is truthy?', !!existingDoerFeedback.value);
      
      // Clear form fields
      selectedDoerRating.value = null;
      hoverDoerRating.value = null;
      doerFeedbackReview.value = '';
      
      // Use nextTick to ensure Vue reactivity updates
      await nextTick();
      
      // Refetch feedback to ensure it's properly loaded from server
      await fetchFeedback();
      
      // Show success message for doer feedback
      doerFeedbackSuccess.value = true;
      setTimeout(() => {
        doerFeedbackSuccess.value = false;
      }, 5000);
    } else {
      alert(response.error || 'Failed to submit feedback');
    }
  } catch (error) {
    console.error('Error submitting feedback:', error);
    console.error('Error response:', error.response);
    alert(error.message || 'Failed to submit feedback');
  } finally {
    isSubmittingFeedback.value = false;
  }
};

const getStarStyle = (index, rating, isDisplay = false) => {
  const isFilled = rating && index <= rating;
  return {
    fontSize: isDisplay ? '20px' : '32px',
    color: isFilled ? '#f6c23e' : '#d1d3e2',
    transition: 'color 0.2s',
    cursor: isDisplay ? 'default' : 'pointer'
  };
};

// Function to load/reload task data
const loadTaskData = async (showSkeleton = true) => {
  try {
    // Only show skeleton on initial load, not on refresh
    if (showSkeleton) {
      isLoading.value = true;
    }
    const response = await tasksAPI.getById(taskId);
    task.value = response.task || response;
    
    // Attach userApplication to task if it exists in response
    if (response.userApplication) {
      task.value.userApplication = response.userApplication;
    }
    
    // Extract applications from task
    if (task.value && task.value.applications) {
      applications.value = Array.isArray(task.value.applications) ? task.value.applications : [];
    } else {
      applications.value = [];
    }
    
    // Set unread message count if available
    if (response.unreadMessageCount !== undefined) {
      unreadMessageCount.value = response.unreadMessageCount;
    }
    
    // Load messages if available
    if (response.messages && Array.isArray(response.messages)) {
      messages.value = response.messages;
    }
    
    // Fetch feedback if task is completed
    if (task.value && task.value.status === 'completed') {
      await fetchFeedback();
    } else {
      // Clear feedback if task is not completed
      existingPosterFeedback.value = null;
      existingDoerFeedback.value = null;
    }
  } catch (error) {
    console.error('Error fetching task:', error);
  } finally {
    if (showSkeleton) {
      isLoading.value = false;
    }
  }
};

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
  
  // Load task data
  await loadTaskData();
  
  // Set up real-time message listener
  if (taskId && $echo && user.value) {
    try {
      const channel = $echo.private(`task.${taskId}`);
      
      channel.error((error) => {
        console.error('Channel authorization error:', error);
      });
      
      channel.listen('.MessageSent', (data) => {
        // Add the new message to the messages array
        if (data && data.id) {
          // Check if message already exists to prevent duplicates
          const exists = messages.value.some(m => m.id === data.id);
          if (!exists) {
            messages.value.push({
              id: data.id,
              sender_id: data.sender_id,
              receiver_id: data.receiver_id,
              sender_name: data.sender_name,
              content: data.content,
              image_url: data.image_url,
              sent_at: data.sent_at
            });
            
            // Update unread count if the message is not from current user and chat is closed
            if (user.value && data.sender_id !== user.value.id && !showChatWindow.value) {
              unreadMessageCount.value++;
            }
            
            // Scroll to bottom if chat is open
            if (showChatWindow.value) {
              nextTick(() => {
                scrollChatToBottom();
              });
            }
          }
        }
      });
    } catch (error) {
      console.error('Error setting up message listener:', error);
    }
  }
  
  // Add window resize listener
  if (process.client) {
    window.addEventListener('resize', handleResize);
    handleResize();
  }
});

onUnmounted(() => {
  if (process.client) {
    window.removeEventListener('resize', handleResize);
  }
  
  // Clean up Echo listener
  if (taskId && $echo) {
    try {
      $echo.leave(`task.${taskId}`);
    } catch (error) {
      console.error('Error cleaning up message listener:', error);
    }
  }
});

const handleResize = () => {
  if (process.client) {
    windowWidth.value = window.innerWidth;
  }
};

// Watch for task status changes to fetch feedback
watch(() => task.value?.status, async (newStatus) => {
  if (newStatus === 'completed') {
    await fetchFeedback();
  } else {
    existingPosterFeedback.value = null;
    existingDoerFeedback.value = null;
  }
});

const handleAcceptApplication = (applicationId) => {
  pendingApplicationId.value = applicationId;
  showAcceptModalVisible.value = true;
};

const closeAcceptModal = () => {
  showAcceptModalVisible.value = false;
  pendingApplicationId.value = null;
};

const confirmAcceptApplication = async () => {
  if (!pendingApplicationId.value) return;
  
  try {
    isAccepting.value = true;
    await tasksAPI.acceptApplication(taskId, pendingApplicationId.value);
    closeAcceptModal();
    // Reload task data to show updated applications (no skeleton, no page reload)
    await loadTaskData(false);
  } catch (error) {
    console.error('Error accepting application:', error);
    alert('❌ ' + (error.message || 'Failed to accept application'));
  } finally {
    isAccepting.value = false;
  }
};

const formatAppliedAt = (appliedAt) => {
  if (!appliedAt) return 'Recently';
  const date = new Date(appliedAt);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  return date.toLocaleDateString();
};

const getApplicationStatusBadgeStyle = (status) => {
  const baseStyle = {
    padding: '4px 8px',
    borderRadius: '12px',
    fontSize: '12px',
    fontWeight: '600',
    display: 'inline-block',
    marginTop: '4px'
  };
  
  if (status === 'pending') {
    return {
      ...baseStyle,
      background: '#fff3cd',
      color: '#856404'
    };
  } else if (status === 'accepted') {
    return {
      ...baseStyle,
      background: '#d4edda',
      color: '#155724'
    };
  } else {
    return {
      ...baseStyle,
      background: '#f8d7da',
      color: '#721c24'
    };
  }
};

const showApplyModal = () => {
  showApplyModalVisible.value = true;
};

const closeApplyModal = () => {
  showApplyModalVisible.value = false;
};

const confirmApply = async () => {
  applyError.value = '';
  closeApplyModal();
  try {
    await tasksAPI.apply(taskId);
    // Show subtle success message above apply button
    applySuccess.value = 'Application submitted successfully!.';
    // Reload task data to show updated application status (no skeleton, no page reload)
    await loadTaskData(false);
    // Auto-clear message after 5 seconds
    setTimeout(() => {
      applySuccess.value = '';
    }, 5000);
  } catch (error) {
    applyError.value = error.message || 'Unknown error occurred';
  }
};

const showCompleteModal = () => {
  showCompleteModalVisible.value = true;
};

const closeCompleteModal = () => {
  showCompleteModalVisible.value = false;
};

const handleMarkAsCompleted = async () => {
  try {
    await tasksAPI.updateStatus(taskId, 'completed');
    closeCompleteModal();
    // Update task status locally
    if (task.value) {
      task.value.status = 'completed';
    }
    // Fetch feedback after marking as completed
    await fetchFeedback();
  } catch (error) {
    console.error('Error marking task as completed:', error);
    alert(error.message || 'Failed to mark task as completed');
  }
};


const showCancelModal = () => {
  showCancelModalVisible.value = true;
};

const closeCancelModal = () => {
  showCancelModalVisible.value = false;
};

const handleCancelTask = async () => {
  try {
    await tasksAPI.cancel(taskId);
    closeCancelModal();
    // Show success message
    success.value = 'Task cancelled successfully! All pending applications have been rejected.';
    // Reload task data to show updated status (no skeleton, no page reload)
    await loadTaskData(false);
    // Auto-clear message after 5 seconds
    setTimeout(() => {
      success.value = '';
    }, 5000);
    // Scroll to top to show the message
    if (process.client) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } catch (error) {
    console.error('Error cancelling task:', error);
    alert('❌ ' + (error.message || 'Failed to cancel task'));
  }
};

const formatPrice = (price) => {
  if (!price) return '0.00';
  const numPrice = typeof price === 'number' ? price : parseFloat(price || 0);
  return numPrice.toFixed(2);
};

const capitalize = (str) => {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
};

const statusBadgeStyle = (status) => ({
  padding: '6px 12px',
  borderRadius: '20px',
  fontSize: '13px',
  fontWeight: '600',
  background: 
    status === 'open' ? '#d4edda' :
    status === 'assigned' ? '#cce5ff' :
    status === 'in_progress' ? '#fff3cd' :
    status === 'completed' ? '#d1ecf1' : '#e3e6f0',
  color: 
    status === 'open' ? '#155724' :
    status === 'assigned' ? '#004085' :
    status === 'in_progress' ? '#856404' :
    status === 'completed' ? '#0c5460' : '#5a5c69'
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
  @media (max-width: 768px) {
    .main-content { 
      margin-left: 0; 
      width: 100%; 
      padding-top: 70px; 
    }
  }
  /* Chat Window Transitions */
  .chat-window-enter-active {
    transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.3s ease;
  }
  .chat-window-leave-active {
    transition: transform 0.3s cubic-bezier(0.55, 0.06, 0.68, 0.19), opacity 0.25s ease;
  }
  .chat-window-enter-from {
    opacity: 0;
    transform: translateY(100%);
  }
  .chat-window-leave-to {
    opacity: 0;
    transform: translateY(100%);
  }
  .chat-window-enter-to,
  .chat-window-leave-from {
    opacity: 1;
    transform: translateY(0);
  }
  
  /* Desktop specific transitions (for floating window) */
  @media (min-width: 769px) {
    .chat-window-enter-from {
      opacity: 0;
      transform: translateY(650px);
    }
    .chat-window-leave-to {
      opacity: 0;
      transform: translateY(650px);
    }
  }
  @keyframes skeleton-loading {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
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
  margin: '24px auto',
  padding: '0 16px'
};

const successStyle = {
  background: 'linear-gradient(135deg, #1cc88a 0%, #17a673 100%)',
  color: 'white',
  padding: '16px 24px',
  borderRadius: '12px',
  marginBottom: '24px',
  boxShadow: '0 4px 12px rgba(28, 200, 138, 0.2)',
  fontSize: '15px',
  fontWeight: '500',
  border: 'none'
};

const applySuccessStyle = computed(() => {
  const base = {
    background: 'rgba(28, 200, 138, 0.1)',
    color: '#1cc88a',
    padding: '10px 16px',
    borderRadius: '8px',
    marginBottom: '12px',
    fontSize: '14px',
    fontWeight: '500',
    border: '1px solid rgba(28, 200, 138, 0.3)',
    display: 'block',
    textAlign: 'center'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderRadius: '0',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const cardStyle = computed(() => {
  if (isMobile.value) {
    // No box on mobile - just padding and transparent background
    return {
      background: 'transparent',
      padding: '16px',
      borderRadius: '0',
      boxShadow: 'none',
      border: 'none'
    };
  }
  // Desktop - with box
  return {
    background: '#fff',
    padding: '32px',
    borderRadius: '12px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
    border: 'none'
  };
});

const taskTitleStyle = computed(() => {
  const base = {
    color: '#2e3a59',
    fontSize: isMobile.value ? '24px' : '32px',
    marginBottom: isMobile.value ? '16px' : '20px'
  };
  if (isMobile.value) {
    return {
      ...base,
      paddingBottom: '16px',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const taskMetaStyle = computed(() => {
  const base = {
    display: 'flex',
    flexDirection: 'column',
    gap: '12px',
    marginBottom: '24px',
    paddingBottom: '20px',
    borderBottom: '2px solid #f8f9fc'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderBottom: '1px solid #e3e6f0',
      gap: '10px'
    };
  }
  return base;
});

const metaRowStyle = {
  display: 'flex',
  alignItems: 'center',
  gap: '16px',
  flexWrap: 'wrap'
};

const metaItemStyle = {
  display: 'flex',
  alignItems: 'center',
  gap: '8px',
  color: '#5a5c69',
  fontSize: '14px'
};

const taskDescriptionStyle = computed(() => {
  const base = {
    color: '#5a5c69',
    lineHeight: '1.8',
    marginBottom: '10px',
    fontSize: '15px'
  };
  if (isMobile.value) {
    return {
      ...base,
      paddingBottom: '16px',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const buttonStyle = {
  display: 'inline-block',
  padding: '12px 24px',
  borderRadius: '8px',
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: '#fff',
  textDecoration: 'none',
  border: 'none',
  cursor: 'pointer',
  fontWeight: '600',
  fontSize: '14px',
  transition: 'transform 0.2s',
  marginRight: '12px',
  marginTop: '12px'
};

const applicationStatusButtonStyle = {
  ...buttonStyle,
  opacity: 0.6,
  cursor: 'not-allowed',
  background: '#e3e6f0',
  color: '#5a5c69'
};

const errorBoxStyle = computed(() => {
  const base = {
    background: '#f8d7da',
    color: '#721c24',
    padding: '12px 16px',
    borderRadius: '8px',
    marginBottom: '12px',
    border: '1px solid #f5c6cb',
    fontSize: '14px'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderRadius: '0',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const actionsStyle = computed(() => {
  const base = {
    marginTop: '24px',
    paddingTop: '24px',
    borderTop: '2px solid #f8f9fc'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderTop: '1px solid #e3e6f0'
    };
  }
  return base;
});

const posterLinkStyle = {
  color: '#4e73df',
  textDecoration: 'none',
  borderBottom: '1px dotted #4e73df'
};

const posterLinkHintStyle = {
  color: '#a6a7afff',
  fontSize: '9px',
  marginBottom: '5px',
  marginLeft: '65px',
  fontWeight: '100'
};

// Computed styles for merged objects

const draftInfoBoxStyle = computed(() => {
  const base = {
    background: '#fff3cd',
    color: '#856404',
    padding: '16px',
    borderRadius: '8px',
    marginBottom: '20px',
    borderLeft: '4px solid #f6c23e'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderRadius: '0',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const draftInfoBoxStrongStyle = {
  display: 'block',
  marginBottom: '8px'
};

const draftInfoBoxPStyle = {
  marginTop: '8px',
  marginBottom: '12px'
};

const draftEditButtonStyle = {
  display: 'inline-block',
  padding: '12px 24px',
  borderRadius: '8px',
  background: 'linear-gradient(135deg, #f6c23e 0%, #dda20a 100%)',
  color: '#fff',
  textDecoration: 'none',
  border: 'none',
  cursor: 'pointer',
  fontWeight: '600',
  fontSize: '14px',
  transition: 'transform 0.2s'
};

const posterInfoBoxStyle = computed(() => {
  const base = {
    background: '#e7f3ff',
    color: '#004085',
    padding: '16px',
    borderRadius: '8px',
    marginTop: '30px',
    borderLeft: '4px solid #4e73df'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderRadius: '0',
      marginTop: '16px',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const posterInfoBoxStrongStyle = {
  display: 'block',
  marginBottom: '8px'
};

const posterInfoBoxPStyle = {
  marginTop: '8px',
  marginBottom: '0'
};

const doerInfoStyle = computed(() => {
  const base = {
    background: '#d4edda',
    color: '#155724',
    padding: '16px',
    borderRadius: '8px',
    marginTop: '16px',
    marginBottom: '16px',
    borderLeft: '4px solid #28a745'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderRadius: '0',
      borderBottom: '1px solid #e3e6f0'
    };
  }
  return base;
});

const markCompleteButtonStyle = {
  ...buttonStyle,
  background: 'linear-gradient(135deg, #1cc88a 0%, #17a673 100%)',
  marginRight: '8px'
};

const cancelButtonStyle = {
  ...buttonStyle,
  background: 'linear-gradient(135deg, #e74a3b 0%, #be2617 100%)',
  marginRight: '8px'
};

const cancelConfirmButtonStyle = {
  ...buttonStyle,
  background: 'linear-gradient(135deg, #e74a3b 0%, #be2617 100%)',
  color: '#fff'
};

const chatButtonStyle = {
  ...buttonStyle,
  position: 'relative',
  marginLeft: '8px'
};

const doerChatButtonStyle = computed(() => {
  const base = {
    ...buttonStyle,
    position: 'relative',
    marginTop: '12px',
    display: 'block'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderBottom: '1px solid #e3e6f0',
      paddingBottom: '16px',
      marginBottom: '0'
    };
  }
  return base;
});

const messageBadgeStyle = {
  position: 'absolute',
  top: '-8px',
  right: '-8px',
  background: '#e74a3b',
  color: 'white',
  borderRadius: '10px',
  padding: '2px 6px',
  fontSize: '11px',
  fontWeight: 'bold',
  minWidth: '18px',
  textAlign: 'center'
};

const isMobile = computed(() => windowWidth.value <= 768);

const chatWindowStyle = computed(() => {
  if (isMobile.value) {
    return {
      position: 'fixed',
      top: '0',
      left: '0',
      right: '0',
      bottom: '0',
      width: '100%',
      height: '100%',
      zIndex: 2000,
      boxShadow: 'none'
    };
  }
  return {
    position: 'fixed',
    bottom: '20px',
    right: '20px',
    width: '400px',
    maxHeight: '600px',
    zIndex: 2000,
    boxShadow: '0 8px 32px rgba(0, 0, 0, 0.2)'
  };
});

const chatWindowCardStyle = computed(() => {
  if (isMobile.value) {
    return {
      background: '#fff',
      borderRadius: '0',
      display: 'flex',
      flexDirection: 'column',
      height: '100%',
      maxHeight: '100%'
    };
  }
  return {
    background: '#fff',
    borderRadius: '12px',
    display: 'flex',
    flexDirection: 'column',
    height: '100%',
    maxHeight: '600px'
  };
});

const chatWindowHeaderStyle = {
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: '#fff',
  padding: '16px 20px',
  borderRadius: '12px 12px 0 0',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'flex-start'
};

const chatWindowHeaderInfoStyle = {
  flex: 1
};

const chatWindowTitleStyle = {
  color: '#fff',
  fontWeight: '700',
  fontSize: '15px',
  marginBottom: '4px'
};

const chatWindowSubtitleStyle = {
  color: 'rgba(255,255,255,0.85)',
  fontSize: '11px'
};

const chatCloseButtonStyle = {
  background: 'transparent',
  border: 'none',
  color: '#fff',
  fontSize: '20px',
  cursor: 'pointer',
  padding: '0',
  width: '24px',
  height: '24px',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center'
};

const chatWindowBodyStyle = computed(() => ({
  flex: 1,
  overflowY: 'auto',
  padding: '16px',
  background: '#f8f9fc',
  maxHeight: isMobile.value ? 'calc(100vh - 200px)' : '400px',
  minHeight: isMobile.value ? 'calc(100vh - 200px)' : '200px'
}));

const messageStyle = {
  marginBottom: '16px',
  padding: '12px',
  borderRadius: '8px',
  maxWidth: '60%'
};

const messageMeStyle = {
  background: '#4e73df',
  color: '#fff',
  marginLeft: 'auto',
  textAlign: 'right'
};

const messageOtherStyle = {
  background: '#e3e6f0',
  color: '#2e3a59',
  marginRight: 'auto'
};

const messageHeaderStyle = {
  fontSize: '12px',
  opacity: 0.9,
  marginBottom: '4px'
};

const messageImageStyle = {
  marginTop: '8px',
  marginBottom: '8px'
};

const messageImageImgStyle = {
  maxWidth: '100%',
  borderRadius: '8px',
  cursor: 'pointer'
};

const messageContentStyle = {
  wordWrap: 'break-word'
};

const chatWindowFooterStyle = {
  display: 'flex',
  alignItems: 'flex-end',
  gap: '8px',
  padding: '12px',
  background: '#fff',
  borderTop: '1px solid #e3e6f0',
  borderRadius: '0 0 12px 12px'
};

const chatImageButtonStyle = {
  background: 'transparent',
  border: 'none',
  fontSize: '20px',
  cursor: 'pointer',
  padding: '8px',
  flexShrink: 0
};

const chatImagePreviewStyle = {
  position: 'relative',
  display: 'inline-block',
  marginRight: '8px'
};

const previewImgStyle = {
  width: '60px',
  height: '60px',
  objectFit: 'cover',
  borderRadius: '8px'
};

const chatRemoveImageStyle = {
  position: 'absolute',
  top: '-8px',
  right: '-8px',
  background: '#e74a3b',
  color: '#fff',
  border: 'none',
  borderRadius: '50%',
  width: '20px',
  height: '20px',
  cursor: 'pointer',
  fontSize: '12px',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center'
};

const chatInputStyle = {
  flex: 1,
  padding: '10px',
  border: '1px solid #d1d3e2',
  borderRadius: '8px',
  fontSize: '14px',
  fontFamily: 'inherit',
  resize: 'none',
  minHeight: '40px',
  maxHeight: '100px'
};

const sendButtonStyle = {
  padding: '10px 20px',
  background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)',
  color: '#fff',
  border: 'none',
  borderRadius: '8px',
  cursor: 'pointer',
  fontWeight: '600',
  fontSize: '14px',
  flexShrink: 0
};

const modalOverlayStyle = {
  position: 'fixed',
  top: 0,
  left: 0,
  width: '100%',
  height: '100%',
  background: 'rgba(0, 0, 0, 0.5)',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  zIndex: 3000
};

const modalContentStyle = {
  background: '#fff',
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
  color: '#2e3a59',
  fontSize: '18px',
  fontWeight: '600'
};

const modalCloseButtonStyle = {
  background: 'transparent',
  border: 'none',
  fontSize: '24px',
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
  padding: '24px'
};

const modalBodyPStyle = {
  margin: 0,
  color: '#5a5c69',
  fontSize: '16px'
};

const modalFooterStyle = {
  padding: '16px 24px',
  borderTop: '1px solid #e3e6f0',
  display: 'flex',
  justifyContent: 'flex-end',
  gap: '12px'
};

const modalCancelButtonStyle = {
  ...buttonStyle,
  background: '#858796',
  color: '#fff'
};

const modalConfirmButtonStyle = {
  ...buttonStyle,
  background: 'linear-gradient(135deg, #1cc88a 0%, #17a673 100%)',
  color: '#fff'
};

const applicationsSectionStyle = computed(() => {
  const base = {
    marginTop: '24px',
    paddingTop: '24px',
    borderTop: '2px solid #f8f9fc'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderTop: '1px solid #e3e6f0',
      borderBottom: '1px solid #e3e6f0',
      paddingBottom: '20px'
    };
  }
  return base;
});

const applicationsTitleStyle = {
  color: '#2e3a59',
  marginBottom: '16px',
  fontSize: '18px',
  fontWeight: '600'
};

const applicationItemStyle = {
  background: '#f8f9fc',
  padding: '16px',
  borderRadius: '8px',
  marginBottom: '12px',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center'
};

const applicantLinkStyle = {
  color: '#2e3a59',
  fontWeight: '600',
  textDecoration: 'none',
  fontSize: '16px',
  display: 'block'
};

const applicantLinkHintStyle = {
  color: '#a6a7afff',
  fontSize: '13px',
  marginBottom: '5px',
  marginLeft: '10px',
  fontWeight: 'normal'
};

const appliedAtStyle = {
  color: '#858796',
  fontSize: '13px',
  marginTop: '4px'
};

const acceptButtonStyle = {
  ...buttonStyle,
  margin: 0,
  padding: '8px 16px',
  fontSize: '14px'
};

// Feedback styles
const feedbackSuccessStyle = {
  background: '#d4edda',
  color: '#155724',
  padding: '12px 16px',
  borderRadius: '8px',
  marginBottom: '16px',
  border: '1px solid #c3e6cb',
  fontSize: '14px',
  fontWeight: '500'
};

const feedbackSectionStyle = computed(() => {
  const base = {
    marginTop: '24px',
    padding: '24px',
    borderRadius: '12px',
    borderLeft: '4px solid'
  };
  if (isMobile.value) {
    return {
      ...base,
      borderRadius: '0',
      borderBottom: '1px solid #e3e6f0',
      padding: '16px'
    };
  }
  return base;
});

const existingFeedbackStyle = computed(() => ({
  ...feedbackSectionStyle.value,
  background: '#e7f3ff',
  borderLeftColor: '#4e73df'
}));

const posterFeedbackFormStyle = computed(() => ({
  ...feedbackSectionStyle.value,
  background: '#fff3cd',
  borderLeftColor: '#f6c23e'
}));

const doerFeedbackFormStyle = computed(() => ({
  ...feedbackSectionStyle.value,
  background: '#d4edda',
  borderLeftColor: '#28a745'
}));

const feedbackTitleStyle = {
  color: '#2e3a59',
  marginBottom: '12px',
  fontSize: '18px',
  fontWeight: '600'
};

const posterFeedbackTitleStyle = {
  color: '#856404',
  marginBottom: '16px',
  fontSize: '18px',
  fontWeight: '600'
};

const doerFeedbackTitleStyle = {
  color: '#155724',
  marginBottom: '16px',
  fontSize: '18px',
  fontWeight: '600'
};

const doerFeedbackSubtitleStyle = {
  color: '#155724',
  marginBottom: '16px',
  fontSize: '14px'
};

const feedbackRatingStyle = {
  marginBottom: '8px',
  fontSize: '16px'
};

const feedbackReviewStyle = {
  marginTop: '8px',
  fontSize: '15px',
  lineHeight: '1.6'
};

const feedbackFormGroupStyle = {
  marginBottom: '16px'
};

const feedbackLabelStyle = {
  display: 'block',
  color: '#5a5c69',
  fontWeight: '600',
  marginBottom: '8px',
  fontSize: '14px'
};

const doerFeedbackLabelStyle = {
  display: 'block',
  color: '#155724',
  fontWeight: '600',
  marginBottom: '8px',
  fontSize: '14px'
};

const starsContainerStyle = {
  display: 'flex',
  gap: '8px'
};

const starLabelStyle = {
  cursor: 'pointer'
};

const feedbackTextareaStyle = {
  width: '100%',
  padding: '12px',
  border: '1px solid #d1d3e2',
  borderRadius: '8px',
  fontFamily: 'inherit',
  fontSize: '14px',
  resize: 'vertical'
};

const posterFeedbackSubmitStyle = {
  ...buttonStyle,
  background: 'linear-gradient(135deg, #f6c23e 0%, #dda20a 100%)',
  color: '#fff'
};

const doerFeedbackSubmitStyle = {
  ...buttonStyle,
  background: 'linear-gradient(135deg, #28a745 0%, #1e7e34 100%)',
};

// Skeleton Loading Styles
const skeletonBaseStyle = {
  background: 'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)',
  backgroundSize: '200px 100%',
  animation: 'skeleton-loading 1.5s ease-in-out infinite',
  borderRadius: '8px'
};

const skeletonTitleStyle = {
  ...skeletonBaseStyle,
  width: '70%',
  height: '36px',
  marginBottom: '20px'
};

const skeletonMetaStyle = {
  display: 'flex',
  flexWrap: 'wrap',
  gap: '16px',
  marginBottom: '24px'
};

const skeletonMetaItemStyle = {
  ...skeletonBaseStyle,
  width: '120px',
  height: '24px'
};

const skeletonDescriptionStyle = {
  ...skeletonBaseStyle,
  width: '100%',
  height: '16px',
  marginBottom: '12px'
};

const skeletonDescriptionStyleLast = {
  ...skeletonBaseStyle,
  width: '60%',
  height: '16px',
  marginBottom: '24px'
};
</script>

