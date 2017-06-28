<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Trade;

class AttemptedPurchase extends Mailable
{
    use Queueable, SerializesModels;

    public $trade;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->trade->account->refreshMoney();
        return $this->markdown('emails.trades.purchase');
    }
}
