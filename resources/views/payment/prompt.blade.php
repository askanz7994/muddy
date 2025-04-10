@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Payment Required</div>

                <div class="card-body text-center">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <i class="fas fa-lock fa-4x text-primary mb-3"></i>
                        <h4>You've reached your view limit</h4>
                        <p class="text-muted">To continue viewing your bookings, please make a payment of ₹3000 to get 600 additional views.</p>
                    </div>

                    <form method="POST" action="{{ route('payment.process') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Pay ₹3000
                        </button>
                    </form>

                    <div class="mt-4">
                        <p class="text-muted">Secure payment powered by Razorpay</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 