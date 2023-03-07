<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateScheduledInvoiceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:update-scheduled-invoice-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Scheduling Invoices status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $invoices = Invoice::scheduled()->pluck('id')->toArray();
        if (sizeof($invoices)>0){
            Invoice::whereIn('id',$invoices)->update(['status'=>2]);
        }
        //Log::info($invoices);
    }
}
