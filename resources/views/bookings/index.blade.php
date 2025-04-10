@extends('layouts.app')

@section('content')
<div class="container" data-csrf="{{ csrf_token() }}">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('My Bookings') }}</span>
                    <div>
                        <span class="badge bg-primary">{{ __('Views Remaining:') }} {{ e(auth()->user()->view_count) }}</span>
                        <a href="{{ route('bookings.create') }}" 
                           class="btn btn-success ms-2"
                           rel="noopener">{{ __('New Booking') }}</a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ e(session('error')) }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ e(session('success')) }}
                        </div>
                    @endif

                    <!-- Location Selector -->
                    <div class="mb-4">
                        <h5 class="mb-3">{{ __('Select a Location to View Bookings') }}</h5>
                        <div class="row g-2">
                            @php
                                $locations = [
                                    'Alakkode', 'Anjarakkandi', 'Cheruthazham', 'Madayi', 'Ezhome',
                                    'Cherukunnu', 'Mattool', 'Kannapuram', 'Kadambur', 'Kadirur'
                                ];
                            @endphp
                            @foreach($locations as $location)
                                <div class="col-md-3">
                                    <button 
                                        class="btn btn-outline-primary w-100 location-btn"
                                        data-location="{{ e($location) }}"
                                        data-token="{{ csrf_token() }}"
                                        {{ auth()->user()->view_count <= 0 ? 'disabled' : '' }}>
                                        {{ e($location) }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Bookings Table -->
                    <div id="bookings-table" class="d-none">
                        @include('bookings._table', ['bookings' => $bookings ?? collect()])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
@if(auth()->user()->view_count <= 0)
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Payment Required') }}</h5>
                </div>
                <div class="modal-body">
                    <p>{{ __('You\'ve used all your available views. Purchase 600 more views for ₹3000.') }}</p>
                    <form action="{{ route('payment.process') }}" 
                          method="POST" 
                          id="payment-form"
                          data-token="{{ csrf_token() }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            {{ __('Pay ₹3000') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script nonce="{{ session('csp-nonce') }}">
document.addEventListener('DOMContentLoaded', function() {
    const locationButtons = document.querySelectorAll('.location-btn');
    const bookingsTable = document.getElementById('bookings-table');
    const viewCount = {{ auth()->user()->view_count }};
    let lastClickTime = 0;
    const THROTTLE_DELAY = 1000; // 1 second delay between clicks

    async function fetchBookings(location, token) {
        try {
            const currentTime = Date.now();
            if (currentTime - lastClickTime < THROTTLE_DELAY) {
                throw new Error('Please wait before selecting another location');
            }
            lastClickTime = currentTime;

            const response = await fetch(`/bookings/filter?location=${encodeURIComponent(location)}`, {
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            // Validate response data
            if (!data.html || typeof data.viewCount !== 'number') {
                throw new Error('Invalid response format');
            }

            // Update UI
            bookingsTable.innerHTML = DOMPurify.sanitize(data.html);
            bookingsTable.classList.remove('d-none');
            document.querySelector('.badge.bg-primary').textContent = 
                `Views Remaining: ${data.viewCount}`;

            // Update URL safely
            const url = new URL(window.location);
            url.searchParams.set('location', location);
            window.history.pushState({}, '', url);

        } catch (error) {
            console.error('Error:', error);
            alert(error.message || 'An error occurred while fetching bookings.');
        }
    }

    locationButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (viewCount <= 0) {
                const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                paymentModal.show();
                return;
            }

            const location = this.dataset.location;
            const token = this.dataset.token;

            // Remove active state from all buttons
            locationButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active state to clicked button
            this.classList.add('active');

            fetchBookings(location, token);
        });
    });

    // Prevent form resubmission
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
});
</script>
@endpush
@endsection 