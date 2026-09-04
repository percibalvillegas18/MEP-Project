<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class ProjectController extends Controller{public function index(Request $request):void{$this->view('projects/index',ActionRunner::collect('projects'));}public function select(Request $request):void{$this->view('projects/select',ActionRunner::collect('select_project'));}}
