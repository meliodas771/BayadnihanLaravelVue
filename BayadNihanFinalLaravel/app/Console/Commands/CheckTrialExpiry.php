<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckTrialExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check-trials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired trials and update user roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($users as $user) {
            // When trial ends → switch role to poster
            $user->subscription_status = 'expired';
            $user->role = 'poster'; 
            $user->trial_ends_at = null;
            $user->save();
        }

        $this->info('Trial check completed.');
    }
}

