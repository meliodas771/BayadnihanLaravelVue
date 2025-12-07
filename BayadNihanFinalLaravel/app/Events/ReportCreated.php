<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportCreated implements ShouldBroadcastNow
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $report;

	public function __construct(Report $report)
	{
		$this->report = $report->load(['reporter:id,username,email', 'reportedUser:id,username,email']);
	}

	public function broadcastOn()
	{
		return new Channel('admin-reports');
	}

	public function broadcastWith()
	{
		return [
			'report' => [
				'id' => $this->report->id,
				'report_type' => $this->report->report_type,
				'reason' => $this->report->reason,
				'status' => $this->report->status,
				'reporter' => $this->report->reporter ? [
					'id' => $this->report->reporter->id,
					'username' => $this->report->reporter->username,
					'email' => $this->report->reporter->email,
				] : null,
				'reported_user' => $this->report->reportedUser ? [
					'id' => $this->report->reportedUser->id,
					'username' => $this->report->reportedUser->username,
					'email' => $this->report->reportedUser->email,
				] : null,
				'created_at' => $this->report->created_at?->toISOString(),
			]
		];
	}

	public function broadcastAs()
	{
		return 'ReportCreated';
	}
}

