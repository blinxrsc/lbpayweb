<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //$customers = Customer::paginate(10);
        //return view('customers.index', compact('customers'));
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(10)->appends($request->only('search'));
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:customers',
            'phone_country_code' => 'nullable|string|max:5',
            'phone_number'       => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:customers',
            'password' => 'required|string|min:6',
            'birthday' => 'nullable|date',
            'tags'     => 'nullable|string',
            'sign_in'  => 'required|in:web,google,facebook',
            'status'   => 'required|in:active,inactive',
        ]);

        Customer::create($validated);
        return redirect()->route('customers.index')->with('success','Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:customers,email,'.$customer->id,
            'phone_country_code' => 'nullable|string|max:5',
            'phone_number'       => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:customers,username,'.$customer->id,
            'password' => 'nullable|string|min:6',
            'birthday' => 'nullable|date',
            'tags'     => 'nullable|string',
            'sign_in'  => 'required|in:web,google,facebook',
            'status'   => 'required|in:active,inactive',
        ]);

        $customer->update($validated);
        return redirect()->route('customers.index')->with('success','Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success','Customer deleted successfully.');
    }

    public function CSV(Request $request)
    {
        // Handle Export (Better to grab all data for export, not just paginated)
        if ($request->has('export')) {
            // Example: Export all devices, not just the 10 on the current page
            return $this->export(Customer::all());
        }
        // Handle Import
        if ($request->has('import')) {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt',
            ]);

            $path = $request->file('csv_file')->getRealPath();
            $rows = array_map('str_getcsv', file($path));
            $header = array_map('trim', $rows[0]);
            unset($rows[0]);

            $previewData = [];
            foreach ($rows as $row) {
                if (count($header) === count($row)) {
                    $previewData[] = array_combine($header, $row);
                }
            }

            // Store in session for the next request
            session(['import_preview' => $previewData]);

            return view('customers.import-preview', compact('previewData'));
        }
    }

    public function export($data)
    {
        //$customers = Customer::all();
        $fileName = "customer_" . now()->format('YmdHi') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            "Content-Disposition" => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = ['Name','Email','Phone Country Code'.'Phone Number','Username','Birthday','Tags','Referral Code','Sign In','Status'];

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $c) {
                fputcsv($file, [
                    $c->name,
                    $c->email,
                    $c->phone_country_code.$c->phone_number,
                    $c->username,
                    $c->birthday,
                    $c->tags,
                    $c->referral_code,
                    $c->sign_in,
                    $c->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function confirmImport(Request $request)
    {
        $dataToImport = session('import_preview');

        if (!$dataToImport) {
            return redirect()->route('customers.index')->with('error', 'No data to import.');
        }

        foreach ($dataToImport as $data) {
            // 1. Convert empty strings to null for database compatibility
            $sanitized = [
                'name'          => $data['Name'],
                'email'         => $data['Email'],
                'username'      => $data['Username'],
                'sign_in'       => $data['Sign In'],
                'status'        => $data['Status'],
                'phone_country_code'    => '60',
                'phone_number'   => substr($data['Phone Country CodePhone Number'],2,0),
                'birthday'      => !empty($data['Birthday']) ? $data['Birthday'] : null,
            ];
            //if ($sanitized->fails()) {
            //    continue; // skip invalid rows
            //}

            // Auto-generate referral code if missing
            if (empty($data['Referral Code'])) {
                $data['Referral Code'] = strtoupper(Str::random(6));
            }

            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            // 2. Use the sanitized array
            Customer::updateOrCreate(
                ['email' => $data['Email']], // unique key
                $sanitized
            );
        }

        // Clear session after success
        session()->forget('import_preview');

        return redirect()->route('customers.index')->with('success', 'Customers imported successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', $rows[0]);
        unset($rows[0]);

        foreach ($rows as $row) {
            $data = array_combine($header, $row);

            $validator = Validator::make($data, [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email',
                'username' => 'required|string|max:255',
                'password' => 'nullable|string|min:6',
                'sign_in'  => 'required|in:web,google,facebook',
                'status'   => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                continue; // skip invalid rows
            }

            // Auto-generate referral code if missing
            if (empty($data['referral_code'])) {
                $data['referral_code'] = strtoupper(Str::random(6));
            }

            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }

            Customer::updateOrCreate(
                ['email' => $data['email']], // unique key
                $data
            );
        }

        return redirect()->route('customers.index')->with('success','Customers imported successfully.');
    }
    public function sampleCsv()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer_sample.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'name','email','phone_country_code','phone_number',
                'username','password','birthday','tags',
                'referral_code','sign_in','status'
            ]);

            // Example rows
            fputcsv($handle, [
                'John Doe','john@example.com','+60','123456789',
                'johndoe','secret123','1990-05-10','VIP','','web','active'
            ]);
            fputcsv($handle, [
                'Jane Smith','jane@example.com','+60','987654321',
                'janesmith','','1988-11-20','Promo','ABC123','google','inactive'
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

}
