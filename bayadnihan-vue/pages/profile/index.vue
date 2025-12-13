<template>
  <div>
    <div :style="containerStyle">
        <!-- Trial Status Banner (Only for 'both' role on trial) -->
        <div v-if="user && user.role === 'both' && isOnTrial" class="trial-banner" :style="trialBannerStyle">
          <div class="trial-banner-content" :style="trialBannerContentStyle">
            <div class="trial-icon" :style="trialIconStyle">⏰</div>
            <div class="trial-info" :style="trialInfoStyle">
              <h3 :style="trialH3Style">Your Free Trial is Active!</h3>
              <p>
                You have <strong>{{ trialDaysRemaining }} {{ trialDaysRemaining === 1 ? 'day' : 'days' }}</strong> remaining in your trial period.
                <span v-if="user.trial_ends_at">
                  Trial expires on <strong>{{ formatDate(user.trial_ends_at) }}</strong>.
                </span>
              </p>
              <p class="trial-warning" :style="trialWarningStyle">
                ⚠️ After your trial ends, your role will automatically change to <strong>Poster</strong> by default.
              </p>
            </div>
            <div class="trial-actions" :style="trialActionsStyle">
              <button class="btn-upgrade" @click="showSubscriptionModalFunc" :style="btnUpgradeStyle">
                ✨ Continue as Both - ₱99/month
              </button>
              <button class="btn-info" @click="toggleTrialInfo" :style="btnInfoStyle">
                ℹ️ Learn More
              </button>
            </div>
          </div>
          <div v-if="showTrialDetails" class="trial-details" id="trialDetails" :style="trialDetailsStyle">
            <div class="trial-details-content" :style="trialDetailsContentStyle">
              <h4 :style="trialDetailsH4Style">What happens after the trial?</h4>
              <ul :style="trialDetailsUlStyle">
                <li>✓ Your account will remain active</li>
                <li>✓ You'll automatically become a <strong>Poster</strong></li>
                <li>✓ You can still post tasks</li>
                <li>✗ You won't be able to apply to tasks as a Doer</li>
                <li>💡 You can upgrade anytime to keep both roles</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Left Panel: Profile View/Edit -->
        <div class="profile-container" :style="profileContainerStyle">
          <div class="card profile-card" :style="profileCardStyle">
            <!-- Loading State -->
            <div v-if="isLoading" :style="{ textAlign: 'center', padding: '24px' }">
              <!-- Profile Picture Skeleton -->
              <div :style="{ 
                width: '150px', 
                height: '150px', 
                borderRadius: '50%', 
                background: '#e3e6f0', 
                margin: '0 auto 20px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
              
              <!-- Rating Badge Skeleton (optional, shown as placeholder) -->
              <div :style="{ 
                width: '180px', 
                height: '32px', 
                background: '#e3e6f0', 
                margin: '0 auto 20px',
                borderRadius: '20px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
              
              <!-- Username Skeleton -->
              <div :style="{ 
                width: '180px', 
                height: '28px', 
                background: '#e3e6f0', 
                margin: '12px auto',
                borderRadius: '4px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
              
              <!-- Email Skeleton -->
              <div :style="{ 
                width: '200px', 
                height: '18px', 
                background: '#e3e6f0', 
                margin: '8px auto',
                borderRadius: '4px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
              
              <!-- Role Skeleton -->
              <div :style="{ 
                width: '100px', 
                height: '18px', 
                background: '#e3e6f0', 
                margin: '8px auto 20px',
                borderRadius: '4px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
              
              <!-- Edit Button Skeleton -->
              <div :style="{ 
                width: '100%', 
                height: '44px', 
                background: '#e3e6f0', 
                margin: '0 auto',
                borderRadius: '8px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
            </div>

            <!-- Profile Content -->
            <template v-else>
              <div v-if="success" class="alert success" :style="successStyle">{{ success }}</div>

              <div class="profile-pic-wrapper" :style="profilePicWrapperStyle">
                <img 
                  class="profile-pic" 
                  :src="profilePicUrl" 
                  :key="`profile-pic-${user.value?.profile_pic || 'default'}-${profilePicCacheKey}`"
                  alt="Profile"
                  :style="profilePicStyle"
                  @error="handleImageError"
                />
              </div>

              <div v-if="stats.avgRating" class="rating-badge" :style="ratingBadgeStyle">
                ⭐ {{ stats.avgRating }} / 5.0 ({{ stats.totalFeedbacks }} reviews)
              </div>

              <div v-if="user" class="profile-info" id="profile-view" :style="profileInfoStyle">
                <h2 :style="profileH2Style">{{ user.username }}</h2>
                <p :style="profilePStyle">{{ user.email }}</p>
                <p :style="profileRoleStyle">{{ capitalize(user.role) }}</p>

                <button type="button" class="btn" @click="toggleEdit(true)" :style="editButtonStyle">✏️ Edit Profile</button>
              </div>
            </template>

            <!-- Hidden Edit Form -->
            <form v-if="isEditing" id="edit-form" @submit.prevent="handleProfileUpdate" :style="editFormStyle">
              <!-- Only show username edit if current username starts with a letter -->
              <div v-if="canEditUsername" class="form-group" :style="formGroupStyle">
                <label :style="formLabelStyle">Username (Student ID) <span :style="optionalLabelStyle">(Optional)</span></label>
                <input 
                  type="text" 
                  v-model="editUsername" 
                  placeholder="000-00000 (e.g., 231-00123)"
                  pattern="\d{3}-\d{5}"
                  :style="fileInputStyle"
                />
                <small :style="formHelpStyle">Optional: Leave unchanged to keep current username. Format: 000-00000 (e.g., 231-00123)</small>
              </div>

              <!-- Only show email field if username can be edited (for verification) -->
              <div v-if="canEditUsername" class="form-group" :style="formGroupStyle">
                <label :style="formLabelStyle">Email (for verification)</label>
                <input 
                  type="email" 
                  v-model="editEmail" 
                  :style="readonlyInputStyle"
                  readonly
                />
                <small :style="formHelpStyle">Your email will be used to verify your student ID (cannot be changed)</small>
              </div>

              <div class="form-group" :style="formGroupStyle">
                <label :style="formLabelStyle">Profile Picture</label>
                <input type="file" @change="handleFileChange" accept="image/*" :style="fileInputStyle" />
                <small :style="formHelpStyle">Optional: Upload a new profile picture</small>
              </div>

              <div :style="formActionsStyle">
                <button type="submit" class="btn" :style="saveButtonStyle">💾 Save Changes</button>
                <button type="button" class="btn" @click="toggleEdit(false)" :style="cancelButtonStyle">❌ Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right Panel: Earnings & Tasks -->
        <div class="stats-container" :style="statsContainerStyle">
          <div class="card stats-card" :style="statsCardStyle">
          <!-- Loading State for Stats -->
          <div v-if="isLoading" :style="{ padding: '24px' }">
            <!-- Title Skeleton -->
            <div :style="{ 
              width: '200px', 
              height: '28px', 
              background: '#e3e6f0', 
              marginBottom: '20px',
              borderRadius: '4px',
              animation: 'pulse 1.5s ease-in-out infinite'
            }"></div>
            
            <!-- Stat Grid Skeleton (6 boxes for 'both' role, which is most common) -->
            <div :style="{ 
              display: 'grid', 
              gridTemplateColumns: 'repeat(2, 1fr)', 
              gap: '16px',
              marginBottom: '24px'
            }">
              <div v-for="i in 6" :key="i" :style="{ 
                width: '100%', 
                height: '100px', 
                background: '#e3e6f0', 
                borderRadius: '10px',
                padding: '20px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }">
                <div :style="{ 
                  width: '60%', 
                  height: '32px', 
                  background: 'rgba(255,255,255,0.5)', 
                  marginBottom: '8px',
                  borderRadius: '4px',
                  animation: 'pulse 1.5s ease-in-out infinite'
                }"></div>
                <div :style="{ 
                  width: '80%', 
                  height: '16px', 
                  background: 'rgba(255,255,255,0.5)', 
                  borderRadius: '4px',
                  animation: 'pulse 1.5s ease-in-out infinite'
                }"></div>
              </div>
            </div>
            
            <!-- Task Section Skeleton -->
            <div :style="{ marginTop: '24px' }">
              <div :style="{ 
                width: '180px', 
                height: '22px', 
                background: '#e3e6f0', 
                marginBottom: '12px',
                borderRadius: '4px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
              
              <!-- Task Stats Skeleton -->
              <div :style="{ 
                display: 'flex', 
                flexWrap: 'wrap', 
                gap: '12px',
                marginBottom: '16px'
              }">
                <div v-for="i in 5" :key="i" :style="{ 
                  width: '80px', 
                  height: '60px', 
                  background: '#e3e6f0', 
                  borderRadius: '8px',
                  animation: 'pulse 1.5s ease-in-out infinite'
                }"></div>
              </div>
              
              <!-- Task Cards Skeleton -->
              <div v-for="i in 3" :key="i" :style="{ 
                width: '100%', 
                height: '80px', 
                background: '#e3e6f0', 
                marginBottom: '8px',
                borderRadius: '8px',
                animation: 'pulse 1.5s ease-in-out infinite'
              }"></div>
            </div>
          </div>

          <!-- Stats Content -->
          <template v-else>
          <h3 :style="statsH3Style">📊 Your Statistics</h3>
          
          <div class="stat-grid" :style="statGridStyle">
            <template v-if="user && user.role === 'both'">
              <!-- Both Poster and Doer -->
              <div class="stat-box green" :style="statBoxGreenStyle">
                <div class="stat-value" :style="statValueStyle">₱{{ formatPrice(stats.totalEarnings) }}</div>
                <div class="stat-label" :style="statLabelStyle">Total Earnings</div>
              </div>
              <div class="stat-box red" :style="statBoxRedStyle">
                <div class="stat-value" :style="statValueStyle">₱{{ formatPrice(stats.totalSpend) }}</div>
                <div class="stat-label" :style="statLabelStyle">Total Spend</div>
              </div>
              <div class="stat-box" :style="statBoxStyle">
                <div class="stat-value" :style="statValueStyle">{{ stats.completedTasksCount }}</div>
                <div class="stat-label" :style="statLabelStyle">Tasks Completed</div>
              </div>
              <div class="stat-box" :style="statBoxGradientStyle">
                <div class="stat-value" :style="statValueStyle">{{ totalPostedTasks }}</div>
                <div class="stat-label" :style="statLabelStyle">Tasks Posted</div>
              </div>
              <div class="stat-box" :style="statBoxGreyStyle">
                <div class="stat-value" :style="statValueStyle">{{ stats.cancelledTasksCount || 0 }}</div>
                <div class="stat-label" :style="statLabelStyle">Cancelled Tasks</div>
              </div>
              <div class="stat-box orange" :style="statBoxOrangeStyle">
                <div class="stat-value" :style="statValueStyle">{{ activeTasksCount }}</div>
                <div class="stat-label" :style="statLabelStyle">Active Tasks</div>
              </div>
            </template>
            <template v-else-if="user && user.role === 'doer'">
              <!-- Doer Only -->
              <div class="stat-box green" :style="statBoxGreenStyle">
                <div class="stat-value" :style="statValueStyle">₱{{ formatPrice(stats.totalEarnings) }}</div>
                <div class="stat-label" :style="statLabelStyle">Total Earnings</div>
              </div>
              <div class="stat-box" :style="statBoxStyle">
                <div class="stat-value" :style="statValueStyle">{{ stats.completedTasksCount }}</div>
                <div class="stat-label" :style="statLabelStyle">Tasks Completed</div>
              </div>
              <div class="stat-box orange" :style="statBoxOrangeStyle">
                <div class="stat-value" :style="statValueStyle">{{ activeTasksCount }}</div>
                <div class="stat-label" :style="statLabelStyle">Active Tasks</div>
              </div>
            </template>
            <template v-else-if="user && user.role === 'poster'">
              <!-- Poster Only -->
              <div class="stat-box red" :style="statBoxRedStyle">
                <div class="stat-value" :style="statValueStyle">₱{{ formatPrice(stats.totalSpend) }}</div>
                <div class="stat-label" :style="statLabelStyle">Total Spend</div>
              </div>
              <div class="stat-box orange" :style="statBoxOrangeStyle">
                <div class="stat-value" :style="statValueStyle">{{ totalPostedTasks }}</div>
                <div class="stat-label" :style="statLabelStyle">Tasks Posted</div>
              </div>
              <div class="stat-box" :style="statBoxStyle">
                <div class="stat-value" :style="statValueStyle">{{ stats.postedTasks?.completed || 0 }}</div>
                <div class="stat-label" :style="statLabelStyle">Completed Tasks</div>
              </div>
              <div class="stat-box" :style="statBoxGreyStyle">
                <div class="stat-value" :style="statValueStyle">{{ stats.cancelledTasksCount || 0 }}</div>
                <div class="stat-label" :style="statLabelStyle">Cancelled Tasks</div>
              </div>
            </template>
            <template v-else>
              <!-- New User -->
              <div class="stat-box" :style="statBoxStyle">
                <div class="stat-value" :style="statValueStyle">0</div>
                <div class="stat-label" :style="statLabelStyle">Get Started!</div>
              </div>
            </template>
          </div>
          
          <template v-if="user && user.role !== 'doer'">
            <div class="task-section" :style="taskSectionStyle">
              <h4 :style="taskSectionH4Style">📝 Tasks You Posted</h4>
              <div class="task-stats" :style="taskStatsStyle">
                <div class="task-stat-item" :style="taskStatItemStyle">
                  <strong :style="taskStatStrongStyle">{{ stats.postedTasks?.open || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Open</span>
                </div>
                <div class="task-stat-item" :style="taskStatItemStyle">
                  <strong :style="taskStatStrongStyle">{{ stats.postedTasks?.assigned || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Assigned</span>
                </div>
                <div class="task-stat-item" :style="taskStatItemInProgressStyle">
                  <strong :style="taskStatStrongInProgressStyle">{{ stats.postedTasks?.in_progress || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Doing Task</span>
                </div>
                <div class="task-stat-item" :style="taskStatItemStyle">
                  <strong :style="taskStatStrongStyle">{{ stats.postedTasks?.completed || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Completed</span>
                </div>
                <div class="task-stat-item" :style="taskStatItemCancelledStyle">
                  <strong :style="taskStatStrongCancelledStyle">{{ stats.cancelledTasksCount || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Cancelled</span>
                </div>
              </div>
              
              <div :style="taskListWrapperStyle">
                <template v-if="displayedPostedTasks.length > 0">
                  <NuxtLink 
                    v-for="task in displayedPostedTasks" 
                    :key="task.id"
                    :to="`/tasks/${task.id}`"
                    :style="taskCardLinkStyle"
                  >
                    <div :style="getTaskCardStyle(task.status)">
                      <div :style="taskCardContentStyle">
                        <div :style="taskCardLeftStyle">
                          <div :style="taskCardTitleStyle">{{ task.title }}</div>
                          <div :style="taskCardMetaStyle">
                            Status: <span :style="getStatusColorStyle(task.status)">{{ capitalize(task.status.replace('_', ' ')) }}</span>
                            <span v-if="task.doer_username">
                              · Assigned to: <strong>{{ task.doer_username }}</strong>
                            </span>
                            <span v-if="['assigned', 'in_progress'].includes(task.status)" :style="clickToManageStyle">
                              👉 Click to manage
                            </span>
                          </div>
                          <div v-if="['assigned', 'in_progress'].includes(task.status)" :style="completionBarWrapperStyle">
                            <div style="display: flex; align-items: center; gap: 8px;">
                              <div :style="completionBarContainerStyle">
                                <div :style="getCompletionBarStyle(task.completion_percentage || 0)"></div>
                              </div>
                              <span :style="completionPercentageStyle">{{ task.completion_percentage || 0 }}%</span>
                            </div>
                          </div>
                          <div v-else-if="['assigned', 'in_progress'].includes(task.status) && task.completion_percentage === 0" :style="completionBarWrapperStyle">
                            <div :style="completionBarContainerStyle">
                              <div :style="getCompletionBarStyle(0)"></div>
                            </div>
                            <span :style="completionPercentageStyle">0%</span>
                          </div>
                        </div>
                        <div :style="taskCardPriceStyle">₱{{ formatPrice(task.price) }}</div>
                      </div>
                    </div>
                  </NuxtLink>
                </template>
                <p v-else :style="noTasksStyle">No tasks posted yet</p>
                <NuxtLink 
                  v-if="postedTasksList.length > 5"
                  to="/my-tasks"
                  :style="viewAllLinkStyle"
                >
                  View all {{ postedTasksList.length }} tasks →
                </NuxtLink>
              </div>
            </div>
          </template>
          
          <template v-if="user && user.role !== 'poster'">
            <div class="task-section" :style="taskSectionStyle">
              <h4 :style="taskSectionH4Style">✅ Tasks You Did</h4>
              <div class="task-stats" :style="taskStatsStyle">
                <div class="task-stat-item" :style="taskStatItemStyle">
                  <strong :style="taskStatStrongStyle">{{ stats.doneTasks?.assigned || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Assigned</span>
                </div>
                <div class="task-stat-item" :style="taskStatItemInProgressStyle">
                  <strong :style="taskStatStrongInProgressStyle">{{ stats.doneTasks?.in_progress || 0 }}</strong>
                  <span :style="taskStatSpanStyle">In Progress</span>
                </div>
                <div class="task-stat-item" :style="taskStatItemStyle">
                  <strong :style="taskStatStrongStyle">{{ stats.doneTasks?.completed || 0 }}</strong>
                  <span :style="taskStatSpanStyle">Completed</span>
                </div>
              </div>
              
              <div :style="taskListWrapperStyle">
                <template v-if="displayedDoneTasks.length > 0">
                  <div 
                    v-for="task in displayedDoneTasks" 
                    :key="task.id"
                    :style="taskCardDoneStyle"
                  >
                    <div :style="taskCardContentStyle">
                      <div :style="taskCardLeftStyle">
                        <NuxtLink :to="`/tasks/${task.id}`" :style="taskCardTitleLinkStyle">
                          <div :style="taskCardTitleStyle">{{ task.title }}</div>
                        </NuxtLink>
                        <div :style="taskCardMetaStyle">
                          Status: <span :style="getDoneTaskStatusColorStyle(task.status)">{{ capitalize(task.status.replace('_', ' ')) }}</span>
                          · Posted by: <strong>{{ task.poster_username }}</strong>
                        </div>
                        <div v-if="task.status === 'assigned'" :style="taskActionButtonsStyle">
                          <button 
                            @click.prevent="handleStartTask(task.id)"
                            :style="startTaskButtonStyle"
                          >
                            I'm doing the task right now
                          </button>
                        </div>
                        <div v-else-if="task.status === 'in_progress'" :style="taskActionButtonsStyle">
                          <button 
                            @click.prevent="openPauseModal(task.id)"
                            :style="pauseTaskButtonStyle"
                          >
                            ⏸️ Pause doing task
                          </button>
                          <button 
                            @click.prevent="openCompletionModal(task.id, task.completion_percentage || 0)"
                            :style="updateCompletionButtonStyle"
                          >
                            📊 Update Completion
                          </button>
                        </div>
                        <div v-if="['assigned', 'in_progress'].includes(task.status)" :style="taskActionButtonsStyle">
                          <button 
                            v-if="task.status === 'assigned'"
                            @click.prevent="openCompletionModal(task.id, task.completion_percentage || 0)"
                            :style="updateCompletionButtonStyle"
                          >
                            📊 Update Completion
                          </button>
                        </div>
                      </div>
                      <div :style="taskCardPriceDoneStyle">₱{{ formatPrice(task.price) }}</div>
                    </div>
                  </div>
                </template>
                <p v-else :style="noTasksStyle">No tasks done yet</p>
                <NuxtLink 
                  v-if="doneTasksList.length > 5"
                  to="/my-tasks"
                  :style="viewAllLinkDoneStyle"
                >
                  View all {{ doneTasksList.length }} tasks →
                </NuxtLink>
              </div>
            </div>
          </template>
          
          <!-- Feedback Section (as Doer) -->
          <div v-if="feedbacks.length > 0" class="task-section" :style="feedbackSectionStyle">
            <h4 :style="taskSectionH4Style">💬 Feedback from Posters (as Doer)</h4>
            <div :style="feedbackListStyle">
              <div 
                v-for="feedback in feedbacks" 
                :key="feedback.id"
                :style="feedbackCardDoerStyle"
              >
                <div :style="feedbackContentStyle">
                  <img 
                    :src="getFeedbackProfilePic(feedback.from_profile_pic, feedback.from_username, '4e73df')"
                    :alt="feedback.from_username"
                    :style="feedbackProfilePicStyle"
                  />
                  <div :style="feedbackInfoStyle">
                    <div :style="feedbackHeaderStyle">
                      <strong :style="feedbackUsernameStyle">{{ maskFeedbackUsername(feedback.from_username) }}</strong>
                      <div :style="feedbackRatingStyle">
                        <span v-for="i in 5" :key="i" :style="i <= feedback.rating ? starFilledStyle : starEmptyStyle">
                          {{ i <= feedback.rating ? '⭐' : '☆' }}
                        </span>
                        <span :style="feedbackRatingTextStyle">({{ feedback.rating }}/5)</span>
                      </div>
                    </div>
                    <div :style="feedbackTaskStyle">
                      Task: <strong :style="feedbackTaskStrongStyle">{{ feedback.task_title }}</strong>
                    </div>
                    <div v-if="feedback.reviews" :style="feedbackReviewStyle">
                      "{{ feedback.reviews }}"
                    </div>
                    <div :style="feedbackDateStyle">
                      {{ formatRelativeTime(feedback.created_at) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Feedback Section (as Poster) -->
          <div v-if="feedbacksAsPoster.length > 0" class="task-section" :style="feedbackSectionStyle">
            <h4 :style="taskSectionH4Style">⭐ Feedback from Doers (as Poster)</h4>
            <div :style="feedbackListStyle">
              <div 
                v-for="feedback in feedbacksAsPoster" 
                :key="feedback.id"
                :style="feedbackCardPosterStyle"
              >
                <div :style="feedbackContentStyle">
                  <img 
                    :src="getFeedbackProfilePic(feedback.from_profile_pic, feedback.from_username, '1cc88a')"
                    :alt="feedback.from_username"
                    :style="feedbackProfilePicStyle"
                  />
                  <div :style="feedbackInfoStyle">
                    <div :style="feedbackHeaderStyle">
                      <strong :style="feedbackUsernameStyle">{{ maskFeedbackUsername(feedback.from_username) }}</strong>
                      <div :style="feedbackRatingStyle">
                        <span v-for="i in 5" :key="i" :style="i <= feedback.rating ? starFilledStyle : starEmptyStyle">
                          {{ i <= feedback.rating ? '⭐' : '☆' }}
                        </span>
                        <span :style="feedbackRatingTextStyle">({{ feedback.rating }}/5)</span>
                      </div>
                    </div>
                    <div :style="feedbackTaskStyle">
                      Task: <strong :style="feedbackTaskStrongStyle">{{ feedback.task_title }}</strong>
                    </div>
                    <div v-if="feedback.reviews" :style="feedbackReviewStyle">
                      "{{ feedback.reviews }}"
                    </div>
                    <div :style="feedbackDateStyle">
                      {{ formatRelativeTime(feedback.created_at) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Subscription Modal -->
    <div 
      class="subscription-modal" 
      :class="{ show: showSubscriptionModal }"
      @click.self="closeSubscriptionModal"
      :style="subscriptionModalStyle"
    >
      <div class="subscription-modal-content" :style="subscriptionModalContentStyle">
        <div class="subscription-modal-header" :style="subscriptionModalHeaderStyle">
          <h2 :style="subscriptionModalH2Style">✨ Continue as Both Roles</h2>
          <button class="close-modal" @click="closeSubscriptionModal" :style="closeModalStyle">&times;</button>
        </div>
        <div class="subscription-modal-body" :style="subscriptionModalBodyStyle">
          <div class="pricing-highlight" :style="pricingHighlightStyle">
            <div class="price-display" :style="priceDisplayStyle">
              <span class="currency" :style="currencyStyle">₱</span>
              <span class="amount" :style="amountStyle">99</span>
              <span class="period" :style="periodStyle">/month</span>
            </div>
            <p class="pricing-subtitle" :style="pricingSubtitleStyle">Keep full access to both Poster and Doer roles</p>
          </div>
          
          <div class="features-list" :style="featuresListStyle">
            <h3 :style="featuresH3Style">What you'll get:</h3>
            <ul :style="featuresUlStyle">
              <li :style="featuresLiStyle"><span class="check-icon" :style="checkIconStyle">✓</span> Post unlimited tasks</li>
              <li :style="featuresLiStyle"><span class="check-icon" :style="checkIconStyle">✓</span> Apply to unlimited tasks</li>
              <li :style="featuresLiStyle"><span class="check-icon" :style="checkIconStyle">✓</span> Priority support</li>
              <li :style="featuresLiStyle"><span class="check-icon" :style="checkIconStyle">✓</span> No interruptions to your workflow</li>
              <li :style="featuresLiStyle"><span class="check-icon" :style="checkIconStyle">✓</span> Cancel anytime</li>
            </ul>
          </div>

          <div class="payment-info" :style="paymentInfoStyle">
            <p :style="paymentInfoPStyle"><strong>Payment Methods:</strong></p>
            <p :style="paymentInfoPStyle">Contact admin to process your subscription payment.</p>
            <p class="contact-info" :style="contactInfoStyle">📧 Email: bayadnihan@gmail.com</p>
          </div>
        </div>
        <div class="subscription-modal-footer" :style="subscriptionModalFooterStyle">
          <button class="btn-cancel" @click="closeSubscriptionModal" :style="btnCancelStyle">Maybe Later</button>
          <button class="btn-subscribe" @click="contactAdmin" :style="btnSubscribeStyle">Contact Admin</button>
        </div>
      </div>
    </div>

    <!-- Pause Task Modal -->
    <div 
      v-if="showPauseModal" 
      class="modal" 
      @click.self="closePauseModal"
      :style="modalStyle"
    >
      <div class="modal-content" :style="pauseModalContentStyle">
        <div class="modal-header" :style="modalHeaderStyle">
          <h3 :style="modalH3Style">⏸️ Pause Task</h3>
          <button class="modal-close" @click="closePauseModal" :style="modalCloseStyle">&times;</button>
        </div>
        <form @submit.prevent="handlePauseTask" :style="pauseFormStyle">
          <div class="modal-body" :style="modalBodyStyle">
            <p :style="modalBodyPStyle">Please provide a reason for pausing this task. The poster will be notified of your reason.</p>
            <div class="form-group" :style="formGroupStyle">
              <label for="pauseReason" :style="formLabelStyle">Reason for pausing *</label>
              <textarea 
                id="pauseReason"
                v-model="pauseReason"
                rows="4" 
                required 
                maxlength="500"
                placeholder="e.g., Need to attend to an emergency, waiting for materials, etc."
                :style="pauseTextareaStyle"
              ></textarea>
              <small :style="pauseTextareaSmallStyle">Maximum 500 characters</small>
            </div>
          </div>
          <div class="modal-footer" :style="modalFooterStyle">
            <button type="button" class="btn" @click="closePauseModal" :style="modalCancelButtonStyle">Cancel</button>
            <button type="submit" class="btn" :style="modalSubmitButtonStyle">Pause Task</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Completion Percentage Modal -->
    <div 
      v-if="showCompletionModal" 
      class="modal" 
      @click.self="closeCompletionModal"
      :style="modalStyle"
    >
      <div class="modal-content" :style="completionModalContentStyle">
        <div class="modal-header" :style="modalHeaderStyle">
          <h3 :style="modalH3Style">📊 Task Completion Percentage</h3>
          <button class="modal-close" @click="closeCompletionModal" :style="modalCloseStyle">&times;</button>
        </div>
        <form @submit.prevent="handleUpdateCompletion" :style="completionFormStyle">
          <div class="modal-body" :style="modalBodyStyle">
            <p :style="modalBodyPStyle">Drag the slider to set how much of the task is completed.</p>
            <div class="form-group" :style="formGroupStyle">
              <label for="completionPercentage" :style="formLabelStyle">
                Completion: <span id="completionValue" :style="completionValueStyle">{{ completionPercentage }}</span>%
              </label>
              <input 
                type="range" 
                id="completionPercentage"
                v-model.number="completionPercentage"
                min="0" 
                max="100" 
                step="1" 
                @input="updateCompletionPreview"
                :style="completionRangeStyle"
              />
              <div :style="completionRangeLabelsStyle">
                <span :style="completionRangeLabelStyle">0%</span>
                <span :style="completionRangeLabelStyle">50%</span>
                <span :style="completionRangeLabelStyle">100%</span>
              </div>
            </div>
            <div :style="completionPreviewWrapperStyle">
              <div :style="completionPreviewContainerStyle">
                <div :style="getCompletionPreviewBarStyle(completionPercentage)"></div>
              </div>
              <span :style="completionPreviewTextStyle">{{ completionPercentage }}%</span>
            </div>
          </div>
          <div class="modal-footer" :style="modalFooterStyle">
            <button type="button" class="btn" @click="closeCompletionModal" :style="modalCancelButtonStyle">Cancel</button>
            <button type="submit" class="btn" :style="completionSubmitButtonStyle">Update Completion</button>
          </div>
        </form>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUser } from '~/composables/useUser';
import { useAPI } from '~/utils/api';

const router = useRouter();
const { user: contextUser, updateUser, isAuthenticated, isLoading: userLoading } = useUser();
const { userAPI, tasksAPI } = useAPI();

const user = ref(null);
const isEditing = ref(false);
const selectedFile = ref(null);
const imagePreview = ref(null);
const success = ref('');
const isLoading = ref(true);
const editUsername = ref('');
const editEmail = ref('');
const profilePicCacheKey = ref(Date.now());
const showTrialDetails = ref(false);
const showSubscriptionModal = ref(false);
const showPauseModal = ref(false);
const showCompletionModal = ref(false);
const selectedTask = ref(null);
const completionPercentage = ref(0);
const pauseReason = ref('');

const stats = ref({
  totalEarnings: 0,
  totalSpend: 0,
  completedTasksCount: 0,
  postedTasks: { open: 0, assigned: 0, in_progress: 0, completed: 0 },
  cancelledTasksCount: 0,
  doneTasks: { assigned: 0, in_progress: 0, completed: 0 },
  avgRating: null,
  totalFeedbacks: 0
});

const postedTasksList = ref([]);
const doneTasksList = ref([]);
const feedbacks = ref([]);
const feedbacksAsPoster = ref([]);
const hasPostedTasks = ref(false);

const isOnTrial = computed(() => {
  if (!user.value || user.value.role !== 'both') return false;
  if (!user.value.trial_ends_at) return false;
  const trialEnd = new Date(user.value.trial_ends_at);
  return trialEnd > new Date();
});

const trialDaysRemaining = computed(() => {
  if (!isOnTrial.value || !user.value.trial_ends_at) return 0;
  const trialEnd = new Date(user.value.trial_ends_at);
  const now = new Date();
  const diff = trialEnd - now;
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
});

const totalPostedTasks = computed(() => {
  if (!stats.value.postedTasks) return 0;
  return (stats.value.postedTasks.open || 0) + 
         (stats.value.postedTasks.assigned || 0) + 
         (stats.value.postedTasks.in_progress || 0) + 
         (stats.value.postedTasks.completed || 0);
});

const activeTasksCount = computed(() => {
  return (stats.value.doneTasks?.assigned || 0) + (stats.value.doneTasks?.in_progress || 0);
});

const displayedPostedTasks = computed(() => {
  return postedTasksList.value.slice(0, 5);
});

const displayedDoneTasks = computed(() => {
  return doneTasksList.value.slice(0, 5);
});

const profilePicUrl = computed(() => {
  if (imagePreview.value) {
    return imagePreview.value;
  }
  if (user.value?.profile_pic) {
    // Add cache-busting parameter to force browser to reload the image
    // Use cache key that updates when profile is refreshed
    return `http://localhost:8000/api/storage/profile_pics/${user.value.profile_pic}?t=${profilePicCacheKey.value}`;
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value?.username || 'User')}&size=150&background=4e73df&color=fff`;
});

// Check if username can be edited (starts with a letter)
const canEditUsername = computed(() => {
  if (!user.value?.username) return false;
  const firstChar = user.value.username.charAt(0);
  // Check if first character is a letter (a-z or A-Z)
  return /^[a-zA-Z]/.test(firstChar);
});

const loadProfile = async (showSkeleton = true) => {
  try {
    // Only show skeleton on initial load, not on refresh
    if (showSkeleton) {
      isLoading.value = true;
    }
    const response = await userAPI.getProfile();
    const profileData = response.user || response;
    user.value = profileData;
    
    // Update cache key to force image refresh
    profilePicCacheKey.value = Date.now();
    
    // Update user in composable to sync with localStorage
    updateUser(user.value);
    
    stats.value = {
      totalEarnings: response.totalEarnings || 0,
      totalSpend: response.totalSpend || 0,
      completedTasksCount: response.completedTasksCount || 0,
      postedTasks: response.postedTasks || { open: 0, assigned: 0, in_progress: 0, completed: 0 },
      cancelledTasksCount: response.cancelledTasksCount || 0,
      doneTasks: response.doneTasks || { assigned: 0, in_progress: 0, completed: 0 },
      avgRating: response.avgRating || null,
      totalFeedbacks: response.totalFeedbacks || 0
    };
    
    if (response.postedTasksList) {
      postedTasksList.value = response.postedTasksList;
    }
    if (response.doneTasksList) {
      doneTasksList.value = response.doneTasksList;
    }
    // Handle feedbacks - Laravel paginator returns data in 'data' property
    if (response.feedbacks) {
      if (response.feedbacks.data && Array.isArray(response.feedbacks.data)) {
        feedbacks.value = response.feedbacks.data;
      } else if (Array.isArray(response.feedbacks)) {
        feedbacks.value = response.feedbacks;
      } else {
        feedbacks.value = [];
      }
    } else {
      feedbacks.value = [];
    }
    
    // Handle feedbacks as poster - always ensure it's an array
    feedbacksAsPoster.value = Array.isArray(response.feedbacks_as_poster) 
      ? response.feedbacks_as_poster 
      : [];
    
    hasPostedTasks.value = response.has_posted_tasks || false;
  } catch (error) {
    console.error('Error fetching profile:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleImageError = (event) => {
  console.error('❌ Profile picture failed to load:', event.target?.src);
  console.warn('💡 Solution: Please re-upload your profile picture. The file will be saved to the correct location.');
  
  // Fallback to default avatar
  setTimeout(() => {
    if (event.target) {
      event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value?.username || 'User')}&size=150&background=4e73df&color=fff`;
    }
  }, 1000);
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
  
  await loadProfile();
});

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    selectedFile.value = file;
    const reader = new FileReader();
    reader.onloadend = () => {
      imagePreview.value = reader.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleProfileUpdate = async () => {
  try {
    let profileUpdated = false;
    let usernameUpdated = false;
    
    // Only check for username change if username editing is allowed
    const hasUsernameChange = canEditUsername.value && editUsername.value && editUsername.value.trim() !== '' && editUsername.value !== user.value?.username;
    const hasProfilePicChange = selectedFile.value !== null;

    // Check if there are any changes
    if (!hasUsernameChange && !hasProfilePicChange) {
      success.value = 'No changes to save.';
      setTimeout(() => {
        success.value = '';
      }, 2000);
      return;
    }

    // Update username if it has changed and is provided (and editing is allowed)
    if (hasUsernameChange && canEditUsername.value) {
      try {
        // Validate username format if provided
        const usernamePattern = /^\d{3}-\d{5}$/;
        if (!usernamePattern.test(editUsername.value)) {
          success.value = 'Error: Username must be in format 000-00000 (e.g., 231-00123)';
          return;
        }

        const usernameResponse = await userAPI.updateUsername(editUsername.value, editEmail.value);
        if (usernameResponse.success) {
          usernameUpdated = true;
          user.value.username = usernameResponse.new_username || editUsername.value;
          updateUser(user.value);
        }
      } catch (error) {
        success.value = 'Error updating username: ' + (error.message || 'Failed to update username');
        return;
      }
    }

    // Update profile picture if a new file was selected
    if (hasProfilePicChange) {
      try {
        const profileResponse = await userAPI.updateProfile({ profile_pic: selectedFile.value });
        
        if (profileResponse.success || profileResponse.user) {
          profileUpdated = true;
          
          // Update user immediately from response if available
          if (profileResponse.user) {
            user.value = { ...user.value, ...profileResponse.user };
            updateUser(user.value);
            
            // Update cache key to force image refresh
            profilePicCacheKey.value = Date.now();
          }
          
          // Re-fetch the profile to ensure we have the latest data from the server
          // Add a small delay to ensure file is fully saved on server
          await new Promise(resolve => setTimeout(resolve, 500));
          await loadProfile();
          
          // Clear preview after profile is loaded to use server URL
          imagePreview.value = null;
        }
      } catch (error) {
        console.error('Error updating profile picture:', error);
        success.value = 'Error updating profile picture: ' + (error.message || 'Failed to update profile picture');
        return;
      }
    }

    // Show success message
    if (usernameUpdated && profileUpdated) {
      success.value = 'Profile updated successfully!';
    } else if (usernameUpdated) {
      success.value = 'Username updated successfully!';
    } else if (profileUpdated) {
      success.value = 'Profile picture updated successfully!';
    }

    isEditing.value = false;
    selectedFile.value = null;
    setTimeout(() => {
      success.value = '';
    }, 3000);
  } catch (error) {
    success.value = 'Error: ' + (error.message || 'Failed to update profile');
  }
};

const handleStartTask = async (taskId) => {
  try {
    await tasksAPI.startTask(taskId);
    // Show success message
    success.value = 'You have started the task! The poster has been notified.';
    // Refresh profile data without skeleton (no page reload)
    await loadProfile(false);
    // Auto-clear message after 5 seconds
    setTimeout(() => {
      success.value = '';
    }, 5000);
    // Scroll to top to show the message
    if (process.client) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } catch (error) {
    console.error('Error starting task:', error);
    success.value = ' Error starting task: ' + (error.message || 'Failed to start task');
  }
};

const handlePauseTask = async () => {
  if (!pauseReason.value.trim()) return;
  
  try {
    await tasksAPI.pauseTask(selectedTask.value, pauseReason.value);
    showPauseModal.value = false;
    pauseReason.value = '';
    selectedTask.value = null;
    // Show success message
    success.value = 'You have paused the task! The poster has been notified.';
    // Refresh profile data without skeleton (no page reload)
    await loadProfile(false);
    // Auto-clear message after 5 seconds
    setTimeout(() => {
      success.value = '';
    }, 5000);
    // Scroll to top to show the message
    if (process.client) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } catch (error) {
    console.error('Error pausing task:', error);
    success.value = ' Error pausing task: ' + (error.message || 'Failed to pause task');
  }
};

const handleUpdateCompletion = async () => {
  try {
    const percentage = completionPercentage.value;
    await userAPI.updateTaskCompletion(selectedTask.value, percentage);
    showCompletionModal.value = false;
    completionPercentage.value = 0;
    selectedTask.value = null;
    // Show success message
    success.value = `Task completion updated to ${percentage}%! The poster has been notified.`;
    // Refresh profile data without skeleton (no page reload)
    await loadProfile(false);
    // Auto-clear message after 5 seconds
    setTimeout(() => {
      success.value = '';
    }, 5000);
    // Scroll to top to show the message
    if (process.client) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } catch (error) {
    console.error('Error updating completion:', error);
    success.value = ' Error updating completion: ' + (error.message || 'Failed to update completion');
  }
};

const openPauseModal = (taskId) => {
  selectedTask.value = taskId;
  showPauseModal.value = true;
};

const openCompletionModal = (taskId, currentPercentage = 0) => {
  selectedTask.value = taskId;
  completionPercentage.value = currentPercentage;
  showCompletionModal.value = true;
};

const updateCompletionPreview = () => {
  // This is handled by v-model
};

const toggleTrialInfo = () => {
  showTrialDetails.value = !showTrialDetails.value;
};

const showSubscriptionModalFunc = () => {
  showSubscriptionModal.value = true;
};

const closeSubscriptionModal = () => {
  showSubscriptionModal.value = false;
};

const contactAdmin = () => {
  window.location.href = 'mailto:bayadnihan@gmail.com?subject=Subscription Request - Both Roles&body=Hi, I would like to subscribe to continue using both Poster and Doer roles.';
};

const closePauseModal = () => {
  showPauseModal.value = false;
  pauseReason.value = '';
  selectedTask.value = null;
};

const closeCompletionModal = () => {
  showCompletionModal.value = false;
  completionPercentage.value = 0;
  selectedTask.value = null;
};

const toggleEdit = (show) => {
  isEditing.value = show;
  if (show) {
    // Initialize edit fields with current user data
    editUsername.value = user.value?.username || '';
    editEmail.value = user.value?.email || '';
  } else {
    selectedFile.value = null;
    imagePreview.value = null;
    editUsername.value = '';
    editEmail.value = '';
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

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatRelativeTime = (dateString) => {
  if (!dateString) return '';
  
  // Parse the date string
  const date = new Date(dateString);
  
  // Check if date is valid
  if (isNaN(date.getTime())) {
    return '';
  }
  
  const now = new Date();
  let diff = now - date;
  
  // Handle timezone offset issues - if date appears to be in the future or very recent,
  // it might be a timezone mismatch. Check if the absolute difference is small (within 12 hours)
  const absDiff = Math.abs(diff);
  
  // If the date appears to be in the future (negative diff) or very recent with timezone offset
  // and the absolute difference is within 12 hours, treat as "Just now"
  if (diff < 0 && absDiff < 12 * 60 * 60 * 1000) {
    return 'Just now';
  }
  
  // For positive differences (past dates), check for timezone offset patterns
  // Common timezone offsets: 8 hours (UTC+8), so if difference is 7-9 hours and very recent, 
  // it's likely a timezone issue
  if (diff > 0) {
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    
    // If less than 1 minute, show "Just now"
    if (seconds < 60) {
      return 'Just now';
    }
    
    // Handle timezone offset: if it's showing 7-9 hours ago (common UTC+8 offset),
    // it's likely a timezone mismatch for a recently created feedback - show "Just now"
    if (hours >= 7 && hours <= 9 && minutes < 30) {
      // Check if the feedback was created today by comparing date components
      const feedbackDate = new Date(dateString);
      const today = new Date();
      const isToday = feedbackDate.getDate() === today.getDate() &&
                     feedbackDate.getMonth() === today.getMonth() &&
                     feedbackDate.getFullYear() === today.getFullYear();
      
      // If created today and showing 7-9 hours difference, it's a timezone issue - show "Just now"
      if (isToday) {
        return 'Just now';
      }
    }
    
    const days = Math.floor(hours / 24);
    if (days > 0) return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    if (hours > 0) return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    if (minutes > 0) return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
  }
  
  return 'Just now';
};

const maskFeedbackUsername = (username) => {
  if (!username) return '';
  if ((/^\d+$/.test(username) || /^[\d\-]+$/.test(username)) && username.length > 5) {
    return username.substring(0, 5) + '***';
  }
  return username;
};

const getFeedbackProfilePic = (profilePic, username, bgColor) => {
  if (profilePic) {
    return `http://localhost:8000/storage/profile_pics/${profilePic}`;
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(username || 'User')}&size=40&background=${bgColor}&color=fff`;
};

const getStatusColorStyle = (status) => {
  const colors = {
    open: '#4e73df',
    assigned: '#f6c23e',
    in_progress: '#ff9800',
    completed: '#1cc88a',
    cancelled: '#ae0925ff'
  };
  return {
    color: colors[status] || '#858796',
    fontWeight: '600'
  };
};

const getTaskCardStyle = (status) => {
  const borderColors = {
    open: '#4e73df',        // Blue for open tasks
    assigned: '#ff9800',    // Orange for assigned tasks
    in_progress: '#ff9800', // Orange for in_progress tasks
    completed: '#1cc88a',   // Green for completed tasks
    cancelled: '#ae0925ff'  // Red for cancelled tasks
  };
  return {
    background: '#fff',
    padding: '12px',
    borderRadius: '8px',
    marginBottom: '8px',
    borderLeft: `3px solid ${borderColors[status] || '#4e73df'}`,
    transition: 'transform 0.2s, box-shadow 0.2s',
    cursor: 'pointer'
  };
};

const getDoneTaskStatusColorStyle = (status) => {
  const colors = {
    assigned: '#f6c23e',
    in_progress: '#ff9800',
    completed: '#1cc88a'
  };
  return {
    color: colors[status] || '#858796',
    fontWeight: '600'
  };
};

const getCompletionBarStyle = (percentage) => {
  return {
    background: 'linear-gradient(90deg, #1cc88a 0%, #17a673 100%)',
    height: '100%',
    width: `${percentage || 0}%`,
    transition: 'width 0.3s ease',
    borderRadius: '6px'
  };
};

const getCompletionPreviewBarStyle = (percentage) => {
  return {
    background: 'linear-gradient(90deg, #1cc88a 0%, #17a673 100%)',
    height: '100%',
    width: `${percentage || 0}%`,
    transition: 'width 0.2s ease',
    borderRadius: '10px'
  };
};


const layoutStyles = `
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background: #f8f9fc;
  }
  .card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 24px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }
  .profile-card {
    text-align: center;
    height: fit-content;
  }
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.5;
    }
  }
  @media (max-width: 768px) {
    .profile-container {
      width: 100% !important;
      max-width: 100% !important;
    }
    .stats-container {
      width: 100% !important;
    }
    .stat-grid {
      grid-template-columns: 1fr !important;
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

// All style objects would go here  include key ones
// Use a ref to track window width for reactive updates
const windowWidth = ref(1920); // Default to desktop width
const isMounted = ref(false);

if (process.client) {
  onMounted(() => {
    isMounted.value = true;
    // Update to actual window width after mount
    windowWidth.value = window.innerWidth;
    
    const updateWidth = () => {
      windowWidth.value = window.innerWidth;
    };
    window.addEventListener('resize', updateWidth);
    onUnmounted(() => {
      window.removeEventListener('resize', updateWidth);
    });
  });
}

const containerStyle = computed(() => {
  // During SSR and initial render, use consistent default (desktop layout)
  // Only apply responsive changes after mount to avoid hydration mismatch
  const gridCols = (!isMounted.value || windowWidth.value > 768) ? '280px 1fr' : '1fr';
  
  const baseStyle = {
    maxWidth: '1000px', 
    margin: '0 auto', 
    marginLeft: 'auto',
    padding: '30px 16px', 
    display: 'grid', 
    gridTemplateColumns: gridCols, 
    gap: '24px', 
    alignItems: 'start',
    width: '100%',
    boxSizing: 'border-box'
  };
  
  return baseStyle;
});

const profileContainerStyle = computed(() => {
  // On mobile, make it full width; on desktop, fixed width
  if (!isMounted.value || windowWidth.value > 768) {
    return { width: '300px', maxWidth: '100%' };
  }
  return { width: '100%', maxWidth: '100%' };
});

const statsContainerStyle = computed(() => {
  // Always full width, but ensure proper spacing on mobile
  return { width: '100%' };
});
const profileCardStyle = { 
  textAlign: 'center', 
  height: 'fit-content',
  boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)',
  borderRadius: '12px',
  padding: '24px',
  background: '#fff'
};
const statsCardStyle = { 
  boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)',
  borderRadius: '12px',
  padding: '24px',
  background: '#fff'
};
const profilePicWrapperStyle = { position: 'relative', width: '150px', height: '150px', margin: '0 auto 20px' };
const profilePicStyle = { width: '150px', height: '150px', borderRadius: '50%', objectFit: 'cover' };
const ratingBadgeStyle = { background: '#1cc88a', color: 'white', padding: '6px 12px', borderRadius: '20px', fontSize: '14px', fontWeight: 'bold', display: 'inline-block', margin: '12px 0' };
const profileInfoStyle = { margin: '20px 0', textAlign: 'left' };
const profileH2Style = { color: '#2e3a59', marginBottom: '8px', fontSize: '24px' };
const profilePStyle = { color: '#858796', margin: '4px 0' };
const profileRoleStyle = { color: '#4e73df', fontWeight: '600' };
const editButtonStyle = { width: '100%', padding: '12px', background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', color: '#fff', border: 'none', borderRadius: '8px', fontSize: '16px', fontWeight: '600', cursor: 'pointer', transition: 'transform 0.2s' };
const editFormStyle = { };
const formGroupStyle = { margin: '16px 0', textAlign: 'left' };
const formLabelStyle = { display: 'block', color: '#5a5c69', fontWeight: '600', marginBottom: '8px', fontSize: '14px' };
const optionalLabelStyle = { color: '#858796', fontWeight: '400', fontSize: '12px' };
const fileInputStyle = { width: '100%', padding: '12px', border: '1px solid #d1d3e2', borderRadius: '8px', fontSize: '14px' };
const readonlyInputStyle = { width: '100%', padding: '12px', border: '1px solid #d1d3e2', borderRadius: '8px', fontSize: '14px', backgroundColor: '#f8f9fc', color: '#858796', cursor: 'not-allowed' };
const formHelpStyle = { display: 'block', color: '#858796', fontSize: '12px', marginTop: '4px' };
const formActionsStyle = { display: 'flex', gap: '10px' };
const saveButtonStyle = { flex: 1, padding: '12px', background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', color: '#fff', border: 'none', borderRadius: '8px', fontSize: '16px', fontWeight: '600', cursor: 'pointer' };
const cancelButtonStyle = { flex: 1, padding: '12px', background: '#858796', color: '#fff', border: 'none', borderRadius: '8px', fontSize: '16px', fontWeight: '600', cursor: 'pointer' };
const successStyle = { background: '#d4edda', color: '#155724', padding: '12px 16px', borderRadius: '8px', marginBottom: '16px', border: '1px solid #c3e6cb', fontSize: '14px' };
const statsH3Style = { color: '#2e3a59', marginBottom: '20px', fontSize: '22px' };
const statGridStyle = computed(() => {
  // Stack on mobile (single column), 2 columns on desktop
  const columns = (!isMounted.value || windowWidth.value > 768) ? 'repeat(2, 1fr)' : '1fr';
  return { 
    display: 'grid', 
    gridTemplateColumns: columns, 
    gap: '16px', 
    marginBottom: '24px' 
  };
});
const statBoxStyle = { background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', padding: '20px', borderRadius: '10px', color: 'white', textAlign: 'center' };
const statBoxGreenStyle = { ...statBoxStyle, background: 'linear-gradient(135deg, #1cc88a 0%, #13855c 100%)' };
const statBoxOrangeStyle = { ...statBoxStyle, background: 'linear-gradient(135deg, #f6c23e 0%, #dda20a 100%)' };
const statBoxRedStyle = { ...statBoxStyle, background: 'linear-gradient(135deg, #e74a3b 0%, #be2617 100%)' };
const statBoxGradientStyle = { ...statBoxStyle, background: 'linear-gradient(135deg, rgb(146, 21, 21) 0%, rgb(55, 55, 99) 100%)' };
const statBoxGreyStyle = { ...statBoxStyle, background: 'linear-gradient(135deg, #858796 0%, #60616f 100%)' };
const statValueStyle = { fontSize: '32px', fontWeight: 'bold', marginBottom: '4px' };
const statLabelStyle = { fontSize: '14px', opacity: 0.9 };
const taskSectionStyle = { marginTop: '24px' };
const taskSectionH4Style = { color: '#2e3a59', marginBottom: '12px', fontSize: '18px' };
const taskStatsStyle = { display: 'flex', flexWrap: 'wrap', gap: '12px' };
const taskStatItemStyle = { background: '#f8f9fc', padding: '12px 16px', borderRadius: '8px', borderLeft: '4px solid #4e73df' };
const taskStatItemInProgressStyle = { ...taskStatItemStyle, borderLeftColor: '#ff9800' };
const taskStatItemCancelledStyle = { ...taskStatItemStyle, borderLeftColor: '#e74a3b' };
const taskStatStrongStyle = { color: '#2e3a59', display: 'block', fontSize: '20px' };
const taskStatStrongInProgressStyle = { ...taskStatStrongStyle, color: '#ff9800' };
const taskStatStrongCancelledStyle = { ...taskStatStrongStyle, color: '#e74a3b' };
const taskStatSpanStyle = { color: '#858796', fontSize: '13px' };
const taskListWrapperStyle = { marginTop: '16px' };
const taskCardLinkStyle = { textDecoration: 'none', display: 'block' };
const taskCardDoneStyle = { background: '#fff', padding: '12px', borderRadius: '8px', marginBottom: '8px', borderLeft: '3px solid #1cc88a', transition: 'transform 0.2s, box-shadow 0.2s' };
const taskCardContentStyle = { display: 'flex', justifyContent: 'space-between', alignItems: 'start' };
const taskCardLeftStyle = { flex: 1 };
const taskCardTitleStyle = { color: '#2e3a59', fontWeight: '600', fontSize: '15px' };
const taskCardTitleLinkStyle = { textDecoration: 'none' };
const taskCardMetaStyle = { color: '#858796', fontSize: '13px', marginTop: '4px' };
const clickToManageStyle = { color: '#f6c23e', fontWeight: '600', marginLeft: '8px' };
const completionBarWrapperStyle = { marginTop: '8px' };
const completionBarContainerStyle = { 
  width: '30%',
  background: '#e3e6f0',
  borderRadius: '6px',
  height: '10px',
  overflow: 'hidden',
  position: 'relative'
};
const completionPercentageStyle = { color: '#1cc88a', fontWeight: '600', fontSize: '13px', minWidth: '45px' };
const taskCardPriceStyle = { color: '#1cc88a', fontWeight: '700', fontSize: '16px' };
const taskCardPriceDoneStyle = { color: '#1cc88a', fontWeight: '700', fontSize: '16px' };
const noTasksStyle = { color: '#858796', fontSize: '14px', textAlign: 'center', padding: '20px' };
const viewAllLinkStyle = { display: 'block', textAlign: 'center', color: '#4e73df', textDecoration: 'none', fontWeight: '600', marginTop: '12px' };
const viewAllLinkDoneStyle = { display: 'block', textAlign: 'center', color: '#1cc88a', textDecoration: 'none', fontWeight: '600', marginTop: '12px' };
const taskActionButtonsStyle = { display: 'inline-block', marginTop: '8px', marginLeft: '8px' };
const startTaskButtonStyle = { background: 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)', padding: '6px 12px', fontSize: '13px', color: 'white', border: 'none', borderRadius: '8px', cursor: 'pointer', fontWeight: '600' };
const pauseTaskButtonStyle = { background: 'linear-gradient(135deg, #858796 0%, #5a5c69 100%)', padding: '6px 12px', fontSize: '13px', color: 'white', border: 'none', borderRadius: '8px', cursor: 'pointer', fontWeight: '600', marginRight: '8px' };
const updateCompletionButtonStyle = { background: 'linear-gradient(135deg, #1cc88a 0%, #17a673 100%)', padding: '6px 12px', fontSize: '13px', color: 'white', border: 'none', borderRadius: '8px', cursor: 'pointer', fontWeight: '600' };
const feedbackSectionStyle = { marginTop: '32px' };
const feedbackListStyle = { marginTop: '16px' };
const feedbackCardDoerStyle = { background: '#fff', padding: '16px', borderRadius: '8px', marginBottom: '12px', borderLeft: '4px solid #1cc88a' };
const feedbackCardPosterStyle = { background: '#fff', padding: '16px', borderRadius: '8px', marginBottom: '12px', borderLeft: '4px solid #4e73df' };
const feedbackContentStyle = { display: 'flex', alignItems: 'start', gap: '12px', marginBottom: '12px' };
const feedbackProfilePicStyle = { width: '40px', height: '40px', borderRadius: '50%', objectFit: 'cover' };
const feedbackInfoStyle = { flex: 1 };
const feedbackHeaderStyle = { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '4px' };
const feedbackUsernameStyle = { color: '#2e3a59', fontSize: '15px' };
const feedbackRatingStyle = { color: '#f6c23e', fontSize: '14px', fontWeight: '600' };
const feedbackRatingTextStyle = { color: '#858796', marginLeft: '4px' };
const starFilledStyle = { };
const starEmptyStyle = { };
const feedbackTaskStyle = { color: '#858796', fontSize: '13px', marginBottom: '8px' };
const feedbackTaskStrongStyle = { color: '#4e73df' };
const feedbackReviewStyle = { color: '#5a5c69', fontSize: '14px', lineHeight: '1.6', background: '#f8f9fc', padding: '12px', borderRadius: '6px', marginTop: '8px' };
const feedbackDateStyle = { color: '#858796', fontSize: '12px', marginTop: '8px' };
const trialBannerStyle = { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', borderRadius: '16px', padding: '24px', marginBottom: '24px', color: 'white', boxShadow: '0 10px 30px rgba(102, 126, 234, 0.3)', gridColumn: '1 / -1' };
const trialBannerContentStyle = { display: 'flex', alignItems: 'center', gap: '20px', flexWrap: 'wrap' };
const trialIconStyle = { fontSize: '3rem' };
const trialInfoStyle = { flex: 1, minWidth: '300px' };
const trialH3Style = { margin: '0 0 12px 0', fontSize: '1.5rem', fontWeight: '700' };
const trialWarningStyle = { background: 'rgba(255, 255, 255, 0.2)', padding: '12px', borderRadius: '8px', marginTop: '12px', borderLeft: '4px solid #f6c23e' };
const trialActionsStyle = { display: 'flex', flexDirection: 'column', gap: '10px' };
const btnUpgradeStyle = { background: 'linear-gradient(135deg, #1cc88a, #17a673)', color: 'white', border: 'none', padding: '14px 24px', borderRadius: '12px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease', boxShadow: '0 5px 15px rgba(28, 200, 138, 0.3)' };
const btnInfoStyle = { background: 'rgba(255, 255, 255, 0.2)', color: 'white', border: '2px solid white', padding: '12px 24px', borderRadius: '12px', fontSize: '0.95rem', fontWeight: '600', cursor: 'pointer', transition: 'all 0.3s ease' };
const trialDetailsStyle = { marginTop: '20px', paddingTop: '20px', borderTop: '2px solid rgba(255, 255, 255, 0.3)' };
const trialDetailsContentStyle = { };
const trialDetailsH4Style = { margin: '0 0 16px 0', fontSize: '1.2rem' };
const trialDetailsUlStyle = { listStyle: 'none', padding: 0, margin: 0 };
const subscriptionModalStyle = computed(() => ({ 
  display: showSubscriptionModal.value ? 'flex' : 'none', 
  position: 'fixed', 
  zIndex: 10000, 
  left: 0, 
  top: 0, 
  width: '100%', 
  height: '100%', 
  backgroundColor: 'rgba(0, 0, 0, 0.7)', 
  backdropFilter: 'blur(5px)', 
  alignItems: 'center', 
  justifyContent: 'center' 
}));
const subscriptionModalContentStyle = { background: 'white', borderRadius: '20px', maxWidth: '500px', width: '90%', maxHeight: '90vh', overflowY: 'auto', boxShadow: '0 25px 80px rgba(0, 0, 0, 0.3)' };
const subscriptionModalHeaderStyle = { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', padding: '24px', borderRadius: '20px 20px 0 0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' };
const subscriptionModalH2Style = { margin: 0, fontSize: '1.5rem' };
const closeModalStyle = { background: 'none', border: 'none', color: 'white', fontSize: '2rem', cursor: 'pointer', lineHeight: 1, padding: 0, width: '32px', height: '32px', display: 'flex', alignItems: 'center', justifyContent: 'center' };
const subscriptionModalBodyStyle = { padding: '24px' };
const pricingHighlightStyle = { textAlign: 'center', marginBottom: '24px' };
const priceDisplayStyle = { display: 'flex', alignItems: 'baseline', justifyContent: 'center', gap: '4px', marginBottom: '8px' };
const currencyStyle = { fontSize: '1.5rem', fontWeight: '600' };
const amountStyle = { fontSize: '3rem', fontWeight: '800' };
const periodStyle = { fontSize: '1.2rem', color: '#858796' };
const pricingSubtitleStyle = { color: '#858796', fontSize: '1rem' };
const featuresListStyle = { marginBottom: '24px' };
const featuresH3Style = { color: '#2e3a59', marginBottom: '16px', fontSize: '1.2rem' };
const featuresUlStyle = { listStyle: 'none', padding: 0, margin: 0 };
const featuresLiStyle = { padding: '8px 0', display: 'flex', alignItems: 'center', gap: '8px' };
const checkIconStyle = { color: '#1cc88a', fontWeight: 'bold', marginRight: '8px' };
const paymentInfoStyle = { background: '#f8f9fc', padding: '16px', borderRadius: '8px', marginBottom: '16px' };
const paymentInfoPStyle = { margin: '8px 0', color: '#5a5c69' };
const contactInfoStyle = { margin: '8px 0', color: '#4e73df', fontWeight: '600' };
const subscriptionModalFooterStyle = { padding: '20px 24px', borderTop: '1px solid #e3e6f0', display: 'flex', gap: '12px', justifyContent: 'flex-end' };
const btnCancelStyle = { padding: '12px 24px', background: '#858796', color: 'white', border: 'none', borderRadius: '8px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer' };
const btnSubscribeStyle = { padding: '12px 24px', background: 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)', color: 'white', border: 'none', borderRadius: '8px', fontSize: '1rem', fontWeight: '600', cursor: 'pointer' };
const modalStyle = { display: 'flex', position: 'fixed', zIndex: 10000, left: 0, top: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0, 0, 0, 0.5)', justifyContent: 'center', alignItems: 'center' };
const pauseModalContentStyle = { background: 'white', borderRadius: '12px', maxWidth: '500px', width: '90%', boxShadow: '0 4px 20px rgba(0,0,0,0.3)' };
const completionModalContentStyle = { background: 'white', borderRadius: '12px', maxWidth: '500px', width: '90%', boxShadow: '0 4px 20px rgba(0,0,0,0.3)' };
const modalHeaderStyle = { padding: '20px 24px', borderBottom: '1px solid #e3e6f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' };
const modalH3Style = { margin: 0, color: '#2e3a59', fontSize: '1.2rem' };
const modalCloseStyle = { background: 'none', border: 'none', fontSize: '1.5rem', cursor: 'pointer', color: '#858796', padding: 0, width: '32px', height: '32px', display: 'flex', alignItems: 'center', justifyContent: 'center' };
const pauseFormStyle = { };
const completionFormStyle = { };
const modalBodyStyle = { padding: '20px 24px' };
const modalBodyPStyle = { marginBottom: '16px', color: '#5a5c69' };
const pauseTextareaStyle = { width: '100%', padding: '12px', border: '1px solid #d1d3e2', borderRadius: '8px', fontFamily: 'inherit', resize: 'vertical', fontSize: '14px' };
const pauseTextareaSmallStyle = { color: '#858796', fontSize: '12px' };
const modalFooterStyle = { padding: '20px 24px', borderTop: '1px solid #e3e6f0', display: 'flex', gap: '10px', justifyContent: 'flex-end' };
const modalCancelButtonStyle = { padding: '10px 20px', background: '#858796', color: 'white', border: 'none', borderRadius: '8px', cursor: 'pointer', fontWeight: '600' };
const modalSubmitButtonStyle = { padding: '10px 20px', background: 'linear-gradient(135deg, #858796 0%, #5a5c69 100%)', color: 'white', border: 'none', borderRadius: '8px', cursor: 'pointer', fontWeight: '600' };
const completionSubmitButtonStyle = { padding: '10px 20px', background: 'linear-gradient(135deg, #1cc88a 0%, #17a673 100%)', color: 'white', border: 'none', borderRadius: '8px', cursor: 'pointer', fontWeight: '600' };
const completionRangeStyle = { width: '100%', height: '8px', borderRadius: '5px', background: '#e3e6f0', outline: 'none', WebkitAppearance: 'none' };
const completionRangeLabelsStyle = { display: 'flex', justifyContent: 'space-between', marginTop: '4px' };
const completionRangeLabelStyle = { color: '#858796', fontSize: '12px' };
const completionValueStyle = { color: '#2e3a59', fontWeight: '600' };
const completionPreviewWrapperStyle = { marginTop: '16px', background: '#f8f9fc', padding: '12px', borderRadius: '8px' };
const completionPreviewContainerStyle = { flex: 1, background: '#e3e6f0', borderRadius: '10px', height: '24px', overflow: 'hidden', position: 'relative', display: 'flex', alignItems: 'center', gap: '8px' };
const completionPreviewTextStyle = { color: '#1cc88a', fontWeight: '600', fontSize: '14px', minWidth: '45px' };
</script>
