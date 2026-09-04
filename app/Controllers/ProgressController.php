<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class ProgressController extends Controller{public function index(Request $request):void{$this->view('progress/index',ActionRunner::collect('project_progress'));}}
