<?php

namespace Modules\Wishlist\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
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
}
