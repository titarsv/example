<?php

namespace Modules\Wishlist\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Http\Request;
use Modules\Wishlist\Models\Wishlist;

class WishlistController extends Controller
{
    /**
     * Moved from UsersController::adminWishlist() as-is. Note: there is
     * currently no route registered for this action and no
     * admin.users.wishlist view exists either - the "Favorites" button on
     * the admin user page (resources/views/admin/users/show.blade.php)
     * already pointed at a dead link before this module existed. Fixing
     * that is out of scope here; this move preserves behavior exactly.
     */
    public function adminWishlist($id)
    {
        $wishlist = Wishlist::where('user_id', $id)->paginate(10);

        return view('admin.users.wishlist')->with('wishlist', $wishlist)->with('user', User::find($id));
    }

    /**
     * Storefront wishlist page for the logged-in customer.
     */
    public function indexAction()
    {
        $user = Sentinel::check();

        $products = Product::whereHas('wishlist', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return view('public.wishlist')->with('products', $products);
    }

    /**
     * Add/remove a product from the logged-in customer's wishlist (heart
     * icon toggle on product cards / product page). Returns the new state
     * so the frontend can flip the icon without a page reload.
     */
    public function toggleAction(Request $request)
    {
        $user = Sentinel::check();

        $existing = Wishlist::where('user_id', $user->id)->where('product_id', $request->product_id)->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['result' => 'success', 'in_wish' => false]);
        }

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json(['result' => 'success', 'in_wish' => true]);
    }
}
