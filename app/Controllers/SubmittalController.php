<?php
namespace App\Controllers;

use App\Core\ActionRunner;
use App\Core\Controller;
use App\Core\Request;

final class SubmittalController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('submittals/index', ActionRunner::collect('submittals'));
    }
}