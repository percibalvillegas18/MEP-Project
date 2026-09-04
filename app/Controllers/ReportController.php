<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Controller;use App\Core\Request;
final class ReportController extends Controller{public function progressPdf(Request $r):void{$this->view('reports/progress',ActionRunner::collect('report_pdf'));}public function workplanPdf(Request $r):void{$this->view('reports/workplan',ActionRunner::collect('workplan_report_pdf'));}public function progressXlsx(Request $r):void{ActionRunner::execute('export_excel');}public function managementXlsx(Request $r):void{ActionRunner::execute('export_management_xlsx');}}
