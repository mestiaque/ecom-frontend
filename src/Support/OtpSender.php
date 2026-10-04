<?php

namespace ME\Efront\Support;

use Illuminate\Support\Facades\Log;

/**
 * Delivers registration codes by SMS and e-mail (metheme's me_sms / me_mail).
 */
class OtpSender
{
    public function sms(string $phone, string $code): bool
    {
        $minutes = config('efront.registration_otp.expires', 10);
        $sent = me_sms($phone, 'Your '.efront()->storeName()." verification code is {$code}. It expires in {$minutes} minutes.", hideMessage: true);

        return $sent || $this->logLocally('phone', $phone, $code);
    }

    public function mail(string $email, string $name, string $code): bool
    {
        $sent = me_mail($email, 'Verify your email', '<p>Hi '.e($name).', use this code to verify your email for '.e(efront()->storeName()).'.</p>', [
            'otp' => $code,
            'title' => 'Email verification',
        ], 'auth');

        return $sent || $this->logLocally('email', $email, $code);
    }

    /**
     * Local development only: the code goes to the log when SMS / mail is not set up.
     */
    private function logLocally(string $channel, string $to, string $code): bool
    {
        if (! app()->isLocal()) {
            return false;
        }

        Log::info("[efront] Registration code for {$channel} {$to}: {$code} (not sent — local environment)");

        return true;
    }
}
