<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrisSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QrisController extends Controller
{
    public function edit()
    {
        $qris = QrisSetting::first();

        return view('admin.qris.edit', compact('qris'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'qris_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $qris = QrisSetting::first();

        if (!$qris) {
            $qris = new QrisSetting();
        }

        if ($request->hasFile('qris_image')) {
            if ($qris->qris_image) {
                Storage::disk('public')->delete($qris->qris_image);
            }

            $qris->qris_image = $request
                ->file('qris_image')
                ->store('qris', 'public');
        }

        $qris->is_active = $request->boolean('is_active');
        $qris->save();

        return redirect()
            ->route('admin.qris.edit')
            ->with('success', 'QRIS berhasil diperbarui.');
    }
}