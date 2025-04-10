<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected $locations = [
        'Alakkode' => 'Alakkode',
        'Anjarakkandi' => 'Anjarakkandi',
        'Cheruthazham' => 'Cheruthazham',
        'Madayi' => 'Madayi',
        'Ezhome' => 'Ezhome',
        'Cherukunnu' => 'Cherukunnu',
        'Mattool' => 'Mattool',
        'Kannapuram' => 'Kannapuram',
        'Kadambur' => 'Kadambur',
        'Kadirur' => 'Kadirur'
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $location = request('location');
        $bookings = collect();

        if ($location) {
            $bookings = Auth::user()->bookings()
                            ->where('location', $location)
                            ->latest()
                            ->get();
        }

        return view('bookings.index', compact('bookings'));
    }

    public function filter(Request $request)
    {
        try {
            // Validate request is AJAX
            if (!$request->ajax()) {
                throw new \Exception('Invalid request method');
            }

            $user = Auth::user();
            $location = $request->query('location');

            // Validate location
            if (!in_array($location, $this->locations)) {
                throw new \Exception('Invalid location selected');
            }

            // Check for rate limiting
            $key = 'location_views:' . $user->id;
            $maxAttempts = 10; // Max 10 views per minute
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                throw new \Exception("Too many attempts. Please wait {$seconds} seconds.");
            }

            // Check view count
            if ($user->view_count <= 0) {
                return response()->json([
                    'redirect' => route('payment.prompt')
                ]);
            }

            DB::beginTransaction();
            try {
                // Deduct view count
                $user->decrement('view_count');

                // Log the view
                \Log::info('Location view', [
                    'user_id' => $user->id,
                    'location' => $location,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                // Get filtered bookings
                $bookings = $user->bookings()
                    ->where('location', $location)
                    ->latest()
                    ->get();

                // Record the attempt
                RateLimiter::hit($key, 60); // Reset after 60 seconds

                DB::commit();

                // Render view with CSRF token
                $html = View::make('bookings._table', compact('bookings'))
                    ->render();

                return response()->json([
                    'html' => $html,
                    'viewCount' => $user->view_count
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Booking filter error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'An error occurred while processing your request.'
            ], 422);
        }
    }

    public function create()
    {
        $locations = $this->locations;
        return view('bookings.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'location' => ['required', 'string', 'in:' . implode(',', array_keys($this->locations))],
            'booking_date' => 'required|date|after:now',
            'duration_hours' => 'required|integer|min:1|max:24',
            'additional_notes' => 'nullable|string',
        ]);

        $booking = Auth::user()->bookings()->create($validated);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking created successfully!');
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);
        return view('bookings.show', compact('booking'));
    }
} 