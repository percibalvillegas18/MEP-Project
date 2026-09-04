<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class AuthController extends Controller
{
    public function login(Request $request):void{$this->view('auth/login',ActionRunner::collect('login'));}
    public function signup(Request $request):void{$this->view('auth/signup',ActionRunner::collect('signup'));}
    public function setup(Request $request):void{$this->view('auth/setup',ActionRunner::collect('setup_admin'));}
    public function resetPassword(Request $request):void{$this->view('auth/reset_password',ActionRunner::collect('reset_password'));}
    public function logout(Request $request):void{ActionRunner::execute('logout');}
}
