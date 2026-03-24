<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderSuccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $customerName;
    public string $appName;

    public function __construct(Order $order, string $customerName)
    {
        $this->order = $order;
        $this->customerName = $customerName;
        $this->appName = config('app.name', 'BoroBazar');
    }

    public function build(): self
    {
        return $this
            ->subject("Your order #{$this->order->id} is confirmed")
            ->view('emails.order-success')
            ->with([
                'order' => $this->order,
                'customerName' => $this->customerName,
                'appName' => $this->appName,
            ]);
    }
}

