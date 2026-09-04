<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class DashboardController extends Controller{public function index(Request $request):void{$this->view('dashboard/index',ActionRunner::collect('dashboard'));}}
