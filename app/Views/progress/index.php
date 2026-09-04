<?php include __DIR__.'/../../../includes/header.php'; ?>
<div class="project-context panel">
 <div>
  <div class="context-nav-links">
   <a href="projects.php">&larr; Projects</a>
   <span class="context-nav-sep">&bull;</span>
   <?php if ($edit): ?>
   <a href="project_progress.php?project_id=<?= $projectId ?>" class="boq-add-link">&larr; Cancel Edit</a>
   <?php else: ?>
   <a href="#" class="boq-add-link" id="boqAddLink" onclick="toggleBoqForm();return false">&#43; Add BOQ Item</a>
   <?php endif; ?>
  </div>
  <h2><?= e($project['project_name']) ?></h2>
  <p><?= e($project['location']) ?> &middot; <?= e($project['client']) ?> &middot; Project Manager: <?= e($project['project_manager'] ?? '') ?></p>
 </div>
 <div class="overall-box"><span>Weighted Actual Progress</span><strong><?= $overall ?>%</strong><div class="progress-bar"><i style="width:<?= $overall ?>%"></i></div><small>Planned: <?= $planned ?>% &middot; Variance: <?= $variance>0?'+':'' ?><?= $variance ?>%</small></div>
</div>
<div class="export-bar">
 <a href="report_pdf.php?project_id=<?= $projectId ?>" class="export-link export-pdf" target="_blank" rel="noopener noreferrer">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
  View Report as PDF
 </a>
 <a href="export_excel.php?project_id=<?= $projectId ?>" class="export-link export-excel">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 10h16"/><path d="M4 16h16"/><path d="M10 4v16"/></svg>
  Export to Excel
 </a>
</div>

<div class="boq-form-slide" id="boqFormSlide" style="<?= $edit ? '' : 'display:none' ?>">
 <div class="panel boq-form-panel">
  <div class="panel-head boq-form-panel-head">
   <div class="boq-title-row"><img src="assets/img/mark-64.png" alt="MEP" class="boq-title-logo"><div><h2><?= $edit ? 'Edit BOQ Item' : 'MEP Project BOQ' ?></h2><p class="muted"><?= $edit ? 'Update the selected work item.' : 'Work Item Registration' ?></p></div></div>
   <button type="button" class="btn ghost boq-close-btn" onclick="toggleBoqForm()">&times; Close</button>
  </div>
  
<form method="post" id="boqForm" class="boq-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">

        <div class="boq-form-row">
            <label>Discipline <span class="req" style="color:#EF4444;">*</span>
                <input type="text" name="discipline" value="<?= e($edit['discipline'] ?? '') ?>" placeholder="e.g., HVAC, Electrical, Plumbing" required>
            </label>
            <label>BOQ Ref No. <small>(Optional)</small>
                <input type="text" name="boq_no" value="<?= e($edit['boq_no'] ?? '') ?>" placeholder="e.g., 8A.01.01">
            </label>
            <label>Priority <small>(Optional)</small>
                <select name="priority">
                    <option value="">-- Select --</option>
                    <option value="High" <?= ($edit['priority'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                    <option value="Medium" <?= ($edit['priority'] ?? '') === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="Low" <?= ($edit['priority'] ?? '') === 'Low' ? 'selected' : '' ?>>Low</option>
                </select>
            </label>
        </div>

        <label>Task / Activity Name <span class="req" style="color:#EF4444;">*</span>
            <input type="text" name="task" value="<?= e($edit['task'] ?? '') ?>" placeholder="e.g., Supply and install Chilled Water Pumps" required>
        </label>

        <label>Task Description <small>(Optional — structured for GridView)</small>
            <textarea name="material_description" rows="8" placeholder="Objective:&#10;Key Deliverables:&#10;Requirements/Specifications:&#10;Remarks/References:&#10;Requirements:&#10;Acceptance Criteria:"><?= e($edit['material_description'] ?? '') ?></textarea>
        </label>

        <div class="boq-measurement-grid">
            <label>Item Type
                <select name="item_type" id="boqItemType">
                    <option <?= ($edit['item_type'] ?? 'Measurable Item') === 'Heading' ? 'selected' : '' ?>>Heading</option>
                    <option <?= ($edit['item_type'] ?? '') === 'Group' ? 'selected' : '' ?>>Group</option>
                    <option <?= ($edit['item_type'] ?? 'Measurable Item') === 'Measurable Item' ? 'selected' : '' ?>>Measurable Item</option>
                </select>
            </label>
            <label>Measurement Profile
                <select name="measurement_profile" id="boqMeasurementProfile">
                    <option value="Manual" <?= ($edit['measurement_profile'] ?? 'Manual') === 'Manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="Profile A (Multi-Stage)" <?= ($edit['measurement_profile'] ?? '') === 'Profile A (Multi-Stage)' ? 'selected' : '' ?>>Profile A (Multi-Stage)</option>
                    <option value="Profile B (Single-Stage)" <?= ($edit['measurement_profile'] ?? '') === 'Profile B (Single-Stage)' ? 'selected' : '' ?>>Profile B (Single-Stage)</option>
                    <option value="Profile C (T&C Only)" <?= ($edit['measurement_profile'] ?? '') === 'Profile C (T&C Only)' ? 'selected' : '' ?>>Profile C (T&C Only)</option>
                    <option value="Profile D (Quantity-Based)" <?= ($edit['measurement_profile'] ?? '') === 'Profile D (Quantity-Based)' ? 'selected' : '' ?>>Profile D (Quantity-Based)</option>
                </select>
            </label>
            <label>Activity Weight
                <input id="boqActivityWeight" type="number" min="0.01" max="100" step="0.01" name="activity_weight" value="<?= e((string)($edit['activity_weight'] ?? 1)) ?>">
            </label>
            <label>Planned Progress %
                <input id="boqPlannedProgress" type="number" min="0" max="100" step="0.01" name="planned_percentage" value="<?= e(number_format((float)($edit['planned_percentage'] ?? 0),2,'.','')) ?>">
            </label>
        </div>

        <div class="boq-form-row">
            <label>Material Qty <small>(Optional)</small>
                <input type="number" step="0.01" min="0" name="material_quantity" value="<?= e((string)($edit['material_quantity'] ?? '')) ?>">
            </label>
            <label>Unit <small>(Optional)</small>
                <input type="text" name="unit" value="<?= e($edit['unit'] ?? '') ?>" placeholder="e.g., m, No., Set">
            </label>
            <label>Planned Start <small>(Optional)</small>
                <input type="date" name="planned_start_date" value="<?= e($edit['planned_start_date'] ?? '') ?>">
            </label>
            <label>Planned End <small>(Optional)</small>
                <input type="date" name="planned_end_date" value="<?= e($edit['planned_end_date'] ?? '') ?>">
            </label>
        </div>

        <div class="boq-form-row">
            <label>Actual Start <small>(Optional)</small>
                <input type="date" name="actual_start_date" value="<?= e($edit['actual_start_date'] ?? '') ?>">
            </label>
            <label>Installation Start <small>(Optional)</small>
                <input type="date" name="installation_start_date" value="<?= e($edit['installation_start_date'] ?? '') ?>">
            </label>
            <label>Actual End <small>(Optional)</small>
                <input type="date" name="actual_end_date" value="<?= e($edit['actual_end_date'] ?? '') ?>">
            </label>
        </div>

        <label>Notes / Remarks <small>(Optional)</small>
            <textarea name="notes" rows="2"><?= e($edit['notes'] ?? '') ?></textarea>
        </label>

        <div class="form-actions boq-form-actions">
            <button class="btn" type="submit"><?= $edit ? 'Update BOQ Item' : 'Add BOQ Item' ?></button>
            <a class="btn ghost" href="project_progress.php?project_id=<?= $projectId ?>">Cancel</a>
        </div>
    </form>
  </div>
</div>

<section class="panel" id="tracker">
 <div class="panel-head">
  <div><h2>Project BOQ Item List</h2><p class="muted">Group by Discipline. Priority, Percentage, Status and Remarks can be updated directly in the table.</p></div>
  <form class="search" method="get">
   <input type="hidden" name="project_id" value="<?= $projectId ?>">
   <select name="discipline"><option value="">ALL DISCIPLINES</option><?php foreach($disciplineSummary as $d):?><option <?= $discFilter===$d['discipline']?'selected':'' ?>><?= e($d['discipline']) ?></option><?php endforeach;?></select>
   <button class="btn">Filter</button>
  </form>
 </div>
 <div class="tracker-summary-bar">
  <div><span>Measurable Items</span><strong><?= $summary['measurable'] ?></strong></div>
  <div class="summary-complete"><span>Completed</span><strong><?= $summary['complete'] ?></strong></div>
  <div class="summary-active"><span>In Progress</span><strong><?= $summary['in_progress'] ?></strong></div>
  <div class="summary-behind"><span>Behind Plan</span><strong><?= $summary['behind'] ?></strong></div>
  <div><span>Planned / Actual</span><strong><?= $planned ?>% / <?= $overall ?>%</strong></div>
  <div class="<?= $variance<0?'summary-behind':'summary-complete' ?>"><span>Variance</span><strong><?= $variance>0?'+':'' ?><?= $variance ?>%</strong></div>
 </div>
 <div class="tracker-view-controls">
  <button type="button" class="btn ghost tracker-view-btn" data-view-mode="compact">Compact View</button>
  <button type="button" class="btn ghost tracker-view-btn" data-view-mode="standard">Standard View</button>
  <button type="button" class="btn ghost tracker-view-btn" data-view-mode="full">Full View</button>
  <span class="tracker-view-sep"></span>
  <button type="button" class="btn ghost tracker-collapse-btn" id="collapseAllBtn" title="Collapse All Disciplines">
   <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 12 6 20 14"/></svg>
   Collapse All
  </button>
  <button type="button" class="btn ghost tracker-collapse-btn" id="expandAllBtn" title="Expand All Disciplines">
   <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 10 12 18 20 10"/></svg>
   Expand All
  </button>
  <span class="muted compact-note" id="trackerViewStatus">Current view: Standard View</span>
 </div>
 <div class="discipline-cards"><?php foreach($disciplineSummary as $d):?><div class="discipline-card"><strong><?= e($d['discipline']) ?></strong><span><?= (int)$d['progress'] ?>%</span><div class="progress-bar"><i style="width:<?= (int)$d['progress'] ?>%"></i></div><small><?= (int)$d['total'] ?> task(s)</small></div><?php endforeach;?><?php if(!$disciplineSummary):?><p class="empty">No progress entries yet.</p><?php endif;?></div>

 <?php $current=''; foreach($rows as $r):
 if($current!==$r['discipline']):
   if($current!=='') echo '</tbody></table></div></div>';
   $current=$r['discipline'];
   $sum=array_values(array_filter($disciplineSummary,fn($d)=>$d['discipline']===$current));
   $dp=$sum?(int)$sum[0]['progress']:0;
 ?>
 <div class="discipline-group">
  <div class="discipline-title" onclick="toggleDiscipline(this)" style="cursor:pointer"><h3><span class="disc-toggle">&#9660;</span> <?= e($current) ?></h3><span>Discipline Total Percentage Progress: <strong><?= $dp ?>%</strong></span></div>
  <div class="tracker-table-scroll">
   <table class="tracker-table-v5 is-standard" data-view="standard">
    <colgroup>
     <col style="width:105px" data-col="priority"><col style="width:95px" data-col="boq"><col style="width:200px" data-col="task"><col style="width:420px" data-col="description"><col style="width:85px" data-col="qty"><col style="width:65px" data-col="unit"><col style="width:285px" data-col="timeline"><col style="width:115px" data-col="total-days"><col style="width:220px" data-col="progress"><col style="width:135px" data-col="status"><col style="width:210px" data-col="notes"><col style="width:210px" data-col="remarks"><col style="width:140px" data-col="actions">
    </colgroup>
    <thead><tr>
     <th data-col="priority">Priority</th><th data-col="boq">BOQ No.</th><th data-col="task">Task</th><th data-col="description">Task Description</th><th data-col="qty">Material Quantity</th><th data-col="unit">Unit</th>
     <th data-col="timeline">Timeline</th><th data-col="total-days">Total Days</th><th data-col="progress">Planned vs Actual</th><th data-col="status">Status</th><th data-col="notes">Notes</th><th data-col="remarks">Remarks</th><th data-col="actions">Actions</th>
    </tr></thead><tbody>
 <?php endif; $formId='rowform-'.$r['id']; ?>
    <tr class="item-row item-type-<?= strtolower(str_replace(' ','-',e($r['item_type']??'Measurable Item'))) ?>" data-item-type="<?= e($r['item_type']??'Measurable Item') ?>">
     <td class="center-cell" data-col="priority" data-label="Priority"><select form="<?= $formId ?>" name="priority" class="<?= priority_class($r['priority']) ?>"><?php foreach($priorities as $p):?><option <?= $p===$r['priority']?'selected':'' ?>><?= e($p) ?></option><?php endforeach;?></select></td>
     <td class="center-cell" data-col="boq" data-label="BOQ No."><?= e($r['boq_no']) ?></td>
     <td class="text-cell" data-col="task" data-label="Task"><strong><?= e($r['task']) ?></strong><?php if(($r['measurement_profile']??'Manual')!=='Manual'): ?><span class="badge-sm" style="display:block;margin-top:4px;font-size:10px;background:#eef2f6;color:#64748b;padding:2px 6px;border-radius:4px;width:max-content" title="Progress is automated by <?= e($r['measurement_profile']) ?>">&#9881; Auto</span><?php endif; ?></td>
     <td class="text-cell task-description-part" data-col="description" data-label="Task Description">
      <div class="task-description-lines">
       <span class="task-description-line"><b>Objective:</b><em><?= e($r['description_parts']['objective'] ?: '—') ?></em></span>
       <span class="task-description-line"><b>Key Deliverables:</b><em><?= e($r['description_parts']['deliverables'] ?: '—') ?></em></span>
       <span class="task-description-line"><b>Requirements/Specifications:</b><em><?= e($r['description_parts']['specifications'] ?: '—') ?></em></span>
       <span class="task-description-line"><b>Remarks/References:</b><em><?= e($r['description_parts']['references'] ?: '—') ?></em></span>
       <span class="task-description-line"><b>Requirements:</b><em><?= e($r['description_parts']['requirements'] ?: '—') ?></em></span>
       <span class="task-description-line"><b>Acceptance Criteria:</b><em><?= e($r['description_parts']['acceptance'] ?: '—') ?></em></span>
      </div>
     </td>
     <td class="center-cell" data-col="qty" data-label="Material Quantity"><?= $r['material_quantity']!==null?e(rtrim(rtrim(number_format((float)$r['material_quantity'],2,'.',''),'0'),'.')):'-' ?></td>
     <td class="center-cell" data-col="unit" data-label="Unit"><?= e($r['unit']) ?></td>
     <td class="text-cell timeline-cell-v5" data-col="timeline" data-label="Timeline"><span class="timeline-line"><b>Planned</b><time><?= e($r['planned_start_date']?:'-') ?></time><em class="timeline-to">to</em><time><?= e($r['planned_end_date']?:'-') ?></time></span><span class="timeline-line"><b>Actual</b><time><?= e($r['actual_start_date']?:'-') ?></time><em class="timeline-to">to</em><time><?= e($r['actual_end_date']?:'In Progress') ?></time></span><small><?= e($r['start_date_source']??'Not Set') ?><?= !empty($r['start_source_reference'])?' · '.e($r['start_source_reference']):'' ?></small></td>
     <td class="center-cell total-days-cell" data-col="total-days" data-label="Total Days"><?php $plannedDays=total_days($r['planned_start_date'],$r['planned_end_date']); ?><strong><?= e($plannedDays) ?></strong><?php if($plannedDays!=='-'): ?><small>day<?= $plannedDays==='1'?'':'s' ?></small><?php endif; ?></td>
     <?php $rowActual=(float)$r['percentage_complete'];$rowPlanned=(float)$r['planned_percentage'];$rowVariance=round($rowActual-$rowPlanned,2); $canEditInlinePct = $canEditPct && ($r['measurement_profile'] ?? 'Manual') === 'Manual'; ?>
     <td class="center-cell progress-compare" data-col="progress" data-label="Planned vs Actual"><div class="dual-progress"><i style="width:<?= $rowActual ?>%"></i><b style="left:<?= $rowPlanned ?>%" title="Planned <?= number_format($rowPlanned,2) ?>%"></b></div><div class="progress-values"><span>P <?= number_format($rowPlanned,2) ?>%</span><span>A <?= number_format($rowActual,2) ?>%</span><em class="<?= $rowVariance<0?($rowVariance<=-5?'variance-red':'variance-amber'):'variance-green' ?>"><?= $rowVariance>0?'+':'' ?><?= number_format($rowVariance,2) ?>%</em></div><div class="pct-editor <?= pct_class($rowActual) ?>"><input form="<?= $formId ?>" type="number" name="percentage_complete" min="0" max="100" step="0.01" value="<?= number_format($rowActual,2,'.','') ?>" <?= $canEditInlinePct?'':'readonly title="Automated by Measurement Profile"' ?>><b>%</b></div></td>
     <td class="center-cell" data-col="status" data-label="Status"><select form="<?= $formId ?>" name="status" class="<?= status_class($r['status']) ?>"><?php foreach($statuses as $statusOption):?><option <?= $statusOption===$r['status']?'selected':'' ?>><?= e($statusOption) ?></option><?php endforeach;?></select></td>
     <td class="text-cell" data-col="notes" data-label="Notes"><textarea form="<?= $formId ?>" name="notes" placeholder="Enter notes" class="notes-cell-textarea"><?= e($r['notes']??'') ?></textarea></td>
     <td class="remarks-cell-v5" data-col="remarks" data-label="Remarks"><textarea form="<?= $formId ?>" name="remarks" placeholder="Enter remarks"><?= e($r['remarks']??'') ?></textarea></td>
     <td class="actions-cell" data-col="actions" data-label="Actions">
      <form method="post" id="<?= $formId ?>" class="tracker-row-form">
       <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="project_id" value="<?= $projectId ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="inline_discipline" value="<?= e($discFilter) ?>">
       <?php $vd=json_encode(['d'=>$r['discipline'],'p'=>$r['priority'],'b'=>$r['boq_no']??'','t'=>$r['task'],'obj'=>$r['description_parts']['objective'],'del'=>$r['description_parts']['deliverables'],'spec'=>$r['description_parts']['specifications'],'ref'=>$r['description_parts']['references'],'req'=>$r['description_parts']['requirements'],'acc'=>$r['description_parts']['acceptance'],'mq'=>$r['material_quantity'],'u'=>$r['unit']??'','pc'=>(float)$r['percentage_complete'],'s'=>$r['status'],'n'=>$r['notes']??'','rm'=>$r['remarks']??''],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES); ?>
       <button class="act-icon act-view" type="button" title="View" data-view="<?= e($vd) ?>" onclick="openBoqView(this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
       </button>
       <button class="act-icon act-update" type="submit" name="action" value="inline_update" title="Update">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
       </button>
       <a class="act-icon act-edit" href="?project_id=<?= $projectId ?>&edit_id=<?= $r['id'] ?>" title="Edit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
       </a>
       <?php if (has_role('admin')): ?>
       <button class="act-icon act-delete" type="submit" name="action" value="delete_progress" title="Delete" onclick="return confirm('Delete this tracker item?')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
       </button>
       <?php endif; ?>
      </form>
     </td>
    </tr>
 <?php endforeach; if($current!=='') echo '</tbody></table></div></div>'; if(!$rows):?><p class="empty">No tracker rows match this project/filter.</p><?php endif;?>
</section>

<div id="boqViewOverlay" class="boq-view-overlay" style="display:none" onclick="if(event.target===this)closeBoqView()">
 <div class="boq-view-modal">
  <div class="boq-view-header">
   <div class="boq-view-title-row">
    <img src="assets/img/mark-64.png" alt="MEP" class="boq-view-logo">
    <div><h2>BOQ Line Item View</h2><p class="boq-view-project"><?= e($project['project_name']) ?> &middot; <?= e($project['location']) ?> &middot; <?= e($project['client']) ?></p></div>
   </div>
   <div class="boq-view-header-actions no-print">
    <button type="button" class="btn boq-print-btn" onclick="printBoqView()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Print / Save as PDF</button>
    <button type="button" class="btn ghost" onclick="closeBoqView()">&times; Close</button>
   </div>
  </div>
  <div class="boq-view-body" id="boqViewGrid"></div>
 </div>
</div>

<style>
/* Context nav links */
.context-nav-links{display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap}
.context-nav-sep{color:#94a3b8;font-size:14px}
.boq-add-link{display:inline-flex;align-items:center;gap:4px;color:#0D9488;font-weight:700;font-size:14px;text-decoration:none;padding:4px 12px;border:2px solid #0D9488;border-radius:6px;transition:all .2s}
.boq-add-link:hover{background:#0D9488;color:#fff}
.boq-add-link.is-active{background:#EF4444;border-color:#EF4444;color:#fff}
.boq-add-link.is-active:hover{background:#DC2626;border-color:#DC2626}

/* Export bar */
.export-bar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.export-link{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:6px;font-weight:600;font-size:13px;text-decoration:none;transition:all .2s;border:1px solid}
.export-pdf{color:#DC2626;border-color:#FECACA;background:#FEF2F2}
.export-pdf:hover{background:#DC2626;color:#fff;border-color:#DC2626}
.export-excel{color:#16A34A;border-color:#BBF7D0;background:#F0FDF4}
.export-excel:hover{background:#16A34A;color:#fff;border-color:#16A34A}

/* BOQ slide-down form */
.boq-form-slide{overflow:hidden}
.boq-form-panel{border:2px solid #0D9488;border-radius:12px}
.boq-form-panel-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.boq-close-btn{font-size:16px;font-weight:700;color:#64748b}
.boq-close-btn:hover{color:#EF4444}

/* Grouped BOQ entry form */
.boq-entry-grid{grid-template-columns:1fr;gap:16px}
.boq-field-group{min-width:0;margin:0;padding:16px 18px 18px;border:1px solid #d8e2ea;border-radius:12px;background:#fbfdff}
.boq-field-group legend{padding:0 8px;color:#12304a;font-size:14px;font-weight:800}
.boq-field-group legend span{margin-right:10px}
.boq-group-help{margin:-2px 0 13px;color:#64748b;font-size:12px;line-height:1.45}
.boq-group-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px 20px}
.boq-measurement-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.boq-span-2{grid-column:1/-1}
.boq-field-hint{color:#718096;font-size:11px;font-weight:500;line-height:1.35}
.boq-group-measurement{border-color:#9edbd4;background:#f4fcfa}
.boq-group-material{background:#fffdf8}
.boq-field-group textarea{min-height:92px}
.boq-field-disabled{opacity:.6}
.boq-field-disabled input{background:#eef2f5;cursor:not-allowed}
.boq-notes-field{grid-row:span 2}
.boq-notes-field textarea{min-height:120px;height:100%;resize:vertical}
.boq-form-actions{grid-column:1/-1;justify-content:flex-end}

/* Priority field with visual indicator */
.priority-field-wrap{display:flex;flex-direction:column;gap:0}
.priority-label-row{display:flex;align-items:center;gap:0;margin-bottom:2px}
.priority-label-text{font-weight:600;font-size:14px;color:#172033;white-space:nowrap;margin-right:auto}
.priority-icons{display:flex;align-items:center;gap:12px}
.pvi-item{display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;white-space:nowrap}
.pvi-low{color:#22C55E}
.pvi-medium{color:#F59E0B}
.pvi-high{color:#EF4444}
.pvi-gradient-bar{height:5px;border-radius:3px;margin-bottom:6px;background:linear-gradient(to right,#22C55E 0%,#86EFAC 18%,#FBBF24 38%,#F59E0B 55%,#FB923C 72%,#EF4444 100%)}
.priority-field-wrap select{width:100%;box-sizing:border-box}

/* BOQ form title with logo */
.boq-title-row{display:flex;align-items:center;gap:12px}
.boq-title-logo{width:44px;height:44px;border-radius:10px;object-fit:contain}

/* Discipline collapse toggle */
.disc-toggle{font-size:12px;margin-right:4px;display:inline-block;transition:transform .2s}
.discipline-title.collapsed .disc-toggle{transform:rotate(-90deg)}
.discipline-title:hover{opacity:.92}

.tracker-table-scroll{width:100%;overflow-x:auto;border:1px solid #e5e7eb;border-radius:10px}
.tracker-table-v5{width:100%;min-width:2285px;border-collapse:collapse;table-layout:fixed;background:#fff}
.tracker-table-v5 thead th{position:sticky;top:0;z-index:3;background:#f8fafc;color:#172033;font-weight:700;text-align:center;vertical-align:middle;padding:11px 8px;border-right:1px solid #e5e7eb;border-bottom:2px solid #cbd5e1;white-space:normal}
.tracker-table-v5 tbody td{padding:8px;vertical-align:middle;border-right:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;overflow-wrap:anywhere;box-sizing:border-box}
.tracker-table-v5 th:last-child,.tracker-table-v5 td:last-child{border-right:0}
.tracker-table-v5 .center-cell{text-align:center}.tracker-table-v5 .text-cell{text-align:left}
.task-description-lines,.bv-task-description{display:block;width:100%}.task-description-line{display:grid;grid-template-columns:155px minmax(0,1fr);gap:8px;padding:3px 0;border-bottom:1px dashed #e2e8f0;line-height:1.45}.task-description-line:last-child{border-bottom:0}.task-description-line b{font-size:11px;color:#334155}.task-description-line em{white-space:pre-line;font-style:italic;font-weight:400;color:#475569}
.tracker-table-v5 select,.tracker-table-v5 input[type="date"],.tracker-table-v5 input[type="number"],.tracker-table-v5 textarea{width:100%;max-width:100%;box-sizing:border-box}
.remarks-cell-v5 textarea{min-height:62px;resize:vertical;background:#fff;color:#172033}
.notes-cell-textarea{min-height:62px;resize:vertical;background:#fff;color:#172033}
.actions-cell{text-align:center}.tracker-row-form{display:flex;flex-direction:row;gap:4px;align-items:center;justify-content:center}
/* Action icon buttons */
.act-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:28px;border-radius:5px;border:1px solid transparent;text-decoration:none;transition:all .15s;cursor:pointer;position:relative;background:none;padding:0;font-size:0;line-height:1}
.act-icon svg{flex-shrink:0}
.act-update{color:#22C55E;border-color:#86efac}
.act-update:hover{background:#F0FDF4;border-color:#22C55E}
.act-edit{color:#F59E0B;border-color:#fde68a}
.act-edit:hover{background:#FFFBEB;border-color:#F59E0B}
.act-view{color:#3B82F6;border-color:#93c5fd}
.act-view:hover{background:#EFF6FF;border-color:#3B82F6}
.act-delete{color:#EF4444;border-color:#fca5a5}
.act-delete:hover{background:#FEF2F2;border-color:#EF4444}
/* Tooltip */
.act-icon[title]{position:relative}
.act-icon[title]:hover::after{content:attr(title);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);padding:4px 10px;background:#1e293b;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;border-radius:5px;pointer-events:none;z-index:10}
.act-icon[title]:hover::before{content:'';position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1e293b;pointer-events:none;z-index:10}
.tracker-table-v5 .pct-editor{display:flex;align-items:center;justify-content:center;gap:3px;border-radius:6px;padding:3px}
.tracker-table-v5 .pct-editor input{text-align:center;min-width:0}
.tracker-summary-bar{display:grid;grid-template-columns:repeat(6,minmax(120px,1fr));gap:10px;margin:14px 0}
.tracker-summary-bar>div{padding:12px 14px;border:1px solid #dbe4ec;border-radius:10px;background:#f8fafc}
.tracker-summary-bar span{display:block;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase}.tracker-summary-bar strong{display:block;margin-top:4px;color:#172033;font-size:20px}.tracker-summary-bar .summary-complete{background:#f0fdf4;border-color:#bbf7d0}.tracker-summary-bar .summary-active{background:#eff6ff;border-color:#bfdbfe}.tracker-summary-bar .summary-behind{background:#fef2f2;border-color:#fecaca}
.timeline-cell-v5{font-variant-numeric:tabular-nums}.timeline-cell-v5 .timeline-line{display:grid;grid-template-columns:50px 1fr 24px 1fr;align-items:center;gap:5px;line-height:1.45;white-space:nowrap}.timeline-cell-v5 .timeline-line b{font-size:11px;color:#475569}.timeline-cell-v5 .timeline-line time{text-align:center}.timeline-cell-v5 .timeline-to{font-size:13px;font-weight:900;font-style:normal;color:#0d9488;text-align:center;text-transform:lowercase}.timeline-cell-v5 small{display:block;margin-top:5px;color:#64748b;line-height:1.35;white-space:normal}.total-days-cell{white-space:nowrap!important;font-variant-numeric:tabular-nums}.total-days-cell strong{display:block;font-size:18px;line-height:1.1;color:#172033}.total-days-cell small{display:block;margin-top:3px;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.03em}
.dual-progress{position:relative;height:12px;border-radius:999px;background:#e5e7eb;overflow:visible;margin:4px 4px 7px}.dual-progress i{display:block;height:100%;border-radius:999px;background:#0d9488}.dual-progress b{position:absolute;top:-4px;width:3px;height:20px;background:#172033;transform:translateX(-1px);border-radius:2px}.progress-values{display:flex;justify-content:space-between;gap:4px;font-size:10px;font-weight:700}.progress-values em{font-style:normal}.variance-green{color:#15803d}.variance-amber{color:#d97706}.variance-red{color:#dc2626}
.item-type-heading td{background:#17324d!important;color:#fff;border-color:#294965!important}.item-type-heading td:not(.col-hidden),.item-type-group td:not(.col-hidden){display:table-cell}.item-type-heading [data-col="task"]{font-size:14px}.item-type-heading textarea,.item-type-heading input{color:#172033}.item-type-heading .task-description-line b,.item-type-heading .task-description-line em{color:#fff}.item-type-group td{background:#e8f2f5;font-weight:700}
@media(min-width:901px){.tracker-table-v5 th[data-col="boq"],.tracker-table-v5 td[data-col="boq"]{position:sticky;left:0;z-index:2;background:inherit}.tracker-table-v5 th[data-col="task"],.tracker-table-v5 td[data-col="task"]{position:sticky;left:95px;z-index:2;background:inherit;box-shadow:2px 0 3px rgba(15,23,42,.08)}.tracker-table-v5 thead th[data-col="boq"],.tracker-table-v5 thead th[data-col="task"]{z-index:5;background:#f8fafc}}


.tracker-view-controls{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:10px 0 14px}
.tracker-view-controls .compact-note{font-size:12px}
.tracker-view-sep{width:1px;height:22px;background:#cbd5e1;margin:0 4px}
.tracker-collapse-btn{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600}

.tracker-view-btn.active-view{font-weight:700;outline:2px solid currentColor;outline-offset:1px}
.tracker-table-v5.is-compact{min-width:1105px}
.tracker-table-v5.is-standard{min-width:1865px}
.tracker-table-v5.is-full{min-width:2285px}
.tracker-table-v5 .col-hidden{display:none !important}

/* Responsive tracker: table + horizontal scroll down to tablet width, */
/* then a stacked "card per row" layout on phones so nothing requires zooming. */
@media (max-width: 900px) {
    .tracker-summary-bar{grid-template-columns:repeat(3,1fr)}
    .tracker-table-scroll{overflow-x:auto}
    .tracker-table-v5{min-width:1100px}
    .tracker-table-v5.is-compact{min-width:1000px}
    .tracker-table-v5.is-standard{min-width:1750px}
    .tracker-table-v5.is-full{min-width:2150px}
}
@media (max-width: 760px) {
    .boq-entry-grid{grid-template-columns:1fr}
    .boq-group-grid,.boq-measurement-grid{grid-template-columns:1fr}
    .boq-span-2{grid-column:span 1}
    .boq-notes-field{grid-row:span 1}
}
@media (max-width: 700px) {
    .tracker-summary-bar{grid-template-columns:repeat(2,1fr)}
    .tracker-table-scroll{overflow-x:visible;border:0}
    .tracker-table-v5,
    .tracker-table-v5.is-compact,
    .tracker-table-v5.is-standard,
    .tracker-table-v5.is-full{width:100%;min-width:0;table-layout:auto}
    .tracker-table-v5 thead{display:none}
    .tracker-table-v5 tbody{display:block}
    .tracker-table-v5 tr{display:block;margin-bottom:14px;border:1px solid #e2e7ef;border-radius:12px;padding:10px 12px;background:#fff;box-shadow:0 5px 20px rgba(25,43,72,.035)}
    .tracker-table-v5 td{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;border:0;border-bottom:1px solid #f1f4f9;padding:9px 2px;overflow-wrap:anywhere}
    .tracker-table-v5 .item-type-heading td:not(.col-hidden),
    .tracker-table-v5 .item-type-group td:not(.col-hidden){display:flex}
    .tracker-table-v5 td:last-child{border-bottom:0}
    .tracker-table-v5 td.col-hidden{display:none !important}
    .tracker-table-v5 td::before{content:attr(data-label);flex:0 0 40%;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.03em;color:#8a95a8;padding-top:9px}
    .tracker-table-v5 td > *{flex:1 1 auto;min-width:0}
    .tracker-table-v5 .center-cell,.tracker-table-v5 .text-cell{text-align:right}
    .tracker-table-v5 select,.tracker-table-v5 input[type="date"],.tracker-table-v5 input[type="number"],.tracker-table-v5 textarea{width:auto;min-width:0}
    .actions-cell{justify-content:flex-end}
    .tracker-row-form{flex-direction:row;flex-wrap:wrap;justify-content:flex-end}
    .discipline-title{flex-direction:column;align-items:flex-start;gap:4px}
}

/* BOQ View Modal */
.boq-view-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.55);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}
.boq-view-modal{background:#fff;border-radius:14px;box-shadow:0 25px 60px rgba(0,0,0,.18);width:100%;max-width:780px;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column}
.boq-view-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:24px 28px 16px;border-bottom:2px solid #0D9488;flex-wrap:wrap}
.boq-view-title-row{display:flex;align-items:center;gap:14px}
.boq-view-logo{width:48px;height:48px;border-radius:10px;object-fit:contain}
.boq-view-header h2{font-size:18px;margin:0;color:#172033}
.boq-view-project{font-size:13px;color:#64748b;margin:2px 0 0}
.boq-view-header-actions{display:flex;align-items:center;gap:8px}
.boq-print-btn{display:inline-flex;align-items:center;gap:6px}
.boq-view-body{padding:24px 28px 28px}
.bv-row{display:grid;grid-template-columns:1fr 1fr;gap:0 24px}
.bv-row.bv-full{grid-template-columns:1fr}
.bv-field{padding:14px 0;border-bottom:1px solid #f1f5f9}
.bv-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;margin-bottom:5px}
.bv-val{display:block;font-size:15px;color:#172033;font-weight:500}
.bv-multi{white-space:pre-wrap;font-size:14px;font-weight:400;line-height:1.6}
.bv-description{font-style:italic;color:#475569}
.bv-badge{display:inline-block;padding:3px 12px;border-radius:6px;font-size:13px;font-weight:600}
.bv-pri-high{background:#FEF2F2;color:#DC2626}
.bv-pri-medium{background:#FFFBEB;color:#D97706}
.bv-pri-low{background:#F0FDF4;color:#16A34A}
.bv-pri-complete{background:#F0FDF4;color:#16A34A}
.bv-pct-wrap{display:flex;align-items:center;gap:12px}
.bv-pct-wrap .progress-bar{flex:1}
.bv-pct-wrap strong{font-size:16px;color:#172033;min-width:44px;text-align:right}
@media print{
  body *{visibility:hidden!important}
  #boqViewOverlay,#boqViewOverlay *{visibility:visible!important}
  #boqViewOverlay{position:fixed;top:0;left:0;width:100%;height:auto;background:#fff!important;padding:0!important;z-index:99999}
  .boq-view-modal{box-shadow:none!important;max-height:none!important;overflow:visible!important;max-width:100%!important;border-radius:0!important}
  .no-print{display:none!important}
  .boq-view-header{border-bottom:2px solid #172033}
}
@media(max-width:600px){
  .boq-view-modal{border-radius:10px}
  .boq-view-header,.boq-view-body{padding:16px 18px}
  .bv-row{grid-template-columns:1fr}
}
</style>
<script>
function toggleBoqForm(){
  const slide=document.getElementById('boqFormSlide');
  const link=document.getElementById('boqAddLink');
  if(!slide)return;
  const isOpen=slide.style.display!=='none';
  if(isOpen){
    slide.style.display='none';
    if(link){link.classList.remove('is-active');link.innerHTML='&#43; Add BOQ Item';}
  }else{
    slide.style.display='';
    if(link){link.classList.add('is-active');link.innerHTML='&#10005; Close Form';}
    const discipline=slide.querySelector('select[name="discipline"]');
    if(discipline)discipline.focus();
    slide.scrollIntoView({behavior:'smooth',block:'start'});
  }
}

document.querySelectorAll('.tracker-table-v5 select[name="priority"]').forEach(el=>el.addEventListener('change',()=>{el.className='priority-'+el.value.toLowerCase()}));
document.querySelectorAll('.tracker-table-v5 input[name="percentage_complete"]').forEach(el=>el.addEventListener('input',()=>{const p=Math.max(0,Math.min(100,+el.value||0)),box=el.closest('.pct-editor');box.className='pct-editor '+(p>=100?'pct-100':p>=76?'pct-76':p>=51?'pct-51':p>=26?'pct-26':'pct-0')}));

const trackerViewColumns = {
  compact: ['description','qty','unit','timeline','total-days','remarks'],
  standard: ['notes','remarks'],
  full: []
};

function toggleDiscipline(el){
  const group=el.closest('.discipline-group');
  const scroll=group.querySelector('.tracker-table-scroll');
  const icon=el.querySelector('.disc-toggle');
  if(scroll.style.display==='none'){
    scroll.style.display='';
    icon.innerHTML='&#9660;';
    el.classList.remove('collapsed');
  }else{
    scroll.style.display='none';
    icon.innerHTML='&#9654;';
    el.classList.add('collapsed');
  }
}

function applyTrackerView(mode) {
  if (!trackerViewColumns.hasOwnProperty(mode)) mode = 'standard';

  document.querySelectorAll('.tracker-table-v5').forEach(table => {
    table.dataset.view = mode;
    table.classList.remove('is-compact','is-standard','is-full');
    table.classList.add('is-'+mode);
    table.querySelectorAll('[data-col]').forEach(el => el.classList.remove('col-hidden'));
    trackerViewColumns[mode].forEach(key => table.querySelectorAll(`[data-col="${key}"]`).forEach(el => el.classList.add('col-hidden')));
  });

  const status = document.getElementById('trackerViewStatus');
  document.querySelectorAll('[data-view-mode]').forEach(btn => {const active=btn.dataset.viewMode===mode;btn.classList.toggle('active-view',active);btn.setAttribute('aria-pressed',active?'true':'false')});
  if (status) status.textContent = 'Current view: '+mode.charAt(0).toUpperCase()+mode.slice(1)+' View';

  try {
    localStorage.setItem('mepTrackerView', mode);
  } catch (e) {}
}

document.querySelectorAll('[data-view-mode]').forEach(btn=>btn.addEventListener('click',()=>applyTrackerView(btn.dataset.viewMode)));

let initialTrackerView = 'standard';
try {
  const savedTrackerView = localStorage.getItem('mepTrackerView');
  if (trackerViewColumns.hasOwnProperty(savedTrackerView)) {
    initialTrackerView = savedTrackerView;
  }
} catch (e) {}

applyTrackerView(initialTrackerView);

// Collapse All / Expand All discipline groups
document.getElementById('collapseAllBtn').addEventListener('click', function() {
  document.querySelectorAll('.discipline-group').forEach(group => {
    const scroll = group.querySelector('.tracker-table-scroll');
    const title = group.querySelector('.discipline-title');
    const icon = title.querySelector('.disc-toggle');
    if (scroll && scroll.style.display !== 'none') {
      scroll.style.display = 'none';
      icon.innerHTML = '&#9654;';
      title.classList.add('collapsed');
    }
  });
});

document.getElementById('expandAllBtn').addEventListener('click', function() {
  document.querySelectorAll('.discipline-group').forEach(group => {
    const scroll = group.querySelector('.tracker-table-scroll');
    const title = group.querySelector('.discipline-title');
    const icon = title.querySelector('.disc-toggle');
    if (scroll && scroll.style.display === 'none') {
      scroll.style.display = '';
      icon.innerHTML = '&#9660;';
      title.classList.remove('collapsed');
    }
  });
});

// BOQ View Modal
function bvEsc(s){if(!s)return'';var d=document.createElement('div');d.textContent=s;return d.innerHTML}

function openBoqView(btn){
  var d=JSON.parse(btn.dataset.view);
  var qty=d.mq!=null?Number(d.mq):null;
  var qs=qty===null?'-':(Number.isInteger(qty)?qty.toString():parseFloat(qty.toFixed(2)).toString());
  document.getElementById('boqViewGrid').innerHTML=
    '<div class="bv-row">'+
      '<div class="bv-field"><span class="bv-label">Discipline</span><span class="bv-val">'+bvEsc(d.d)+'</span></div>'+
      '<div class="bv-field"><span class="bv-label">Priority</span><span class="bv-val bv-badge bv-pri-'+d.p.toLowerCase()+'">'+bvEsc(d.p)+'</span></div>'+
    '</div>'+
    '<div class="bv-row">'+
      '<div class="bv-field"><span class="bv-label">BOQ No.</span><span class="bv-val">'+(bvEsc(d.b)||'&mdash;')+'</span></div>'+
      '<div class="bv-field"><span class="bv-label">Status</span><span class="bv-val bv-badge">'+bvEsc(d.s)+'</span></div>'+
    '</div>'+
    '<div class="bv-row bv-full">'+
      '<div class="bv-field"><span class="bv-label">Task</span><span class="bv-val">'+bvEsc(d.t)+'</span></div>'+
    '</div>'+
    '<div class="bv-row bv-full">'+
      '<div class="bv-field"><span class="bv-label">Task Description</span><div class="bv-task-description">'+
        '<span class="task-description-line"><b>Objective:</b><em>'+(bvEsc(d.obj)||'&mdash;')+'</em></span>'+
        '<span class="task-description-line"><b>Key Deliverables:</b><em>'+(bvEsc(d.del)||'&mdash;')+'</em></span>'+
        '<span class="task-description-line"><b>Requirements/Specifications:</b><em>'+(bvEsc(d.spec)||'&mdash;')+'</em></span>'+
        '<span class="task-description-line"><b>Remarks/References:</b><em>'+(bvEsc(d.ref)||'&mdash;')+'</em></span>'+
        '<span class="task-description-line"><b>Requirements:</b><em>'+(bvEsc(d.req)||'&mdash;')+'</em></span>'+
        '<span class="task-description-line"><b>Acceptance Criteria:</b><em>'+(bvEsc(d.acc)||'&mdash;')+'</em></span>'+
      '</div></div>'+
    '</div>'+
    '<div class="bv-row">'+
      '<div class="bv-field"><span class="bv-label">Material Quantity</span><span class="bv-val">'+qs+'</span></div>'+
      '<div class="bv-field"><span class="bv-label">Unit</span><span class="bv-val">'+(bvEsc(d.u)||'&mdash;')+'</span></div>'+
    '</div>'+
    '<div class="bv-row bv-full">'+
      '<div class="bv-field"><span class="bv-label">% Complete</span><div class="bv-val bv-pct-wrap"><div class="progress-bar"><i style="width:'+d.pc+'%"></i></div><strong>'+d.pc+'%</strong></div></div>'+
    '</div>'+
    '<div class="bv-row bv-full">'+
      '<div class="bv-field"><span class="bv-label">Notes</span><span class="bv-val bv-multi">'+(bvEsc(d.n)||'&mdash;')+'</span></div>'+
    '</div>'+
    '<div class="bv-row bv-full">'+
      '<div class="bv-field"><span class="bv-label">Remarks</span><span class="bv-val bv-multi">'+(bvEsc(d.rm)||'&mdash;')+'</span></div>'+
    '</div>';
  document.getElementById('boqViewOverlay').style.display='flex';
  document.body.style.overflow='hidden';
}

function closeBoqView(){
  document.getElementById('boqViewOverlay').style.display='none';
  document.body.style.overflow='';
}

function printBoqView(){window.print()}

function updateBoqMeasurementFields(){
  const type=document.getElementById('boqItemType');
  const weight=document.getElementById('boqActivityWeight');
  const planned=document.getElementById('boqPlannedProgress');
  const hint=document.getElementById('itemTypeHint');
  if(!type||!weight||!planned)return;
  const measurable=type.value==='Measurable Item';
  weight.disabled=!measurable;planned.disabled=!measurable; document.getElementById('boqMeasurementProfile').disabled=!measurable;
  weight.closest('label').classList.toggle('boq-field-disabled',!measurable);
  planned.closest('label').classList.toggle('boq-field-disabled',!measurable);
  if(!measurable){weight.value='0';planned.value='0';hint.textContent=type.value+' organizes the BOQ and is excluded from progress.';}
  else{if(Number(weight.value)<=0)weight.value='1';hint.textContent='A real work activity measured from 0% to 100%.';}
}
const boqItemType=document.getElementById('boqItemType');
if(boqItemType){boqItemType.addEventListener('change',updateBoqMeasurementFields);updateBoqMeasurementFields();}

document.addEventListener('keydown',function(e){if(e.key==='Escape'&&document.getElementById('boqViewOverlay').style.display==='flex')closeBoqView()});

</script>

<?php include __DIR__.'/../../../includes/footer.php'; ?>
