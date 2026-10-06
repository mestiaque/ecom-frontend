<?php

namespace ME\Efront\Http\Controllers\Account;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use ME\Efront\Http\Controllers\Controller;
use ME\Efront\Http\Middleware\AuthenticateCustomer;
use ME\Efront\Models\Customer;
use ME\Efront\Support\Phone;
use ME\Efront\Support\RegistrationOtp;

/**
 * Customer login (phone or e-mail + password), registration and password reset by e-mail.
 */
class AuthController extends Controller
{
    public function login(): View
    {
        return view('efront::account.auth.login');
    }

    public function loginStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => 'required|string|max:150',
            'password' => 'required|string',
        ]);

        $login = trim($data['login']);
        $customer = str_contains($login, '@')
            ? Customer::where('email', $login)->first()
            : Customer::where('phone', Phone::normalize($login))->first();

        if (! $customer || ! $customer->password || ! Hash::check($data['password'], $customer->password)) {
            return back()->withInput($request->only('login', 'remember'))->withErrors(['login' => 'The phone / e-mail or password is incorrect.']);
        }

        if ($customer->is_blocked) {
            return back()->withInput($request->only('login'))->withErrors(['login' => 'Your account has been blocked. Please contact support.']);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->to(AuthenticateCustomer::pullIntended($request, route('efront.account.dashboard')))->with('success', "Welcome back, {$customer->name}!");
    }

    public function register(): View
    {
        return view('efront::account.auth.register');
    }

    /**
     * Validate the form, then send the phone / email codes. The account is created in verifyStore().
     */
    public function registerStore(Request $request, RegistrationOtp $otp): RedirectResponse
    {
        $request->merge(['phone' => Phone::normalize((string) $request->input('phone'))]);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'string', Phone::RULE, Rule::unique('ecom_customers', 'phone')],
            'email' => [config('efront.registration_otp.email') ? 'required' : 'nullable', 'email', 'max:150', Rule::unique('ecom_customers', 'email')],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'phone.regex' => Phone::MESSAGE,
            'phone.unique' => 'An account with this phone number already exists. Please log in.',
            'email.unique' => 'An account with this e-mail already exists. Please log in.',
        ]);

        $data = [...$data, 'email' => $data['email'] ?? null, 'password' => Hash::make($data['password'])];

        if (RegistrationOtp::channels($data) === []) {
            return $this->createAccount($request, $data, []);
        }

        $otp->start($data);

        return redirect()->route('efront.account.register.verify');
    }

    public function verify(RegistrationOtp $otp): View|RedirectResponse
    {
        $pending = $otp->pending();

        if (! $pending || ! $pending['codes']) {
            return redirect()->route('efront.account.register');
        }

        return view('efront::account.auth.verify', [
            'data' => $pending['data'],
            'channels' => array_keys($pending['codes']),
            'resendIn' => $otp->secondsUntilResend(),
        ]);
    }

    public function verifyStore(Request $request, RegistrationOtp $otp): RedirectResponse
    {
        if (! $otp->pending()) {
            return redirect()->route('efront.account.register');
        }

        $data = $otp->verify($request->only('phone_code', 'email_code'));

        // Someone may have registered the same phone / email while the codes were open
        if (Customer::where('phone', $data['phone'])->orWhere(fn ($q) => $q->whereNotNull('email')->where('email', $data['email']))->exists()) {
            $otp->clear();

            return redirect()->route('efront.account.login')->with('error', 'An account with this phone or email already exists. Please log in.');
        }

        $verified = RegistrationOtp::channels($data);
        $otp->clear();

        return $this->createAccount($request, $data, $verified);
    }

    public function resend(RegistrationOtp $otp): RedirectResponse
    {
        if (! $otp->pending()) {
            return redirect()->route('efront.account.register');
        }

        if ($seconds = $otp->secondsUntilResend()) {
            return back()->with('error', "Please wait {$seconds} seconds before asking for a new code.");
        }

        $otp->send();

        return back()->with('success', 'A new code has been sent.');
    }

    /**
     * @param  array{name: string, phone: string, email: ?string, password: string}  $data
     * @param  array<int, string>  $verified  channels confirmed by code
     */
    private function createAccount(Request $request, array $data, array $verified): RedirectResponse
    {
        $customer = Customer::create([
            ...$data,
            'phone_verified_at' => in_array('phone', $verified, true) ? now() : null,
            'email_verified_at' => in_array('email', $verified, true) ? now() : null,
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->to(AuthenticateCustomer::pullIntended($request, route('efront.account.dashboard')))->with('success', 'Your account has been created. Welcome!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('efront.home')->with('success', 'You have been logged out.');
    }

    public function forgotPassword(): View
    {
        return view('efront::account.auth.forgot-password');
    }

    public function forgotPasswordStore(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'Please wait a minute before asking for another link.']);
        }

        // Same answer whether the e-mail exists or not, so accounts cannot be discovered here
        return back()->with('success', 'If an account uses this e-mail, a password reset link has been sent to it.');
    }

    public function resetPassword(Request $request, string $token): View
    {
        return view('efront::account.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPasswordStore(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password): void {
                $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($customer));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Please ask for a new one.']);
        }

        return redirect()->route('efront.account.login')->with('success', 'Your password has been reset. Please log in.');
    }
}
