<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Models\EnrollmentAgreement;
use App\Models\User;
use App\Services\DocuSignService;
use App\Services\PaymentService;

class AgreementController extends Controller
{
    /**
     * DocuSign redirects the student's browser here once they finish (or
     * decline) the embedded signing ceremony. We identify which agreement
     * this is via the random "token" query param we embedded in the return
     * URL when the envelope was created (see DocuSignService::createEnvelopeForUser) —
     * not Laravel's signed-URL middleware, because DocuSign appends its own
     * "?event=..." parameter to our return URL, which would otherwise break
     * signature validation.
     */
    public function returning(User $user, DocuSignService $docusign, PaymentService $payments)
    {
        $token = request('token');

        $agreement = EnrollmentAgreement::where('user_id', $user->id)
            ->where('return_token', $token)
            ->latest()->first();

        if (! $agreement) {
            return redirect()->route('apply')->with('error', 'We could not find your signing session. Please start again.');
        }

        // Belt-and-suspenders: poll DocuSign directly in case the Connect
        // webhook (the primary path) hasn't landed yet by the time the
        // student's browser gets redirected back to us.
        try {
            $agreement = $docusign->syncStatus($agreement);
        } catch (PaymentGatewayException) {
            // If DocuSign is briefly unreachable here, we simply fall through
            // and let the webhook (once it arrives) mark this signed instead.
        }

        if (! $agreement->isCompleted()) {
            return redirect()->route('apply')->with('error',
                'Your enrollment agreement was not completed (' . $agreement->status . '). ' .
                'Please try signing again, or settle your reservation fee at the cashier window instead.'
            );
        }

        // Signed! Now actually continue on to the reservation-fee payment —
        // PaymentService::startReservationCheckout() will see hasSigned() is
        // now true and proceed straight to the PayMongo checkout this time.
        try {
            $checkoutUrl = $payments->startReservationCheckout(
                $user,
                \Illuminate\Support\Facades\URL::signedRoute('apply.reservation.return', ['user' => $user->id]),
                \Illuminate\Support\Facades\URL::signedRoute('apply.reservation.cancel', ['user' => $user->id])
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
            'reservationFee' => (int) \App\Models\Setting::get('reservation_fee', '500'),
        ]);
    }

    /** Stream the authenticated user's own signed enrollment agreement back from DocuSign. */
    public function downloadMine(DocuSignService $docusign)
    {
        return $this->download(\Illuminate\Support\Facades\Auth::user(), $docusign);
    }

    /** Same, but for Registrar/Admission staff checking a given applicant's/student's agreement. */
    public function downloadForUser(int $id, DocuSignService $docusign)
    {
        return $this->download(User::findOrFail($id), $docusign);
    }

    private function download(User $user, DocuSignService $docusign)
    {
        $agreement = $user->agreements()->where('status', 'completed')->latest()->first();

        if (! $agreement) {
            return back()->with('error', 'No signed enrollment agreement was found for this account.');
        }

        try {
            $document = $docusign->downloadSignedDocument($agreement);
        } catch (PaymentGatewayException $e) {
            return back()->with('error', 'Could not retrieve the signed document: ' . $e->getMessage());
        }

        return response($document, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="enrollment-agreement.pdf"');
    }
}