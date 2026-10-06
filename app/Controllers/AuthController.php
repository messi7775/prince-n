<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Session;
use Services\AuthService;
use Models\AuditLog;

final class AuthController extends Controller
{
    public function root(Request $request): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        $this->redirect('/login');
    }

    public function legacyLogin(Request $request): void
    {
        $this->redirect('/login');
    }

    public function login(Request $request): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $error = '';

        if ($request->isPost()) {
            $this->verifyCsrf();

            if (!Session::loginAllowed()) {
                $error = 'تم تجاوز عدد محاولات الدخول. حاول بعد عدة دقائق.';
            } else {
                $email    = (string)$request->input('email', '');
                $password = (string)$request->input('password', '');

                if (!validate_email($email) || $password === '') {
                    Session::recordLoginFailure();
                    $error = 'أدخل البريد الإلكتروني وكلمة المرور بشكل صحيح.';
                } else {
                    $auth = new AuthService();
                    if ($auth->attempt($email, $password)) {
                        (new AuditLog())->log('login', 'تسجيل الدخول');
                        $this->redirect('/dashboard');
                    }
                    Session::recordLoginFailure();
                    $error = 'بيانات الدخول غير صحيحة.';
                }
            }
        }

        $this->view('auth/login', ['error' => $error, 'pageTitle' => 'تسجيل الدخول'], layout: null);
    }

    public function logout(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        if (Session::isAuthenticated()) {
            (new AuditLog())->log('logout', 'تسجيل الخروج');
        }

        Session::logout();
        $this->redirect('/login');
    }
}
