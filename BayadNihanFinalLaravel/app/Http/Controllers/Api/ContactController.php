<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Handle contact form submissions and send via configured SMTP.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $to = config('mail.contact_to', 'bayadnihan@gmail.com');
        $fromAddress = config('mail.from.address');

        try {
            Mail::raw(
                "Name: {$data['name']}\nEmail: {$data['email']}\nSubject: {$data['subject']}\nMessage:\n{$data['message']}",
                function ($mail) use ($data, $to, $fromAddress) {
                    $mail->to($to)
                        ->replyTo($data['email'])
                        ->subject('Contact Form: ' . $data['subject']);

                    if ($fromAddress) {
                        $mail->from($fromAddress, config('mail.from.name', 'BayadNihan'));
                    }
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Contact email send error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
            ], 500);
        }
    }
}

