<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
        ], [
            'identifier.required' => 'Masukkan alamat email atau nomor telepon Anda.',
        ]);

        $identifier = trim($data['identifier']);
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])
                ->first();
        } else {
            $digits = preg_replace('/\D+/', '', $identifier);
            $normalized62 = str_starts_with($digits, '0')
                ? '62'.substr($digits, 1)
                : $digits;
            $normalized0 = str_starts_with($digits, '62')
                ? '0'.substr($digits, 2)
                : $digits;

            $candidates = array_unique(array_filter([$identifier, $digits, $normalized62, $normalized0]));

            $user = User::query()
                ->where(function ($q) use ($candidates): void {
                    $q->whereIn('telepon', $candidates)
                        ->orWhereIn('lid', $candidates);
                })
                ->first();
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => 'Akun dengan email atau nomor telepon tersebut tidak ditemukan.',
            ]);
        }

        $plainToken = Str::random(64);
        $tokenKey = $user->email ?: $user->id_user;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $tokenKey],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $plainToken,
            'email' => $user->email,
        ]);

        $channels = [];

        // 1. Send via WhatsApp (OpenWA) when user has a phone/LID or requested via phone
        if (! empty($user->telepon) || ! empty($user->lid)) {
            if ($this->sendWhatsAppResetLink($user, $resetUrl)) {
                $waTarget = $user->telepon ?: $user->lid;
                $channels[] = 'WhatsApp ('.$waTarget.')';
            }
        }

        // 2. Send via Email when user has an email address
        if (! empty($user->email)) {
            if ($this->sendEmailResetLink($user, $resetUrl)) {
                $channels[] = 'Email ('.$user->email.')';
            }
        }

        $channelLabel = ! empty($channels)
            ? implode(' dan ', $channels)
            : ($isEmail ? 'email '.$user->email : 'nomor '.$user->telepon);

        return back()
            ->with('success', 'Tautan verifikasi reset kata sandi telah dibuat dan dikirim ke '.$channelLabel.'.')
            ->with('reset_link', $resetUrl)
            ->with('reset_user', $user->name);
    }

    public function resetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = (string) $request->query('email', '');
        $user = $this->resolveValidTokenUser($email, $token);

        if (! $user) {
            return redirect()
                ->route('password.request')
                ->with('error', 'Tautan verifikasi reset kata sandi tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $user->email,
            'user' => $user,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = $this->resolveValidTokenUser($data['email'], $data['token']);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Tautan verifikasi reset kata sandi tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        // Explicitly hash with Bcrypt (and DB trigger also enforces Bcrypt)
        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        DB::table('password_reset_tokens')
            ->where('email', $user->email ?: $user->id_user)
            ->delete();

        return redirect()
            ->route('login')
            ->with('success', 'Kata sandi untuk '.$user->name.' berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.');
    }

    private function resolveValidTokenUser(string $email, string $plainToken): ?User
    {
        if ($email === '' || $plainToken === '') {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->first();

        if (! $user) {
            return null;
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email ?: $user->id_user)
            ->first();

        if (! $record) {
            return null;
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $record->email)->delete();

            return null;
        }

        if (! Hash::check($plainToken, $record->token)) {
            return null;
        }

        return $user;
    }

    private function sendWhatsAppResetLink(User $user, string $resetUrl): bool
    {
        $baseUrl = rtrim((string) env('OPENWA_URL', 'http://openwa-api:2785'), '/');
        $apiKey = (string) env('OPENWA_API_KEY', 'owa_k1_04f6c388bb3d0525e579f1454e835a7c00713b06726ce4572594611bf16436a5');
        $sessionId = (string) env('OPENWA_SESSION_ID', '09593dca-184f-4e66-bb61-1ea9758e5e5b');

        $rawPhone = trim((string) $user->telepon);
        $rawLid = trim((string) $user->lid);
        if ($rawPhone === '' && $rawLid === '') {
            return false;
        }

        $candidates = [];

        if ($rawPhone !== '') {
            $digits = preg_replace('/\D+/', '', $rawPhone);
            if (str_starts_with($digits, '0')) {
                $digits = '62'.substr($digits, 1);
            }

            if (str_contains($rawPhone, '@')) {
                $candidates[] = $rawPhone;
            } elseif (str_starts_with($digits, '628')) {
                $candidates[] = $digits.'@c.us';
                $candidates[] = $digits.'@lid';
            } else {
                $candidates[] = $digits.'@lid';
                $candidates[] = $digits.'@c.us';
            }
        }

        if ($rawLid !== '') {
            $lidDigits = preg_replace('/\D+/', '', $rawLid);
            if (str_contains($rawLid, '@')) {
                $candidates[] = $rawLid;
            } elseif ($lidDigits !== '') {
                $candidates[] = $lidDigits.'@lid';
            }
        }

        $candidates = array_values(array_unique($candidates));

        $message = "*Reset Kata Sandi KorlasSync*\n\n"
            ."Halo *{$user->name}*,\n"
            ."Kami menerima permintaan untuk mengatur ulang kata sandi akun KorlasSync Anda.\n\n"
            ."Silakan buka tautan verifikasi berikut untuk membuat kata sandi baru (berlaku 60 menit):\n"
            .$resetUrl."\n\n"
            ."_Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan pesan ini._";

        foreach ($candidates as $chatId) {
            try {
                $response = Http::withHeaders(['X-API-Key' => $apiKey])
                    ->timeout(8)
                    ->post("{$baseUrl}/api/sessions/{$sessionId}/messages/send-text", [
                        'chatId' => $chatId,
                        'text' => $message,
                    ]);

                if ($response->successful()) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }

    private function sendEmailResetLink(User $user, string $resetUrl): bool
    {
        try {
            Mail::raw(
                "Halo {$user->name},\n\n"
                ."Kami menerima permintaan untuk mengatur ulang kata sandi akun KorlasSync Anda.\n\n"
                ."Buka tautan verifikasi berikut untuk mengatur ulang kata sandi Anda (berlaku 60 menit):\n"
                ."{$resetUrl}\n\n"
                ."Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan pesan ini.\n\n"
                ."Salam,\nKorlasSync",
                function ($message) use ($user): void {
                    $message->to($user->email, $user->name)
                        ->subject('Tautan Verifikasi Reset Kata Sandi · KorlasSync');
                }
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
