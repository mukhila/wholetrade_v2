<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SubscriberController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total' => Subscriber::count(),
            'active' => Subscriber::active()->count(),
            'expired' => Subscriber::expired()->count(),
            'expiring_7_days' => Subscriber::expiringSoon(7)->count(),
            'expiring_30_days' => Subscriber::expiringSoon(30)->count(),
        ];

        $recentSubscribers = Subscriber::latest()->take(5)->get();
        $expiringSubscribers = Subscriber::expiringSoon(30)->orderBy('subscription_end_date')->get();

        return view('super_admin.dashboard', compact('stats', 'recentSubscribers', 'expiringSubscribers'));
    }

    public function index()
    {
        $subscribers = Subscriber::withCount('users')
            ->latest()
            ->paginate(15);

        return view('super_admin.subscribers.index', compact('subscribers'));
    }

    public function create()
    {
        return view('super_admin.subscribers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:subscribers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'plan' => 'required|in:basic,standard,premium',
            'subscription_start_date' => 'required|date',
            'subscription_end_date' => 'required|date|after:subscription_start_date',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|min:8',
        ]);

        DB::beginTransaction();

        try {
            $subscriber = Subscriber::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'plan' => $validated['plan'],
                'status' => 'active',
                'subscription_start_date' => $validated['subscription_start_date'],
                'subscription_end_date' => $validated['subscription_end_date'],
                'is_active' => true,
            ]);

            // Create admin user for subscriber
            User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'subscriber_id' => $subscriber->id,
                'is_subscriber_admin' => true,
            ]);

            DB::commit();

            return redirect()->route('super_admin.subscribers.index')
                ->with('success', 'Subscriber created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create subscriber: ' . $e->getMessage());
        }
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load('users');
        return view('super_admin.subscribers.show', compact('subscriber'));
    }

    public function edit(Subscriber $subscriber)
    {
        return view('super_admin.subscribers.edit', compact('subscriber'));
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:subscribers,email,' . $subscriber->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'plan' => 'required|in:basic,standard,premium',
            'status' => 'required|in:active,suspended,cancelled',
            'subscription_start_date' => 'required|date',
            'subscription_end_date' => 'required|date|after:subscription_start_date',
        ]);

        $subscriber->update($validated);

        return redirect()->route('super_admin.subscribers.index')
            ->with('success', 'Subscriber updated successfully.');
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()->route('super_admin.subscribers.index')
            ->with('success', 'Subscriber deleted successfully.');
    }

    public function extend(Request $request, Subscriber $subscriber)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $subscriber->subscription_end_date = $subscriber->subscription_end_date->addDays($validated['days']);
        $subscriber->save();

        return back()->with('success', 'Subscription extended by ' . $validated['days'] . ' days.');
    }

    public function suspend(Subscriber $subscriber)
    {
        $subscriber->update(['status' => 'suspended']);
        return back()->with('success', 'Subscriber suspended successfully.');
    }

    public function activate(Subscriber $subscriber)
    {
        $subscriber->update(['status' => 'active']);
        return back()->with('success', 'Subscriber activated successfully.');
    }

    public function users(Subscriber $subscriber)
    {
        $users = $subscriber->users()->paginate(15);
        return view('super_admin.subscribers.users', compact('subscriber', 'users'));
    }
}
