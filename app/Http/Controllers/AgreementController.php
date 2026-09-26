<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Models\EnrollmentAgreement;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AgreementController extends Controller
{
    /** Show the agreement text + signature pad. Reached via a signed URL so an applicant can sign before they have an account to log into. */
    public function sign(User $user)
    {
        abort_if(EnrollmentAgreement::hasSigned($user), 403, 'You have already signed your enrollment agreement.');

        return view('agreements.sign', [
            'student' => $user,
            'submitUrl' => URL::signedRoute('agreement.sign.submit', ['user' => $user->id]),
        ]);
    }

    /** Record the drawn signature + audit trail, then continue straight on to the reservation-fee payment. */
    public function submit(Request $request, User $user, PaymentService $payments)
    {
        abort_if(EnrollmentAgreement::hasSigned($user), 403, 'You have already signed your enrollment agreement.');

        $request->validate([
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $binary = base64_decode(substr($request->input('signature'), strlen('data:image/png;base64,')), true);
        if ($binary === false || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return back()->withErrors(['signature' => 'Please draw your signature before submitting.']);
        }

        $agreementHtml = view('agreements.enrollment', ['student' => $user])->render();

        $path = 'agreement-signatures/' . $user->id . '-' . Str::random(20) . '.png';
        Storage::disk('public')->put($path, $binary);

        EnrollmentAgreement::create([
            'user_id' => $user->id,
            'signature_path' => $path,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'agreement_hash' => hash('sha256', $agreementHtml),
            'signed_at' => now(),
        ]);

        try {
            $checkoutUrl = $payments->startReservationCheckout(
                $user,
                URL::signedRoute('apply.reservation.return', ['user' => $user->id]),
                URL::signedRoute('apply.reservation.cancel', ['user' => $user->id])
            );
        } catch (PaymentGatewayException $e) {
            return redirect()->route('apply')->with('success',
                'Your enrollment agreement has been signed. We could not open the online payment page just now (' .
                $e->getMessage() . '), so please settle your reservation fee at the cashier window instead.'
            );
        }

        return view('auth.redirecting-to-payment', [
            'checkoutUrl' => $checkoutUrl,
            'programName' => $user->major,
            'reservationFee' => (int) Setting::get('reservation_fee', '500'),
        ]);
    }

    /** Stream the authenticated user's own signed enrollment agreement back as a PDF. */
    public function downloadMine()
    {
        return $this->download(Auth::user());
    }

    /** Same, but for Registrar/Admission staff checking a given applicant's/student's agreement. */
    public function downloadForUser(int $id)
    {
        return $this->download(User::findOrFail($id));
    }

    private function download(User $user)
    {
        $agreement = $user->agreements()->latest()->first();

        if (! $agreement) {
            return back()->with('error', 'No signed enrollment agreement was found for this account.');
        }

        $pdf = Pdf::loadView('agreements.signed', [
            'student' => $user,
            'agreement' => $agreement,
            'signaturePath' => Storage::disk('public')->path($agreement->signature_path),
        ]);

        return $pdf->stream('enrollment-agreement.pdf');
    }
}
