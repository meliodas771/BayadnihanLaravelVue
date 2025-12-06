<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'email_verified_at',
        'password',
        'role',
        'trial_ends_at',
        'subscription_status',
        'phone_number',
        'profile_pic',
        'google_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function postedTasks() { return $this->hasMany(Task::class, 'poster_id'); }
    public function applications() { return $this->hasMany(Application::class, 'doer_id'); }
    public function feedbacksGiven() { return $this->hasMany(Feedback::class, 'from_user_id'); }
    public function feedbacksReceived() { return $this->hasMany(Feedback::class, 'to_user_id'); }
    
    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }
    
    /**
     * Get masked username for display in public profiles
     * If username is numeric or contains numbers with dashes (ID number), mask it like "2*1-12**5" for "231-12345"
     */
    public function getMaskedUsernameAttribute()
    {
        // Check if username is numeric or contains numbers with dashes (like ID numbers)
        if ((is_numeric($this->username) || preg_match('/^[\d\-]+$/', $this->username)) && strlen($this->username) > 5) {
            $username = $this->username;
            
            // Check if username contains a dash
            if (strpos($username, '-') !== false) {
                $parts = explode('-', $username, 2);
                $firstPart = $parts[0];
                $secondPart = $parts[1] ?? '';
                
                // First part: keep first char, mask middle, keep last char
                if (strlen($firstPart) > 2) {
                    $maskedFirst = substr($firstPart, 0, 1) . str_repeat('*', strlen($firstPart) - 2) . substr($firstPart, -1);
                } elseif (strlen($firstPart) == 2) {
                    $maskedFirst = substr($firstPart, 0, 1) . '*';
                } else {
                    $maskedFirst = $firstPart;
                }
                
                // Second part: keep first 2 chars, mask middle, keep last char
                if (strlen($secondPart) > 3) {
                    $maskedSecond = substr($secondPart, 0, 2) . str_repeat('*', strlen($secondPart) - 3) . substr($secondPart, -1);
                } elseif (strlen($secondPart) == 3) {
                    $maskedSecond = substr($secondPart, 0, 2) . '*';
                } elseif (strlen($secondPart) == 2) {
                    $maskedSecond = substr($secondPart, 0, 1) . '*';
                } else {
                    $maskedSecond = str_repeat('*', strlen($secondPart));
                }
                
                return $maskedFirst . '-' . $maskedSecond;
            } else {
                // No dash: keep first character, mask the rest
                return substr($username, 0, 1) . str_repeat('*', max(0, strlen($username) - 1));
            }
        }
        return $this->username;
    }

    /**
     * Check if user is on trial period
     */
    public function isOnTrial()
    {
        return $this->subscription_status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has expired
     */
    public function trialExpired()
    {
        return $this->subscription_status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isPast();
    }

    /**
     * Get days remaining in trial
     */
    public function trialDaysRemaining()
    {
        if (!$this->isOnTrial()) {
            return 0;
        }
        return now()->diffInDays($this->trial_ends_at, false);
    }
}
