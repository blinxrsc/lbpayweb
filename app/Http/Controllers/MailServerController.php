<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\MailServer;
use App\Models\MailServerLog;

class MailServerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = MailServer::paginate(10);
        return view('mailserver.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mailserver.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|numeric',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'encryption' => 'nullable|string|in:ssl,tls',
        ]);

        MailServer::create($validated);
        return redirect()->route('mailserver.index')->with('success','Mail server added successfully.');
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
    public function edit(MailServer $mailserver)
    {
        return view('mailserver.edit', compact('mailserver'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MailServer $mailserver)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|numeric',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'encryption' => 'nullable|string|in:ssl,tls',
        ]);
        // If password is blank, don’t overwrite 
        if (empty($validated['password'])) { 
            unset($validated['password']); 
        }

        $mailserver->update($validated);
        return redirect()->route('mailserver.index')->with('success','Mail server updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MailServer $mailserver)
    {
        $mailserver->delete();
        return redirect()->route('mailserver.index')->with('success','Mail server deleted successfully.');
    }
    /** Test Mail Server */
    public function test(Request $request, MailServer $mailserver)
    {
        // 1. Validate that we have the necessary credentials
        if (!$mailserver->host || !$mailserver->username || !$mailserver->password) {
            return back()->with('error', 'Mail server configuration is incomplete.');
        }

        try {
            // 2. Define dynamic SMTP configuration
            $config = [
                'transport'  => 'smtp',
                'host'       => $mailserver->host,
                'port'       => $mailserver->port,
                'encryption' => $mailserver->encryption, // e.g., 'tls' or 'ssl'
                'username'   => $mailserver->username,
                'password'   => $mailserver->password,
                'timeout'    => 30, // Short timeout for testing
            ];

            // 3. Send using a temporary mailer instance
            Mail::build($config)->raw('Connection Successful! Your SMTP settings are correct.', function ($message) use ($mailserver) {
                $message->to('itsupport@laundrybar.com.my')
                        ->from($mailserver->username, 'LBPayLinker System')
                        ->subject('✅ SMTP Configuration Test');
            });

            return back()->with('success', 'Connection established and test email sent!');

        } catch (\Exception $e) {
            // Log the full stack trace for the developer
            \Log::error("SMTP Test Failure: " . $e->getMessage());

            // Return a user-friendly snippet of the error
            return back()->with('error', 'Mail Error: ' . $e->getMessage());
        }
    }
    /** Update Logs */
    public function testlog(Request $request, MailServer $mailserver)
    {
        config([
            'mail.mailers.smtp.host'       => $mailserver->host,
            'mail.mailers.smtp.port'       => $mailserver->port,
            'mail.mailers.smtp.username'   => $mailserver->username,
            'mail.mailers.smtp.password'   => $mailserver->password,
            'mail.mailers.smtp.encryption' => $mailserver->encryption,
            'mail.default'                 => 'smtp',
            'mail.from.address'            => $mailserver->username,
            'mail.from.name'               => 'Your App Name',
        ]);

        //$recipient = $request->user()->email;
        $recipient = 'itsupport@laundrybar.com.my';
        try {
            \Mail::raw('This is a test email from your configured mail server.', function ($message) use ($recipient) {
                $message->to($recipient)->subject('Test Mail Server Configuration');
            });

            MailServerLog::create([
                'mail_server_id' => $mailserver->id,
                'recipient_email' => $recipient,
                'status' => 'success',
                'message' => 'Test email sent successfully'
            ]);

            return back()->with('success', 'Test email sent successfully to '.$recipient);
        } catch (\Exception $e) {
            MailServerLog::create([
                'mail_server_id' => $mailserver->id,
                'recipient_email' => $recipient,
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to send test email: '.$e->getMessage());
        }
    }
    public function logs()
    {
        $logs = MailServerLog::with('mailServer')->latest()->paginate(20);
        return view('mailserver.logs', compact('logs'));
    }

}
