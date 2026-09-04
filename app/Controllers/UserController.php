<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class UserController extends Controller{public function index(Request $request):void{$this->view('users/index',ActionRunner::collect('users'));}}
