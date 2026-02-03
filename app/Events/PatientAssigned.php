<?php

namespace App\Events;

use App\Models\Visit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $visit;
    public $doctorUserId;

    public function __construct(Visit $visit, $doctorUserId)
    {
        $this->visit = $visit;
        $this->doctorUserId = $doctorUserId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->doctorUserId);
    }

    public function broadcastWith()
    {
        return [
            'title' => 'New Patient Assignment',
            'message' => "Patient {$this->visit->patient->name} has been assigned to you for {$this->visit->visit_type} visit.",
            'visit_id' => $this->visit->id,
            'patient_name' => $this->visit->patient->name,
            'visit_type' => $this->visit->visit_type,
            'created_at' => now()->toISOString()
        ];
    }
}