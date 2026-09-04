<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class SupplierController extends Controller{public function index(Request $request):void{$this->view('suppliers/index',ActionRunner::collect('suppliers'));}}
