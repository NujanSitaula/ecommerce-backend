<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $status;
    public ?string $reason;
    public string $customerName;
    public string $appName;

    public function __construct(
        Order $order,
        string $status,
        ?string $reason,
        string $customerName,
    ) {
        $this->order = $order;
        $this->status = $status;
        $this->reason = $reason;
        $this->customerName = $customerName;
        $this->appName = config('app.name', 'BoroBazar');
    }

    public function build(): self
    {
        return $this
            ->subject("Update on your order #{$this->order->id}: {$this->status}")
            ->view('emails.order-status-updated')
            ->with([
                'order' => $this->order,
                'status' => $this->status,
                'reason' => $this->reason,
                'customerName' => $this->customerName,
                'appName' => $this->appName,
            ]);
    }
}

