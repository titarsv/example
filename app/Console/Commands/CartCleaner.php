<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;


class CartCleaner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'clear_carts';

    protected $cart;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans empty user carts in DB';

    /**
     * Create a new command instance.
     */

    public function __construct()
    {
        $this->cart = new Cart();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->cart->where('total_quantity', 0)->delete();
    }
}
