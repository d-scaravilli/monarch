<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:contanti,bonifico,carta',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $enrollment->payments()->create($data);

        return back()->with('status', 'Pagamento registrato.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $redirectTo = url()->previous();
        $payment->delete();

        return redirect($redirectTo)->with('status', 'Pagamento eliminato.');
    }
}
