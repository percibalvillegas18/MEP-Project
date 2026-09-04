<?php
namespace App\Core;

final class ActionRunner
{
    private const ALLOWED = [
        'dashboard','projects','select_project','project_progress','submittals',
        'procurement','workplan','suppliers','users','login','signup','setup_admin',
        'logout','reset_password','report_pdf','workplan_report_pdf','export_excel',
        'export_management_xlsx','ajax_boq_list','ajax_mas_by_boq','ajax_mfr_list',
        'ajax_progress_list','ajax_submittal_list',
    ];

    public static function collect(string $action): array
    {
        self::assertAllowed($action);
        $_SERVER['MEP_ACTION_FILE'] = 'app/Actions/'.$action.'.php';
        global $pdo;
        require dirname(__DIR__).'/Actions/'.$action.'.php';
        $data = get_defined_vars();
        unset($data['action'], $data['pdo']);
        return $data;
    }

    public static function execute(string $action): void
    {
        self::assertAllowed($action);
        $_SERVER['MEP_ACTION_FILE'] = 'app/Actions/'.$action.'.php';
        global $pdo;
        require dirname(__DIR__).'/Actions/'.$action.'.php';
    }

    private static function assertAllowed(string $action): void
    {
        if (!in_array($action, self::ALLOWED, true)) {
            throw new \InvalidArgumentException('Unknown application action.');
        }
    }
}
