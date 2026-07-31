<?php

namespace App\Console\Commands;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Console\Command;
use App\Models\ProductsExport;

class MyCryptoCheckout extends Command
{
    /**
     * Название команды
     *
     * @var string
     */
    protected $name = 'mycryptocheckout';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'MyCryptoCheckout events';

    protected $exports;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->exports = new ProductsExport();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
        $account = $mcc->account();

        $mcc->payments()->send_unsent_payments();

        $account->retrieve();
    }
}
