<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function prompt()
    {
        return view('payment.prompt');
    }

    public function process(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $user = Auth::user();
            
            // Add payment verification logic here
            $paymentSuccess = true; // Replace with actual payment gateway verification
            
            if ($paymentSuccess) {
                $user->increment('view_count', 600);
                $user->update(['payment_status' => true]);
                
                DB::commit();
                return redirect()->route('bookings.index')
                    ->with('success', 'Payment successful! You now have 600 additional views.');
            }
            
            DB::rollBack();
            return redirect()->route('payment.prompt')
                ->with('error', 'Payment verification failed.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return redirect()->route('payment.prompt')
                ->with('error', 'An error occurred during payment processing.');
        }
    }
} 