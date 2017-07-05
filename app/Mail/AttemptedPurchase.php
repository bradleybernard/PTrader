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

    public $trades;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($trades)
    {
        $this->trades = $trades;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->trades[0]->account->refreshMoney();
        return $this->markdown('emails.trades.purchase');
    }
}
