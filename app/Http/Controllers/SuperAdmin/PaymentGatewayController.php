<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderBy('sort_order')->get();
        return view('super-admin.payment-gateways.index', compact('gateways'));
    }

    public function edit(PaymentGateway $paymentGateway)
    {
        return view('super-admin.payment-gateways.edit', compact('paymentGateway'));
    }

    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        $request->validate([
            'is_enabled' => 'nullable|boolean',
            'mode' => 'required|in:sandbox,live',
        ]);

        try {
            // Build credentials from config_fields
            $credentials = [];
            foreach ($paymentGateway->config_fields as $field) {
                $key = $field['key'];
                $value = $request->input("credentials.{$key}");

                // Keep existing value if field is password-type and submitted empty
                if (($field['type'] ?? 'text') === 'password' && empty($value)) {
                    $credentials[$key] = $paymentGateway->getCredential($key);
                } else {
                    $credentials[$key] = $value ?? '';
                }
            }

            $paymentGateway->update([
                'is_enabled' => $request->has('is_enabled'),
                'mode' => $request->mode,
                'credentials' => $credentials,
            ]);

            return back()->with('success', "{$paymentGateway->name} settings updated successfully.");
        } catch (\Throwable $e) {
            Log::error('[PaymentGateway] Update failed', [
                'gateway' => $paymentGateway->slug,
                'error' => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Failed to save settings. Please try again.');
        }
    }

    public function toggle(PaymentGateway $paymentGateway)
    {
        try {
            $paymentGateway->update(['is_enabled' => !$paymentGateway->is_enabled]);
            $status = $paymentGateway->is_enabled ? 'enabled' : 'disabled';
            return back()->with('success', "{$paymentGateway->name} has been {$status}.");
        } catch (\Throwable $e) {
            Log::error('[PaymentGateway] Toggle failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update gateway status.');
        }
    }
}
