<?php

namespace App\Http\Controllers;

use App\Services\CasClient;
use App\Services\TeacherDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CasAuthController extends Controller
{
    public function redirect(Request $request, CasClient $cas): RedirectResponse
    {
        abort_unless(config('cas.enabled'), 503, '统一身份认证尚未启用。');

        $intended = $this->safeIntendedUrl((string) $request->query('intended', '/dashboard'));
        $request->session()->put('cas.intended', $intended);

        return redirect()->away($cas->loginUrl($this->serviceUrl()));
    }

    public function callback(
        Request $request,
        CasClient $cas,
        TeacherDirectoryService $directory,
    ): RedirectResponse {
        abort_unless(config('cas.enabled'), 503, '统一身份认证尚未启用。');
        $ticket = trim((string) $request->query('ticket'));
        if ($ticket === '') {
            return to_route('login')->withErrors(['cas' => '统一身份认证未返回有效票据，请重新登录。']);
        }

        try {
            $casIdentity = $cas->validateTicket($this->serviceUrl(), $ticket);
            $attributes = $casIdentity['attributes'];
            $teacher = $directory->findEligible([
                $casIdentity['subject'],
                $this->firstAttribute($attributes, 'accountId'),
                $this->firstAttribute($attributes, 'userId'),
                $this->firstAttribute($attributes, 'userName'),
            ]);

            if (! $teacher) {
                return to_route('login')->withErrors([
                    'cas' => '该账号不在疗休养教师资格清单中，请联系校工会核实。',
                ]);
            }

            $user = $directory->syncTeacher($teacher, $casIdentity['subject']);
        } catch (RuntimeException $exception) {
            return to_route('login')->withErrors(['cas' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Log::error('CAS 登录或教师资格校验失败', ['exception' => $exception]);

            return to_route('login')->withErrors([
                'cas' => '暂时无法连接统一身份认证或教师资格库，请稍后重试。',
            ]);
        }

        $intended = $request->session()->pull('cas.intended', '/dashboard');
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('cas.authenticated', true);

        return redirect()->to($this->safeIntendedUrl((string) $intended));
    }

    public function logout(Request $request, CasClient $cas): RedirectResponse
    {
        $casAuthenticated = (bool) $request->session()->pull('cas.authenticated', false);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($casAuthenticated && config('cas.enabled')) {
            return redirect()->away($cas->logoutUrl(route('auth.cas.logged-out')));
        }

        return to_route('login');
    }

    public function loggedOut(): RedirectResponse
    {
        return to_route('login')->with('status', '您已退出统一身份认证。');
    }

    public function singleLogout(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $callback = (string) $request->query('callback', '');
        if ($callback !== '' && preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]*$/', $callback)) {
            return response("{$callback}({\"success\":true});", 200, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function serviceUrl(): string
    {
        return (string) (config('cas.service_url') ?: route('auth.cas.callback'));
    }

    /** @param array<string, string|array<int, string>> $attributes */
    private function firstAttribute(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? '';

        return trim((string) (is_array($value) ? ($value[0] ?? '') : $value));
    }

    private function safeIntendedUrl(string $url): string
    {
        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : '/dashboard';
    }
}
