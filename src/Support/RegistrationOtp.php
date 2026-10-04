<?php

namespace ME\Efront\Support;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Pending registration kept in the session until the phone / e-mail codes are confirmed.
 * Codes are stored hashed, expire after config('efront.registration_otp.expires') minutes and
 * allow a limited number of wrong tries.
 */
class RegistrationOtp
{
    private const KEY = 'efront.registration';

    public function __construct(private OtpSender $sender) {}

    /**
     * Channels that need a code for this registration ("phone", "email").
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function channels(array $data): array
    {
        return array_values(array_filter([
            config('efront.registration_otp.phone') ? 'phone' : null,
            config('efront.registration_otp.email') && ! empty($data['email']) ? 'email' : null,
        ]));
    }

    /**
     * Keep the form data (password already hashed) and send the codes.
     *
     * @param  array{name: string, phone: string, email: ?string, password: string}  $data
     *
     * @throws ValidationException when a code cannot be delivered
     */
    public function start(array $data): void
    {
        session([self::KEY => ['data' => $data, 'codes' => [], 'attempts' => 0]]);

        $this->send();
    }

    /**
     * (Re)send fresh codes to every channel.
     *
     * @throws ValidationException
     */
    public function send(): void
    {
        $pending = $this->pending();
        $codes = [];

        foreach (self::channels($pending['data']) as $channel) {
            $code = (string) random_int(100000, 999999);
            $sent = $channel === 'phone'
                ? $this->sender->sms($pending['data']['phone'], $code)
                : $this->sender->mail($pending['data']['email'], $pending['data']['name'], $code);

            if (! $sent) {
                throw ValidationException::withMessages([
                    $channel => $channel === 'phone'
                        ? 'We could not send the SMS code right now. Please try again in a moment.'
                        : 'We could not send the email code right now. Please check the address or try again.',
                ]);
            }

            $codes[$channel] = Hash::make($code);
        }

        session([self::KEY => [
            ...$pending,
            'codes' => $codes,
            'attempts' => 0,
            'sent_at' => now()->timestamp,
            'expires_at' => now()->addMinutes((int) config('efront.registration_otp.expires', 10))->timestamp,
        ]]);
    }

    /**
     * @return array{data: array<string, mixed>, codes: array<string, string>, attempts: int, sent_at?: int, expires_at?: int}|null
     */
    public function pending(): ?array
    {
        return session(self::KEY);
    }

    public function secondsUntilResend(): int
    {
        $sentAt = $this->pending()['sent_at'] ?? 0;

        return max(0, $sentAt + (int) config('efront.registration_otp.resend_after', 60) - now()->timestamp);
    }

    /**
     * Check the typed codes. Returns the registration data when every code matches.
     *
     * @param  array<string, mixed>  $input  ['phone_code' => '123456', 'email_code' => '654321']
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function verify(array $input): array
    {
        $pending = $this->pending();

        if (! $pending || count($pending['codes']) !== count(self::channels($pending['data']))) {
            throw ValidationException::withMessages(['code' => 'Please send the verification code again.']);
        }

        if (now()->timestamp > ($pending['expires_at'] ?? 0)) {
            throw ValidationException::withMessages(['code' => 'The code has expired. Please send a new one.']);
        }

        if ($pending['attempts'] >= (int) config('efront.registration_otp.max_attempts', 5)) {
            throw ValidationException::withMessages(['code' => 'Too many wrong tries. Please send a new code.']);
        }

        $errors = [];
        foreach ($pending['codes'] as $channel => $hash) {
            if (! Hash::check(trim((string) ($input[$channel.'_code'] ?? '')), $hash)) {
                $errors[$channel.'_code'] = $channel === 'phone' ? 'The SMS code is not correct.' : 'The email code is not correct.';
            }
        }

        if ($errors) {
            session([self::KEY.'.attempts' => $pending['attempts'] + 1]);

            throw ValidationException::withMessages($errors);
        }

        return $pending['data'];
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }
}
