<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Nexmo;

class SendText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $from;
    protected $to;
    protected $text;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($from, $to, $text)
    {
        $this->from = $from;
        $this->to = $to;
        $this->text = $text;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Nexmo::message()->send([
            'to' => $this->to,
            'from' => $this->from,
            'text' => $this->text,
        ]);
    }
}
