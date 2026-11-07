<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserSubscription;
use App\Services\MonetizationService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Display all transactions, screenshots, and active user subscriptions.
     */
    public function index(Request $request)
    {
        $ordersQuery = Order::with(['user', 'bankCard'])->latest();

        if ($request->filled('type')) {
            $ordersQuery->where('order_type', $request->type);
        }

        if ($request->filled('status')) {
            $ordersQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $ordersQuery->where(function ($q) use ($search) {
                $q->where('transaction_ref', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $ordersQuery->paginate(15, ['*'], 'orders_page')->withQueryString();

        $activeSubscriptions = UserSubscription::with(['user', 'plan'])
            ->latest()
            ->paginate(10, ['*'], 'subs_page');

        return view('admin.orders.index', compact('orders', 'activeSubscriptions'));
    }

    /**
     * Approve a pending order and grant subscription/bids.
     */
    public function approve($id, MonetizationService $monetizationService)
    {
        $order = Order::with('user')->findOrFail($id);

        try {
            $result = $monetizationService->approveOrder($order);
            return redirect()->back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve order: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending order.
     */
    public function reject(Request $request, $id, MonetizationService $monetizationService)
    {
        $order = Order::findOrFail($id);
        $reason = $request->input('reason', 'Payment screenshot verification failed');

        $monetizationService->rejectOrder($order, $reason);

        return redirect()->back()->with('success', "Order #{$order->transaction_ref} has been marked as rejected.");
    }
}
