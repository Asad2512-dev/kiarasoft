<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        $shop = $request->user()->shop;

        return view('settings.edit', ['shop' => $shop, 'settings' => $shop->config()]);
    }

    public function update(Request $request, ActivityLogger $logger)
    {
        $shop = $request->user()->shop;
        $settings = $shop->config();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'in:PKR'],
            'receipt_header' => ['nullable', 'string', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'invoice_prefix' => ['required', 'alpha', 'max:6', 'not_in:RET,ret'],
            'tax_enabled' => ['boolean'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:50', 'decimal:0,2'],
            'default_low_stock_threshold' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,3'],
            'max_discount_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('kirasoft.max_upload_kb')],
        ]);
        $shop->update(collect($data)->only(['name', 'owner_name', 'email', 'phone', 'address', 'city'])->all());
        $data['tax_enabled'] = $request->boolean('tax_enabled');
        $data['invoice_prefix'] = strtoupper($data['invoice_prefix']);
        $settings->fill(collect($data)->only(['currency', 'receipt_header', 'receipt_footer', 'invoice_prefix', 'tax_enabled', 'tax_rate', 'default_low_stock_threshold', 'max_discount_percent'])->all());
        if ($request->hasFile('logo')) {
            $old = $settings->logo_path;
            $settings->logo_path = $request->file('logo')->store("logos/{$shop->id}", 'public');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        } elseif ($request->boolean('remove_logo') && $settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
        }
        $settings->save();
        $logger->log('settings.updated', 'Shop settings updated', $shop, ActivityLogger::diff($settings), null, $shop->id);

        return back()->with('success', 'Settings saved.');
    }
}
