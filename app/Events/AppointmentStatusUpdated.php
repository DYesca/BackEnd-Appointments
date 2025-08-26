<?php

namespace App\Events;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected User $transmitter;
    protected Appointment $appointment;

    protected int $clientId;
    protected int $providerUserId;

    /**
     * Create a new event instance.
     */
    public function __construct(User $transmitter, Appointment $appointment)
    {
        $this->transmitter = $transmitter;
        $this->appointment = $appointment;
        $this->clientId = $appointment->client_id;
        $this->providerUserId = $appointment->provider->user_id;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastWith()
    {
        return [
            'appointment' => $this->appointment->toArray(),
            'transmitter' => $this->transmitter->toArray(),
        ];
    }
    public function broadcastOn(): array
    {

        \Log::info('Canales del evento', [
            'client_id' => $this->appointment->client_id,
            'provider_user_id' => $this->appointment->provider ? $this->appointment->provider->user_id : null,
        ]);
        return [
            new PrivateChannel('App.User.' . $this->appointment->client_id),
            new PrivateChannel('App.User.' . $this->appointment->provider->user_id),
        ];
    }
}
