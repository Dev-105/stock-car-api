<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Command;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Command $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Payment Successful')
                    ->view('emails.payment-success');
    }
}