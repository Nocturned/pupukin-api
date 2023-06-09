<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Profile;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\TransactionByStore;
use App\Models\TransactionItems;
use App\Models\TransactionStatus;
use App\Models\PaymentMethod;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\ItemHistory;

use function PHPUnit\Framework\isEmpty;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        $cart = Cart::where('profile_id', $profile->id)
            ->with(
                'cartItem',
                'cartItem.item',
                // 'cartItem.item.picture',
                // 'cartItem.item.store', 
            //     'cartItem.item.type',
            //     'cartItem.item.plant',
            //     'cartItem.item.plantPart'
                )
            ->first();
    
        
        $cartGroupByStore = Cart::where('carts.profile_id', $profile->id)
            ->join('cart_items as cartItem', 'cartItem.cart_id', '=', 'carts.id')
            ->join('items as item', 'item.id', '=', 'cartItem.item_id')
            ->join('stores as store', 'store.id', '=', 'item.store_id')
            ->groupBy('store.id')
            ->select('store.id as store_id', 'store.name as store_name', 'store.address as store_address', 'store.latitude as store_latitude', 'store.longitude as store_longitude')
            ->get();

        $request->validate([
            'recipient_name' => 'required',
            'recipient_phone' => 'required',
            'recipient_address' => 'required',
            'recipient_latitude' => 'required',
            'recipient_longitude' => 'required',
            'payment_method_id' => 'required',
        ]);

        $request->merge([
            'profile_id' => $profile->id,
            'payment_status_id' => 2,
        ]);
        
        DB::beginTransaction();

        try{
            $transactionId = DB::table('transactions')->insertGetId([
                'recipient_name' => $request->recipient_name,
                'recipient_phone' => $request->recipient_phone,
                'recipient_address' => $request->recipient_address,
                'recipient_latitude' => $request->recipient_latitude,
                'recipient_longitude' => $request->recipient_longitude,
                'profile_id' => $request->profile_id,
                'payment_method_id' => $request->payment_method_id,
                'payment_status_id' => $request->payment_status_id,
                'created_at' => date('Y-m-d H:i:sO', time()),
                'updated_at' => date('Y-m-d H:i:sO', time())
            ], 'id');


            foreach($cartGroupByStore as $cartGrouped) {
                $transactionByStoreId = DB::table('transaction_by_stores')->insertGetId([
                    'transaction_id' => $transactionId,
                    'store_id' => $cartGrouped->store_id,
                    'invoice' => 'INV/'.date('ymd').'/'.$cartGrouped->store_id.'/'.$transactionId.'/'.mt_rand(1000, 9999),
                    'transaction_status_id' => 1,
                    'created_at' => date('Y-m-d H:i:sO', time()),
                    'updated_at' => date('Y-m-d H:i:sO', time())
                ], 'id');
            }

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->with('item', 'item.picture', 'item.store', 'item.type', 'item.plant', 'item.plantPart')
                ->get();

            $ids = [];
            foreach($cart->cartItem as $cartItems){
                $transactionItemId = DB::table('transaction_items')->insertGetId([
                    'transaction_by_store_id' => TransactionByStore::where('transaction_id', $transactionId)->where('store_id', $cartItems->item->store_id)->first()->id,
                    'item_id' => $cartItems->item_id,
                    'store_id' => $cartItems->item->store_id,
                    'quantity' => $cartItems->quantity,
                    'price' => $cartItems->item->price,
                    'subtotal' => $cartItems->item->price * $cartItems->quantity,
                    'created_at' => date('Y-m-d H:i:sO', time()),
                    'updated_at' => date('Y-m-d H:i:sO', time())
                ], 'id');

                $ids[] = $transactionItemId;
            }

            

            return response()->json([
                    "message" => "Checkpoint 2.",
                    "cart" => $cartItem
                ], 400);

            // $cartItem['item_history_id'] = $ids;

            foreach($cartItem as $cartItems){
                $picture = [];
                foreach($cartItems->item->picture as $cartItemPicture){
                    $picture[] = $cartItemPicture->picture;
                }
                $picture = json_encode($picture);

                $plant = [];
                foreach($cartItems->item->plant as $cartItemPlant){
                    $plant[] = $cartItemPlant->name;
                }
                $plant = json_encode($plant);

                $plantPart = [];
                foreach($cartItems->item->plantPart as $cartItemPlantPart){
                    $plantPart[] = $cartItemPlantPart->name;
                }
                $plantPart = json_encode($plantPart);

                $itemHistoryId = DB::table('item_histories')->insertGetId([
                    // 'transaction_item_id'
                    'name' => $cartItems->item->name,
                    'picture' => $picture,
                    'description' => $cartItems->item->description,
                    'type' => $cartItems->item->type->name,
                    'plant' => $plant,
                    'plant_part' => $plantPart,
                    'price' => $cartItems->item->price,
                    'brand' => $cartItems->item->brand,
                    'created_at' => date('Y-m-d H:i:sO', time()),
                    'updated_at' => date('Y-m-d H:i:sO', time())
                ], 'id');
            }

            foreach($cartItem as $cartItems){
                $cartItems->delete();
            }

            $transaction = Transaction::where('id', $transactionId)
                ->with('transactionByStore', 'transactionByStore.transactionItem', 'transactionByStore.transactionItem.item')
                ->first();
            
            if($transaction->transactionByStore->isEmpty()){
                DB::rollback();

                return response()->json([
                    "message" => "Cart is empty."
                ], 400);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction success.',
                'transaction' => $cartItem
            ], 200);

        } catch(Exception $e) {
            DB::rollback();

            return response()->json([
                "message" => $e->getMessage()
            ], 400);
        }

    }
}
