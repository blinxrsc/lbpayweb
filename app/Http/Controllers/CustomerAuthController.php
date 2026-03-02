<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\DeviceOutlet;
use App\Models\CustomerTermAgreement;
use App\Models\TermsOfService;

class CustomerAuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() 
    { 
        //$customer = Auth::guard('customer')->user(); 
        //$account = $customer->ewalletAccount; // relation on Customer model 
        //$credit = $account->credit_balance ?? 0; 
        //$bonus = $account->bonus_balance ?? 0; 
        //return view('customer.dashboard', compact('customer', 'credit', 'bonus')); 
        return redirect()->route('customer.dashboard');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        return view('customer.profile', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'phone_country_code' => 'required|string|max:5',
            'phone_number'       => 'required|string|max:20',
            'birthday'           => 'required|date',
        ]);

        $customer = Auth::guard('customer')->user();
        $customer->update($request->only('phone_country_code','phone_number','birthday'));

        //return redirect()->route('customer.profile.edit')->with('success', 'Profile updated successfully.');
        return redirect()->route('customer.dashboard')->with('success', 'Profile updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showLoginForm(Request $request)
    {    
        if ($request->filled('device_outlet_id')) {
            session([
                'pay.device_outlet_id' => $request->device_outlet_id,
                'pay.amount' => $request->amount,
            ]);

            // set intended URL to confirm page
            session(['url.intended' => route('customer.payment.confirm', $request->device_outlet_id)]);
        }

        return view('auth.customer-login');
    }

    public function login(Request $request)
    {
        /**
        $credentials = $request->only('email','password');

        if (Auth::guard('customer')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate(); // important for session persistence

            return redirect()->intended(route('customer.dashboard'));
        }
        // This handles both wrong email AND wrong password
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
        **/
        $customer = Customer::where('email', $request->email)->first();
        if (!$customer) {
            return back()->withErrors(['email' => 'Email not found.']);
        }

        if (!Hash::check($request->password, $customer->password)) {
            return back()->withErrors(['password' => 'The password you entered is incorrect.']);
        }

        // If both pass, log them in manually
        Auth::guard('customer')->login($customer, $request->filled('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'));

    }

    public function logout(Request $request)
    {
        // Perform the logout
        Auth::guard('customer')->logout();
        // Invalidate the session and regenerate the token
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // Redirect to the login page or any other desired page
        return redirect()->route('customer.login');
    }

    public function showLinkRequestForm()
    {
        return view('auth.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showRegistrationForm()
    {
        return view('auth.customer-register');
    }

    public function register(Request $request)
    {
        $activeTerms = TermsOfService::where('is_active', true)->get();

        $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:customers,email',
            'username'           => 'required|string|max:255|unique:customers,username',
            'password'           => 'required|string|min:6|confirmed',
            'phone_number'       => 'required|string|digits_between:8,12',    // allow digits, dashes
            'birthday'           => 'required|date',             // must be a valid date
            'terms' => 'required|array|min:' . $activeTerms->count(), // Must check all active terms
        ]);
        // Auto set country code
        $phoneCountryCode = '60';

        // Remove leading 0 from phone number
        $phoneNumber = ltrim($request->phone_number, '0');

        $customer = Customer::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'username'           => $request->username,
            //'phone_country_code' => $request->phone_country_code,
            'phone_country_code' => '60',
            'phone_number'       => ltrim($request->phone_number, '0'),
            'birthday'           => $request->birthday,
            'password'           => Hash::make($request->password),
            'referral_code'      => strtoupper(Str::random(6)),
            'sign_in'            => 'web',     //  automatic
            'status'             => 'active',  //  automatic
        ]);

        // Save the agreement version for this customer
        foreach ($activeTerms as $term) {
            CustomerTermAgreement::create([
                'customer_id' => $customer->id,
                'term_id' => $term->id,
                'version_agreed' => $term->version,
            ]);
        }

        Auth::guard('customer')->login($customer);

        return redirect()->intended('/customer/dashboard');
    }

    public function legals()
    {
        $customer = Auth::guard('customer')->user();
        
        // We fetch all active terms and "eager load" the customer's specific agreement
        $terms = TermsOfService::where('is_active', true)
            ->get()
            ->map(function ($term) use ($customer) {
                $agreement = $customer->termAgreements()
                    ->where('term_id', $term->id)
                    ->first();
                    
                $term->signed_at = $agreement ? $agreement->created_at : null;
                $term->version_signed = $agreement ? $agreement->version_agreed : 'Not Signed';
                return $term;
            });

        return view('customer.legals', compact('terms'));
    }

}
