<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WebsiteController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Website/Home');
    }

    public function about(): Response
    {
        return Inertia::render('Website/About');
    }

    public function features(): Response
    {
        return Inertia::render('Website/Features');
    }

    public function contact(): Response
    {
        return Inertia::render('Website/Contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('mail.from.address');
        $subject = __('website.contact.mail_subject', ['name' => $validated['name']]);
        $body = __('website.contact.mail_body', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        try {
            if (is_string($to) && $to !== '') {
                Mail::raw($body, function ($message) use ($to, $subject, $validated): void {
                    $message->to($to)
                        ->subject($subject)
                        ->replyTo($validated['email'], $validated['name']);
                });
            } else {
                Log::info('Website contact form submission', $validated);
            }
        } catch (Throwable $e) {
            Log::warning('Website contact form mail failed', [
                'error' => $e->getMessage(),
                'payload' => $validated,
            ]);
        }

        return redirect()
            ->route('website.contact')
            ->with('type', 'success')
            ->with('message', __('website.contact.success'));
    }
}
