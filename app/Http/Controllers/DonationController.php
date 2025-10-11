<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\InventoryItem;
use App\Models\Notification;

class DonationController extends Controller
{
    // ✅ Show donation list
    public function index()
    {
        $donations = Donation::latest()->get();
        return view('managefoodinventory.donation', compact('donations'));
    }

    // ✅ Convert from inventory
    public function convert(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($request->item_id);

        // ✅ 创建 Donation 记录
        Donation::create([
            'item_name' => $item->name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'expiry_date' => $item->expiry_date,
            'pickup_location' => $request->pickup_location,
            'pickup_duration' => $request->pickup_duration,
        ]);

        // ✅ 创建 Donation 成功的通知
        try {
            Notification::create([
                'item_name' => $item->name,
                'message' => 'You have successfully donated "' . $item->name . '".',
                'expiry_date' => now(),
                'status' => 'new',
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Failed to create donation notification for item ' . $item->name . ': ' . $e->getMessage());
        }

        // ✅ 从库存软删除
        $item->delete();

        return redirect()
            ->route('donation.index')
            ->with('success', 'Item successfully converted to donation!');
    }

    // ✅ Remove donation and return to inventory
    public function destroy($id)
    {
        $donation = Donation::findOrFail($id);

        // ✅ Step 1: 尝试恢复被软删除的库存（如果存在）
        $existing = InventoryItem::withTrashed()
            ->where('name', $donation->item_name)
            ->first();

        if ($existing) {
            // 如果找到软删除的旧记录 → 直接恢复
            $existing->restore();
        } else {
            // 否则 → 创建新的一条
            InventoryItem::create([
                'name' => $donation->item_name,
                'quantity' => $donation->quantity ?? 1,
                'unit' => $donation->unit ?? 'pcs',
                'expiry_date' => $donation->expiry_date ?? now()->addDays(7),
            ]);
        }

        // ✅ Step 2: 删除 donation
        $donation->delete();

        // ✅ Step 3: 新建通知
        Notification::create([
            'item_name' => $donation->item_name,
            'message' => 'Donation of "' . $donation->item_name . '" has been removed and returned to inventory.',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        // ✅ Step 4: 返回提示
        return redirect()->route('donation.index')
            ->with('success', 'Donation removed and item returned to inventory.');
    }
}
