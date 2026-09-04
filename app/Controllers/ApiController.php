<?php
namespace App\Controllers;
use App\Core\ActionRunner;use App\Core\Request;
final class ApiController{public function boq(Request $r):void{ActionRunner::execute('ajax_boq_list');}public function masByBoq(Request $r):void{ActionRunner::execute('ajax_mas_by_boq');}public function manufacturers(Request $r):void{ActionRunner::execute('ajax_mfr_list');}public function progress(Request $r):void{ActionRunner::execute('ajax_progress_list');}public function submittals(Request $r):void{ActionRunner::execute('ajax_submittal_list');}}
