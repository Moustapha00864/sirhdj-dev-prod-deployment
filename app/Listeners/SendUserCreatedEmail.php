<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Services\EmailTemplateService;
use App\Services\WebhookService;
use Exception;

class SendUserCreatedEmail
{
    private static array $processedUsers = [];

    public function __construct(
        private WebhookService $webhookService
    ) {
    }

    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        // Prevent duplicate processing
        $userKey = $user->id . '_' . $user->updated_at->timestamp;
        if (in_array($userKey, self::$processedUsers)) {
            return;
        }

        self::$processedUsers[] = $userKey;

        try {
            // Generate password reset token and link
            $token = \Illuminate\Support\Facades\Password::createToken($user);
            $resetLink = route('password.reset', ['token' => $token, 'email' => $user->email]);

            // Send welcome email with reset link using the Mailable
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\UserCreatedMail($user, $resetLink));

            // Trigger webhooks for New User
            $this->webhookService->triggerWebhooks('New User', $user->toArray(), $user->created_by ?? $user->id);

        } catch (Exception $e) {
            // Store error in session for frontend notification
            session()->flash('email_error', 'Failed to send welcome email: ' . $e->getMessage());
        }
    }
}