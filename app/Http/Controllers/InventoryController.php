<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Donation;
use App\Models\Notification;
use Carbon\Carbon;

class InventoryController extends Controller
{
    /**
     * Display a listing of the items.
     */
    public function index()
    {
        // ✅ 自动检测3天内将过期的物品
        $today = Carbon::today();
        $threeDaysLater = $today->copy()->addDays(3);

        // 找出未来3天内即将过期的物品
        $expiringItems = InventoryItem::whereBetween('expiry_date', [$today, $threeDaysLater])->get();

        foreach ($expiringItems as $item) {
            // 检查是否已存在相同的“未读提醒”
            $exists = Notification::where('item_name', $item->name)
                ->where('message', 'like', '%will expire%')
                ->where('status', 'new')
                ->exists();

            // ✅ 检查今天是否已经提醒过该物品
            $alreadyReminded = Notification::where('item_name', $item->name)
                ->whereDate('created_at', $today)
                ->exists();

            // ✅ 仅当未提醒过 且 没有旧的未读提醒 时，创建新通知
            if (!$exists && !$alreadyReminded) {
                if ($item->expiry_date) {
                    $daysLeft = Carbon::parse($item->expiry_date)->diffInDays($today);
                    $message = $item->name . ' will expire in ' . $daysLeft . ' day' . ($daysLeft > 1 ? 's' : '');

                    try {
                        Notification::create([
                            'item_name' => $item->name,
                            'message' => $message,
                            'expiry_date' => $item->expiry_date,
                            'status' => 'new',
                            'created_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('❌ Failed to create notification for item ' . $item->name . ': ' . $e->getMessage());
                    }
                }
            }
        }

        // ✅ 正常显示库存物品
        $items = InventoryItem::where('user_id', auth()->id())
            ->orderBy('expiry_date', 'asc')
            ->with('user')
            ->get();


        return view('managefoodinventory.inventory', compact('items'));
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100', // ✅ 新增 category 验证
            'quantity' => 'required|numeric|min:1',
            'unit' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
            'status' => 'nullable|string|in:available,used,reserved,expired',
        ]);

        // ✅ 保存包含 category 的数据
        InventoryItem::create([
            'name' => $request->name,
            'category' => $request->category,
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'expiry_date' => $request->expiry_date,
            'status' => $validated['status'] ?? 'available', // Default status
            'user_id' => auth()->id(), // ✅ Automatically assign the logged-in user
        ]);

        return redirect()->route('inventory.index')
            ->with('success', 'New item added successfully!');
    }

    /**
     * Update an existing item.
     */
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100', // ✅ 新增 category 验证
            'quantity' => 'required|numeric|min:1',
            'unit' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
        ]);

        // ✅ 更新包含 category 的数据
        $item->update([
            'name' => $request->name,
            'category' => $request->category,
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'expiry_date' => $request->expiry_date,
        ]);

        return redirect()->route('inventory.index')
            ->with('success', 'Item updated successfully!');
    }

    /**
     * Remove an item from inventory.
     */
    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Item deleted successfully!');
    }

    /**
     * Show the form for converting an item to donation.
     */
    public function convertForm($id)
    {
        $item = InventoryItem::findOrFail($id);
        return view('managefoodinventory.convert_donation', compact('item'));
    }

    /**
     * Handle conversion to donation.
     */
    public function convertStore(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        // ✅ 创建新的 Donation 记录
        Donation::create([
            'item_name' => $item->name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'expiry_date' => $item->expiry_date,
            'pickup_location' => $validated['pickup_location'],
            'pickup_duration' => $validated['pickup_duration'],
        ]);

        // ✅ 转换成功后删除库存项
        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Item converted to donation successfully!');
    }

    public function markUsed($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->status = 'used';
        $item->save();

        return back()->with('success', 'Item marked as used successfully.');
    }

    public function planMeal($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->status = 'reserved';
        $item->save();

        return back()->with('success', 'Item reserved for meal successfully.');
    }
}
