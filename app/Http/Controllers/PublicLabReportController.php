<?php

namespace App\Http\Controllers;

use App\Models\InvestigationOrder;
use App\Services\LabReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicLabReportController extends Controller
{
    private const SESSION_TTL_MINUTES = 120;

    public function show(string $shareToken): View
    {
        $order = $this->findOrderOrAbort($shareToken);

        if ($this->isUnlocked($order)) {
            return redirect()->route('lab-report.view', $order->share_token);
        }

        return view('public.lab-report.verify', [
            'order' => $order,
            'settings' => $this->hospitalSettings(),
        ]);
    }

    public function verify(Request $request, string $shareToken)
    {
        $order = $this->findOrderOrAbort($shareToken);

        $rateLimitKey = 'lab-report-verify:' . $shareToken . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return back()
                ->withInput()
                ->withErrors(['patient_no' => 'Too many attempts. Please try again later.']);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $validated = $request->validate([
            'patient_no' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
        ], [
            'patient_no.required' => 'Patient number is required.',
            'phone.required' => 'Mobile number is required.',
        ]);

        $order->loadMissing('patient');
        $patient = $order->patient;

        $patientNoMatches = $patient
            && strcasecmp(trim($validated['patient_no']), trim((string) $patient->patient_no)) === 0;

        $phoneMatches = $patient
            && $this->phonesMatch($validated['phone'], (string) $patient->phone);

        if (! $patientNoMatches || ! $phoneMatches) {
            return back()
                ->withInput()
                ->withErrors(['patient_no' => 'The details you entered do not match our records.']);
        }

        RateLimiter::clear($rateLimitKey);
        $this->unlock($order);

        return redirect()->route('lab-report.view', $order->share_token);
    }

    public function view(string $shareToken): View|Response
    {
        $order = $this->findOrderOrAbort($shareToken);

        if (! $this->isUnlocked($order)) {
            return redirect()
                ->route('lab-report.show', $order->share_token)
                ->withErrors(['patient_no' => 'Please verify your details to view this report.']);
        }

        $report = LabReportBuilder::build($order);

        return view('admin.lab.results.report', [
            'report' => $report,
            'isPublic' => true,
        ]);
    }

    private function findOrderOrAbort(string $shareToken): InvestigationOrder
    {
        return InvestigationOrder::query()
            ->where('share_token', $shareToken)
            ->firstOrFail();
    }

    private function sessionKey(InvestigationOrder $order): string
    {
        return 'lab_report_unlocked.' . $order->id;
    }

    private function isUnlocked(InvestigationOrder $order): bool
    {
        $expiresAt = session($this->sessionKey($order));

        if (! $expiresAt) {
            return false;
        }

        return now()->lt($expiresAt);
    }

    private function unlock(InvestigationOrder $order): void
    {
        session([$this->sessionKey($order) => now()->addMinutes(self::SESSION_TTL_MINUTES)->toIso8601String()]);
    }

    private function phonesMatch(string $input, string $stored): bool
    {
        $a = $this->normalizePhone($input);
        $b = $this->normalizePhone($stored);

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_ends_with($a, $b) || str_ends_with($b, $a);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '92') && strlen($digits) >= 12) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * @return array{hospital_name: string, hospital_address: string, hospital_phone: string, hospital_email: string, hospital_logo: ?string}
     */
    private function hospitalSettings(): array
    {
        return [
            'hospital_name' => setting('hospital_name', config('app.name', 'Hospital')),
            'hospital_address' => setting('hospital_address', ''),
            'hospital_phone' => setting('hospital_phone', ''),
            'hospital_email' => setting('hospital_email', ''),
            'hospital_logo' => setting('hospital_logo', null),
        ];
    }
}
