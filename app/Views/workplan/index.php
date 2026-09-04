<?php include __DIR__.'/../../../includes/header.php'; ?>
<?php /* ============================================================
   4. ADD/EDIT FORM
   ============================================================ */ ?>

<?php if ($showForm): ?>
<section class="panel wp-form-panel">
    <div class="panel-head">
        <div>
            <h2><?= $edit ? 'Edit Work Plan Record' : 'Add Work Plan Record' ?></h2>
            <p class="muted"><?= $edit ? 'Update work plan entry details.' : 'Register a new work plan entry.' ?></p>
        </div>
    </div>
    <form method="post" class="form-grid wp-form-grid" id="wpForm" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <input type="hidden" name="progress_id" id="progressId" value="<?= e($edit['progress_id'] ?? '') ?>">
        <input type="hidden" name="mas_submittal_id" id="masSubmittalId" value="<?= e($edit['mas_submittal_id'] ?? '') ?>">

        <!-- Project & Responsible -->
        <div class="wp-section-divider"><span>Project &amp; Assignment</span></div>

        <label>Project <span class="req">*</span>
            <select name="project_id" id="wpProjectSelect" required>
                <option value="">-- Select Project --</option>
                <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == ($edit['project_id'] ?? 0) ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (has_role('admin')): ?>
        <label>Responsible Person
            <select name="responsible_user_id" id="wpResponsible">
                <option value="">-- Select User --</option>
                <?php foreach ($usersList as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $u['id'] == ($edit['responsible_user_id'] ?? 0) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php else: ?>
        <input type="hidden" name="responsible_user_id" value="<?= $_SESSION['user_id'] ?>">
        <?php endif; ?>

        <!-- Activity Information -->
        <div class="wp-section-divider"><span>Activity Information</span></div>

        <label>Activity
            <div class="activity-ref-wrap">
                <input type="text" id="activityDisplay" value="<?php if (!empty($edit['progress_id']) && isset($progMap[$edit['progress_id']])) echo e($progMap[$edit['progress_id']]['task']); ?>" readonly placeholder="Click Browse to select">
                <button type="button" class="btn activity-browse-btn" id="btnBrowseActivity">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Browse
                </button>
            </div>
        </label>

        <label>Discipline
            <input name="discipline" id="wpDiscipline" readonly value="<?= e($edit['discipline'] ?? '') ?>" placeholder="Auto-filled from Activity">
        </label>

        <label>BOQ No.
            <input name="boq_no" id="wpBoqNo" readonly value="<?= e($edit['boq_no'] ?? '') ?>" placeholder="Auto-filled from Activity">
        </label>

        <label>MAS Ref. No.
            <div class="mas-ref-wrap">
                <input type="text" id="masRefDisplay" value="<?php if (!empty($edit['mas_submittal_id']) && isset($masMap[$edit['mas_submittal_id']])) echo e($masMap[$edit['mas_submittal_id']]['submittal_reference'] . ' — ' . $masMap[$edit['mas_submittal_id']]['material_description']); ?>" readonly placeholder="Browse MAS">
                <button type="button" class="btn mas-browse-btn" id="btnBrowseMAS">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Browse
                </button>
            </div>
        </label>

        <!-- Work Plan Details -->
        <div class="wp-section-divider"><span>Work Plan Details</span></div>

        <label>Work Plan Stage
            <select name="work_plan_stage">
                <?php foreach ($stages as $s): ?>
                <option value="<?= e($s) ?>" <?= $s === ($edit['work_plan_stage'] ?? 'First Fix') ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Work Plan Status
            <select name="work_plan_status" id="wpStatusSelect" onchange="autoFillPct()">
                <?php foreach ($wpStatuses as $ws): ?>
                <option value="<?= e($ws) ?>" <?= $ws === ($edit['work_plan_status'] ?? 'Work Pending') ? 'selected' : '' ?>><?= e($ws) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Work Percentage
            <input type="text" id="wpCompletionPct" name="completion_percentage_display" readonly value="<?= e(($statusPctMap[$edit['work_plan_status'] ?? 'Work Pending'] ?? 0) . '%') ?>">
        </label>

        <label>Installed Qty <small>(For Quantity-Based items)</small>
            <input type="number" step="0.01" min="0" name="installed_quantity" value="<?= e((string)($edit['installed_quantity'] ?? '')) ?>" placeholder="Cumulative installed quantity">
        </label>

        <!-- Schedule -->
        <div class="wp-section-divider"><span>Schedule</span></div>

        <label>Planned Start
            <input type="date" name="planned_start" id="wpPlannedStart" value="<?= e($edit['planned_start'] ?? '') ?>" onchange="calcDuration()">
        </label>

        <label>Planned Finish
            <input type="date" name="planned_finish" id="wpPlannedFinish" value="<?= e($edit['planned_finish'] ?? '') ?>" onchange="calcDuration()">
        </label>

        <label>Duration (days)
            <input type="text" id="wpDuration" readonly value="" placeholder="Auto-calculated">
        </label>

        <label>Actual Start
            <input type="date" name="actual_start" value="<?= e($edit['actual_start'] ?? '') ?>">
        </label>

        <label>Actual Finish
            <input type="date" name="actual_finish" value="<?= e($edit['actual_finish'] ?? '') ?>">
        </label>

        <!-- Photos -->
        <div class="wp-section-divider"><span>Work Status Photos</span></div>

        <label>Before Work Photo
            <input type="file" name="work_status_image_before" accept="image/jpeg,image/png,image/gif,image/webp">
            <?php if (!empty($edit['work_status_image_before'])): ?>
            <small class="existing-img-note">Current: <a href="<?=e(evidence_url($edit['work_status_image_before']))?>" target="_blank" rel="noopener noreferrer"><?= e($edit['work_status_image_before']) ?></a></small>
            <?php endif; ?>
        </label>

        <label>After Work Photos <small>(Maximum 5)</small>
            <input type="file" name="work_status_images_after[]" id="afterPhotoInput" accept="image/jpeg,image/png,image/gif,image/webp" multiple data-existing-count="<?=count($editAfterPhotos)?>">
            <small class="existing-img-note">Select up to <?=max(0,5-count($editAfterPhotos))?> additional photo(s). Each file must be within the configured upload limit.</small>
            <?php foreach($editAfterPhotos as $photo): ?>
            <span class="existing-img-note"><a href="<?=e(evidence_url($photo['file_name']))?>" target="_blank" rel="noopener noreferrer"><?=e($photo['file_name'])?></a> <label style="display:inline"><input type="checkbox" name="remove_after_photo[]" value="<?=$photo['id']?>"> Remove</label></span>
            <?php endforeach; ?>
        </label>

        <!-- Remarks -->
        <label class="span-2">Remarks
            <textarea name="remarks" rows="3"><?= e($edit['remarks'] ?? '') ?></textarea>
        </label>

        <div class="form-actions wp-form-actions">
            <button class="btn" type="submit"><?= $edit ? 'Update Record' : 'Add Record' ?></button>
            <a class="btn ghost" href="workplan.php">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<?php /* ============================================================
   5. REGISTER GRID
   ============================================================ */ ?>

<!-- Activity Browse Modal -->
<div class="wp-modal-overlay" id="activityModal" style="display:none" onclick="if(event.target===this)closeActivityModal()">
    <div class="wp-modal">
        <div class="wp-modal-head">
            <h3>Select Activity from Project Progress</h3>
            <button type="button" class="btn ghost" onclick="closeActivityModal()">&times;</button>
        </div>
        <div class="wp-modal-search">
            <input type="text" id="activitySearchQ" placeholder="Search by BOQ, task, discipline..." oninput="filterActivityList()">
        </div>
        <div class="wp-modal-body" id="activityResults">
            <p class="wp-empty">Select a project first, then click Browse.</p>
        </div>
    </div>
</div>

<!-- MAS Ref Browse Modal -->
<div class="wp-modal-overlay" id="masRefModal" style="display:none" onclick="if(event.target===this)closeMasRefModal()">
    <div class="wp-modal">
        <div class="wp-modal-head">
            <h3>Select MAS Submittal (by BOQ No.)</h3>
            <button type="button" class="btn ghost" onclick="closeMasRefModal()">&times;</button>
        </div>
        <div class="wp-modal-search">
            <input type="text" id="masRefSearchQ" placeholder="Search by reference or description..." oninput="filterMasRefList()">
        </div>
        <div class="wp-modal-body" id="masRefResults">
            <p class="wp-empty">Select an activity first to set the BOQ No.</p>
        </div>
    </div>
</div>

<?php /* ============================================================
   6. VIEW MODAL
   ============================================================ */ ?>

<!-- View Work Plan Detail Modal -->
<div class="view-modal-overlay" id="viewModal" style="display:none" onclick="if(event.target===this)closeViewModal()">
    <div class="view-modal">
        <div class="view-modal-header">
            <span style="font-weight:700;font-size:14px;color:#334155">Work Plan Record Details</span>
            <div class="view-modal-header-actions">
                <button type="button" class="view-modal-print" onclick="window.print()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print
                </button>
                <button type="button" class="view-modal-close" onclick="closeViewModal()">&times;</button>
            </div>
        </div>
        <div class="view-modal-body" id="viewModalBody"></div>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Work Plan Register</h2>
            <p class="muted">MEP Work Plan records grouped by Project.</p>
        </div>
        <div class="panel-head-actions">
            <?php if (!$showForm): ?><a href="?add=1" class="btn">+ Add Work Plan</a><?php endif; ?>
            <form class="search">
                <input name="q" placeholder="Search..." value="<?= e($q) ?>">
                <select name="project">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filterProject === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="stage">
                    <option value="">All Stages</option>
                    <?php foreach ($stages as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filterStage === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn">Filter</button>
            </form>
        </div>
    </div>

    <?php if (empty($grouped)): ?>
        <p class="empty">No work plan records found.</p>
    <?php endif; ?>

    <?php foreach ($grouped as $projectName => $items): ?>
    <div class="wp-project-group">
        <div class="wp-project-header" onclick="toggleWpGroup(this)">
            <h3><span class="wp-toggle">&#9660;</span> <?= e($projectName) ?></h3>
            <small><?= count($items) ?> record<?= count($items) !== 1 ? 's' : '' ?></small>
        </div>
        <div class="wp-project-body">
            <div class="table-wrap">
                <table class="wp-table">
                    <thead><tr>
                        <th>Discipline</th>
                        <th>BOQ No.</th>
                        <th>MAS Ref.</th>
                        <th>Stage</th>
                        <th>Status</th>
                        <th>Work %</th>
                        <th>Planned Start</th>
                        <th>Planned Finish</th>
                        <th>Duration</th>
                        <th>Actual Start</th>
                        <th>Actual Finish</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $sc = $r['work_plan_stage'] ?? 'First Fix';
                        $sCol = $stageColors[$sc] ?? ['bg' => '#6B7280', 'fg' => '#fff'];
                        // Calculate duration
                        $dur = '';
                        if (!empty($r['planned_start']) && !empty($r['planned_finish'])) {
                            $d1 = new DateTime($r['planned_start']);
                            $d2 = new DateTime($r['planned_finish']);
                            $diff = $d1->diff($d2);
                            $dur = $diff->days . ' day' . ($diff->days !== 1 ? 's' : '');
                        }
                        $masRef = '';
                        if (!empty($r['mas_submittal_id']) && isset($masMap[$r['mas_submittal_id']])) {
                            $masRef = $masMap[$r['mas_submittal_id']]['submittal_reference'];
                        }
                    ?>
                    <?php
                        // Work plan status colors
                        $wpSt = $r['work_plan_status'] ?? 'Work Pending';
                        $wpPct = (int)($r['completion_percentage'] ?? ($statusPctMap[$wpSt] ?? 0));
                        $wpStColor = match($wpSt) {
                            'Work Completed'       => ['bg' => '#22C55E', 'fg' => '#fff'],
                            'Working on Progress'  => ['bg' => '#F59E0B', 'fg' => '#172033'],
                            default                => ['bg' => '#94A3B8', 'fg' => '#fff'],
                        };
                    ?>
                    <tr>
                        <td data-label="Discipline"><?= e($r['discipline'] ?? '') ?></td>
                        <td data-label="BOQ No."><?= e($r['boq_no'] ?? '') ?></td>
                        <td data-label="MAS Ref."><?= e($masRef) ?></td>
                        <td data-label="Stage"><span class="status-badge" style="background:<?= $sCol['bg'] ?>;color:<?= $sCol['fg'] ?>"><?= e($sc) ?></span></td>
                        <td data-label="Status"><span class="status-badge" style="background:<?= $wpStColor['bg'] ?>;color:<?= $wpStColor['fg'] ?>"><?= e($wpSt) ?></span></td>
                        <td data-label="Work %"><span class="pct-badge pct-<?= $wpPct >= 100 ? 'done' : ($wpPct > 1 ? 'wip' : 'pending') ?>"><?= $wpPct ?>%</span></td>
                        <td data-label="Planned Start"><?= e($r['planned_start'] ?? '') ?></td>
                        <td data-label="Planned Finish"><?= e($r['planned_finish'] ?? '') ?></td>
                        <td data-label="Duration"><?= e($dur) ?></td>
                        <td data-label="Actual Start"><?= e($r['actual_start'] ?? '') ?></td>
                        <td data-label="Actual Finish"><?= e($r['actual_finish'] ?? '') ?></td>
                        <td data-label="Remarks"><?= e($r['remarks'] ?? '') ?></td>
                        <td class="actions" data-label="Actions">
                            <a href="#" class="act-icon act-view" title="View" onclick="openViewModal(<?= $r['id'] ?>);return false;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="workplan_report_pdf.php?id=<?= $r['id'] ?>" target="_blank" rel="noopener noreferrer" class="act-icon act-report" title="View / Print Report">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </a>
                            <?php if (has_role('admin','project_manager') || (int)$r['responsible_user_id']===(int)$_SESSION['user_id']): ?><a href="?edit=<?= $r['id'] ?>" class="act-icon act-edit" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a><?php endif; ?>
                            <?php if (has_role('admin')): ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this work plan record?')">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="act-icon act-delete" title="Delete">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<style>
/* Panel head actions */
.panel-head-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.req{color:#EF4444}

/* Form grid */
.wp-form-grid{grid-template-columns:1fr 1fr;gap:14px 20px}
.wp-form-grid .span-2{grid-column:1/-1}
.wp-form-actions{grid-column:1/-1;justify-content:flex-end}

/* Section divider inside form */
.wp-section-divider{grid-column:1/-1;margin:6px 0 2px;padding-bottom:0}
.wp-section-divider span{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700}

/* Activity & MAS Ref fields */
.activity-ref-wrap,.mas-ref-wrap{display:flex;gap:6px}
.activity-ref-wrap input,.mas-ref-wrap input{flex:1}
.activity-browse-btn,.mas-browse-btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;font-size:12px;white-space:nowrap}

/* Readonly fields */
input[readonly]{background:#f1f5f9;color:#64748b;cursor:not-allowed}

/* Existing image note */
.existing-img-note{display:block;margin-top:4px;font-size:11px;color:#64748b}
.existing-img-note a{color:#0D9488;font-weight:600}

/* Status badge */
.status-badge{display:inline-block;padding:3px 10px;border-radius:5px;font-weight:700;font-size:11px;white-space:nowrap;letter-spacing:.02em}

/* Percentage badge */
.pct-badge{display:inline-block;padding:3px 10px;border-radius:5px;font-weight:700;font-size:11px;white-space:nowrap}
.pct-pending{background:#F1F5F9;color:#64748B}
.pct-wip{background:#FEF3C7;color:#92400E}
.pct-done{background:#D1FAE5;color:#065F46}

/* Project group (accordion) */
.wp-project-group{margin-bottom:16px;border:1px solid #e2e7ef;border-radius:10px;overflow:hidden}
.wp-project-header{background:#f1f5f9;padding:12px 16px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background .2s}
.wp-project-header:hover{background:#e2e8f0}
.wp-project-header h3{margin:0;font-size:15px;color:#172033}
.wp-project-header small{color:#64748b;font-size:12px;font-weight:600}
.wp-project-body{padding:0 12px 12px}

/* Collapse toggle */
.wp-toggle{font-size:12px;margin-right:4px;display:inline-block;transition:transform .2s}
.wp-project-header.collapsed .wp-toggle{transform:rotate(-90deg)}

/* Work Plan table */
.wp-table{width:100%;border-collapse:collapse;font-size:12px;min-width:1200px}
.wp-table thead th{background:#f8fafc;font-weight:700;text-align:center;padding:9px 8px;border:1px solid #e5e7eb;white-space:nowrap;font-size:11px}
.wp-table tbody td{padding:7px 8px;border:1px solid #e5e7eb;vertical-align:middle;text-align:center}
.wp-table td:nth-child(12){text-align:left}

/* Action icon buttons */
.actions{white-space:nowrap;display:flex;align-items:center;gap:4px;justify-content:center}
.act-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:28px;border-radius:5px;border:1px solid transparent;text-decoration:none;transition:all .15s;cursor:pointer;position:relative;background:none}
.act-icon svg{flex-shrink:0}
.act-img{color:#3B82F6;border-color:#93c5fd}
.act-img:hover{background:#EFF6FF;border-color:#3B82F6}
.act-img-after{color:#22C55E;border-color:#86efac}
.act-img-after:hover{background:#F0FDF4;border-color:#22C55E}
.act-view{color:#0D9488;border-color:#99f6e4}
.act-view:hover{background:#f0fdfa;border-color:#0D9488}
.act-report{color:#6366F1;border-color:#c7d2fe}
.act-report:hover{background:#EEF2FF;border-color:#6366F1}
.act-edit{color:#F59E0B;border-color:#fde68a}
.act-edit:hover{background:#FFFBEB;border-color:#F59E0B}
.act-delete{color:#EF4444;border-color:#fca5a5;padding:0;font-size:0;line-height:1}
.act-delete:hover{background:#FEF2F2;border-color:#EF4444}

/* Tooltip (CSS-only) */
.act-icon[title]{position:relative}
.act-icon[title]:hover::after{content:attr(title);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);padding:4px 10px;background:#1e293b;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;border-radius:5px;pointer-events:none;z-index:10}
.act-icon[title]:hover::before{content:'';position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1e293b;pointer-events:none;z-index:10}

/* Browse Modals */
.wp-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;display:flex;align-items:center;justify-content:center}
.wp-modal{background:#fff;border-radius:12px;width:95%;max-width:900px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.wp-modal-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e5e7eb}
.wp-modal-head h3{margin:0;font-size:1.05rem}
.wp-modal-search{padding:12px 18px;border-bottom:1px solid #e5e7eb}
.wp-modal-search input{width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px}
.wp-modal-body{overflow-y:auto;flex:1;padding:0}

.wp-list-header{display:grid;grid-template-columns:10ch 1fr 1fr 1.4fr;gap:10px;padding:8px 20px;background:#f8fafc;border-bottom:2px solid #e5e7eb;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.wp-item{display:grid;grid-template-columns:10ch 1fr 1fr 1.4fr;gap:10px;padding:10px 20px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;align-items:start}
.wp-item:hover{background:#f0fdf4}
.wp-item.wp-duplicate{background:#fff7ed;border-left:4px solid #f97316;cursor:not-allowed}
.wp-item.wp-duplicate .wp-item-ref::after{content:' Duplicate';color:#c2410c;font-size:10px;text-transform:uppercase;margin-left:6px}
.wp-item-ref{font-weight:700;color:#0D9488;font-size:13px}
.wp-item-disc{font-size:12px;color:#64748b}
.wp-item-task{font-size:13px;color:#172033}
.wp-item-mat{font-size:12px;color:#475569;word-break:break-word;line-height:1.4}
.wp-empty{padding:30px;text-align:center;color:#94a3b8;font-size:13px}

/* MAS Ref modal table */
.mas-ref-table{width:100%;border-collapse:collapse;font-size:.85rem}
.mas-ref-table th,.mas-ref-table td{padding:8px 10px;text-align:left;border-bottom:1px solid #e8eaed}
.mas-ref-table th{background:#f8fafc;font-weight:600;position:sticky;top:0}
.mas-ref-table tr.selectable{cursor:pointer}
.mas-ref-table tr.selectable:hover{background:#eef3ff}

/* View Modal */
.view-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}
.view-modal{background:#fff;border-radius:12px;width:100%;max-width:800px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.25);overflow:hidden}
.view-modal-header{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb}
.view-modal-header-actions{display:flex;gap:8px;align-items:center}
.view-modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;padding:4px 8px;border-radius:4px}
.view-modal-close:hover{background:#e5e7eb;color:#172033}
.view-modal-print{display:inline-flex;align-items:center;gap:4px;padding:5px 14px;font-size:12px;font-weight:600;color:#0D9488;border:1px solid #0D9488;border-radius:6px;background:none;cursor:pointer;transition:all .15s}
.view-modal-print:hover{background:#0D9488;color:#fff}
.view-modal-body{overflow-y:auto;flex:1;padding:0}

/* Professional View Form */
.sv-form{padding:30px;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
.sv-logos{display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;border-bottom:3px solid #0D9488;margin-bottom:0}
.sv-logos img{height:44px;object-fit:contain}
.sv-title-bar{background:#0D9488;color:#fff;text-align:center;padding:10px 16px;margin-bottom:20px}
.sv-title-bar h2{margin:0;font-size:16px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.sv-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:1px solid #cbd5e1;border-radius:6px;overflow:hidden;margin-bottom:16px}
.sv-field{display:flex;border-bottom:1px solid #e2e8f0;min-height:38px}
.sv-field:last-child{border-bottom:0}
.sv-field.full{grid-column:1/-1}
.sv-field-label{flex:0 0 170px;padding:9px 14px;font-size:12px;font-weight:700;color:#334155;background:#f1f5f9;border-right:1px solid #e2e8f0;display:flex;align-items:center}
.sv-field-value{flex:1;padding:9px 14px;font-size:13px;color:#172033;display:flex;align-items:center;word-break:break-word}
.sv-field-value .status-badge{font-size:12px;padding:4px 14px}
.sv-field-value.sv-textarea{align-items:flex-start;min-height:50px;white-space:pre-wrap}
.sv-footer{text-align:center;padding-top:14px;border-top:2px solid #e2e8f0;margin-top:8px}
.sv-footer small{color:#94a3b8;font-size:11px}
.sv-img-preview{max-width:100%;max-height:200px;border-radius:6px;border:1px solid #e2e8f0;margin-top:4px}

/* Print for View Modal */
@media print{
    body *{visibility:hidden}
    .view-modal-overlay,.view-modal-overlay *{visibility:visible}
    .view-modal-overlay{position:fixed;inset:0;background:#fff;padding:0;display:block}
    .view-modal{box-shadow:none;border-radius:0;max-height:none;max-width:none;width:100%}
    .view-modal-header{display:none}
    .sv-form{padding:20px}
    @page{size:A4 portrait;margin:10mm}
}

/* Responsive */
@media(max-width:760px){
    .wp-form-grid{grid-template-columns:1fr}
    .wp-form-grid .span-2{grid-column:1}
    .status-legend{flex-direction:column;align-items:flex-start;gap:6px}
}
@media(max-width:700px){
    .wp-table{min-width:0}
    .wp-table thead{display:none}
    .wp-table tbody{display:block}
    .wp-table tr{display:block;margin-bottom:12px;border:1px solid #e2e7ef;border-radius:10px;padding:10px;background:#fff}
    .wp-table td{display:flex;justify-content:space-between;gap:10px;border:0;border-bottom:1px solid #f1f4f9;padding:7px 2px;text-align:right}
    .wp-table td:last-child{border-bottom:0}
    .wp-table td::before{content:attr(data-label);font-size:11px;font-weight:800;text-transform:uppercase;color:#8a95a8;text-align:left;flex:0 0 40%}
}
</style>

<?php /* ============================================================
   7. JAVASCRIPT
   ============================================================ */ ?>

<script>
/* --- Embedded JSON blob for View modal (invariant #6) --- */
const wpData = <?php
    $viewData = [];
    foreach ($rows as $r) {
        $sc = $r['work_plan_stage'] ?? 'First Fix';
        $dur = '';
        if (!empty($r['planned_start']) && !empty($r['planned_finish'])) {
            $d1 = new DateTime($r['planned_start']);
            $d2 = new DateTime($r['planned_finish']);
            $diff = $d1->diff($d2);
            $dur = $diff->days . ' day' . ($diff->days !== 1 ? 's' : '');
        }
        $masRefLabel = '';
        $masMatLabel = '';
        if (!empty($r['mas_submittal_id']) && isset($masMap[$r['mas_submittal_id']])) {
            $masRefLabel = $masMap[$r['mas_submittal_id']]['submittal_reference'];
            $masMatLabel = $masMap[$r['mas_submittal_id']]['material_description'];
        }
        $activityLabel = '';
        if (!empty($r['progress_id']) && isset($progMap[$r['progress_id']])) {
            $activityLabel = $progMap[$r['progress_id']]['task'];
        }
        $viewData[$r['id']] = [
            'project_name'        => $r['project_name'] ?? '',
            'responsible'         => $r['responsible_name'] ?? '',
            'activity'            => $activityLabel,
            'discipline'          => $r['discipline'] ?? '',
            'boq_no'              => $r['boq_no'] ?? '',
            'mas_ref'             => $masRefLabel,
            'mas_material'        => $masMatLabel,
            'stage'               => $sc,
            'work_plan_status'    => $r['work_plan_status'] ?? 'Work Pending',
            'completion_percentage' => (int)($r['completion_percentage'] ?? 0),
            'installed_quantity'  => $r['installed_quantity'] !== null ? rtrim(rtrim(number_format((float)$r['installed_quantity'],2,'.',''),'0'),'.') : '',
            'image_before'        => evidence_url($r['work_status_image_before'] ?? ''),
            'images_after'        => array_map('evidence_url',$afterPhotosByWorkplan[(int)$r['id']] ?? array_values(array_filter([$r['work_status_image_after'] ?? '']))),
            'planned_start'       => $r['planned_start'] ?? '',
            'planned_finish'      => $r['planned_finish'] ?? '',
            'duration'            => $dur,
            'actual_start'        => $r['actual_start'] ?? '',
            'actual_finish'       => $r['actual_finish'] ?? '',
            'remarks'             => $r['remarks'] ?? '',
        ];
    }
    echo json_encode($viewData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES);
?>;

const wpStageColors = {
    'First Fix':                {bg:'#3B82F6', fg:'#fff'},
    'Second Fix':               {bg:'#8B5CF6', fg:'#fff'},
    'Third/Final Fix':          {bg:'#F59E0B', fg:'#172033'},
    'Testing & Commissioning':  {bg:'#0EA5E9', fg:'#fff'},
    'Handover':                 {bg:'#22C55E', fg:'#fff'}
};

/* --- View Modal --- */
function openViewModal(id) {
    const d = wpData[id];
    if (!d) return;
    const sc = wpStageColors[d.stage] || {bg:'#6B7280', fg:'#fff'};
    const badge = '<span class="status-badge" style="background:'+sc.bg+';color:'+sc.fg+'">'+escH(d.stage)+'</span>';

    const wpStColors = {'Work Completed':{bg:'#22C55E',fg:'#fff'},'Working on Progress':{bg:'#F59E0B',fg:'#172033'}};
    const wpStCol = wpStColors[d.work_plan_status] || {bg:'#94A3B8',fg:'#fff'};
    const wpStBadge = '<span class="status-badge" style="background:'+wpStCol.bg+';color:'+wpStCol.fg+'">'+escH(d.work_plan_status)+'</span>';

    const pct = Number.isFinite(Number(d.completion_percentage)) ? Number(d.completion_percentage) : 0;
    const pctCls = pct >= 100 ? 'pct-done' : (pct > 0 ? 'pct-wip' : 'pct-pending');
    const pctBadge = '<span class="pct-badge '+pctCls+'">'+pct+'%</span>';

    let imgRow = '';
    if (d.image_before) {
        imgRow += '<div class="sv-field full"><div class="sv-field-label">Before Work Photo</div><div class="sv-field-value"><img src="'+escH(d.image_before)+'" alt="Before photo" class="sv-img-preview"></div></div>';
    }
    if (d.images_after && d.images_after.length) {
        let gallery=''; d.images_after.forEach(function(file,i){gallery+='<img src="'+escH(file)+'" alt="After photo '+(i+1)+'" class="sv-img-preview" style="margin:4px">';});
        imgRow += '<div class="sv-field full"><div class="sv-field-label">After Work Photos ('+d.images_after.length+')</div><div class="sv-field-value">'+gallery+'</div></div>';
    }

    document.getElementById('viewModalBody').innerHTML =
    '<div class="sv-form">' +
        '<div class="sv-logos">' +
            '<img src="assets/img/logo-reversed-white.png" alt="MEP Projects Portal">' +
            '<img src="assets/img/aalatech-logo.png" alt="Aala Tech">' +
        '</div>' +
        '<div class="sv-title-bar"><h2>MEP Work Plan Record</h2></div>' +
        '<div class="sv-field-grid">' +
            svField('Project Name', d.project_name, true) +
            svField('Prepared by', d.responsible) +
            svField('Activity', d.activity) +
            svField('Discipline', d.discipline) +
            svField('BOQ No.', d.boq_no) +
            svField('MAS Ref. No.', d.mas_ref) +
            svField('MAS Material', d.mas_material, true) +
            svFieldRaw('Work Plan Stage', badge) +
            svFieldRaw('Work Plan Status', wpStBadge) +
            svFieldRaw('Work Percentage', pctBadge) +
            svField('Installed Qty', d.installed_quantity) +
            svField('Planned Start', d.planned_start) +
            svField('Planned Finish', d.planned_finish) +
            svField('Duration', d.duration) +
            svField('Actual Start', d.actual_start) +
            svField('Actual Finish', d.actual_finish) +
            imgRow +
            '<div class="sv-field full"><div class="sv-field-label">Remarks</div><div class="sv-field-value sv-textarea">' + escH(d.remarks || '—') + '</div></div>' +
        '</div>' +
        '<div class="sv-footer"><small>MEP Projects Portal • Generated on ' + new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) + '</small></div>' +
    '</div>';

    document.getElementById('viewModal').style.display = 'flex';
}

function svField(label, value, full) {
    return '<div class="sv-field'+(full?' full':'')+'">' +
        '<div class="sv-field-label">' + escH(label) + '</div>' +
        '<div class="sv-field-value">' + escH(value || '—') + '</div></div>';
}
function svFieldRaw(label, html, full) {
    return '<div class="sv-field'+(full?' full':'')+'">' +
        '<div class="sv-field-label">' + escH(label) + '</div>' +
        '<div class="sv-field-value">' + html + '</div></div>';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

/* --- Accordion toggle --- */
function toggleWpGroup(el) {
    const body = el.closest('.wp-project-group').querySelector('.wp-project-body');
    const icon = el.querySelector('.wp-toggle');
    if (body.style.display === 'none') {
        body.style.display = '';
        icon.innerHTML = '&#9660;';
        el.classList.remove('collapsed');
    } else {
        body.style.display = 'none';
        icon.innerHTML = '&#9654;';
        el.classList.add('collapsed');
    }
}

/* --- Duration calculation --- */
function calcDuration() {
    const s = document.getElementById('wpPlannedStart').value;
    const f = document.getElementById('wpPlannedFinish').value;
    const out = document.getElementById('wpDuration');
    if (s && f) {
        const d1 = new Date(s);
        const d2 = new Date(f);
        const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
        out.value = diff >= 0 ? diff + ' day' + (diff !== 1 ? 's' : '') : 'Invalid';
    } else {
        out.value = '';
    }
}
// Calculate on page load for edit mode
calcDuration();

/* --- Activity Browse Modal --- */
let activityData = [];

document.getElementById('btnBrowseActivity').addEventListener('click', function() {
    const pid = document.getElementById('wpProjectSelect').value;
    if (!pid) { alert('Please select a project first.'); return; }
    document.getElementById('activityModal').style.display = 'flex';
    document.getElementById('activitySearchQ').value = '';
    document.getElementById('activityResults').innerHTML = '<p class="wp-empty">Loading...</p>';

    fetch('ajax_progress_list.php?project_id=' + encodeURIComponent(pid))
        .then(r => r.json())
        .then(data => {
            activityData = data;
            renderActivityList(data);
        })
        .catch(() => {
            document.getElementById('activityResults').innerHTML = '<p class="wp-empty">Failed to load activities.</p>';
        });

    document.getElementById('activitySearchQ').focus();
});

function closeActivityModal() {
    document.getElementById('activityModal').style.display = 'none';
}

function renderActivityList(items, query) {
    const body = document.getElementById('activityResults');
    if (!items.length) {
        body.innerHTML = '<p class="wp-empty">No activities found for this project.</p>';
        return;
    }
    const q = query || '';
    let html = '<div class="wp-list-header"><span>BOQ No.</span><span>Discipline</span><span>Task</span><span>Material Description</span></div>';
    items.forEach(item => {
        const duplicateClass = Number(item.duplicate_count) > 1 ? ' wp-duplicate' : '';
        html += '<div class="wp-item'+duplicateClass+'" onclick="selectActivity(this)" data-id="' + Number(item.id) + '" data-boq="' + escH(item.boq_no) + '" data-disc="' + escH(item.discipline) + '" data-task="' + escH(item.task) + '" data-duplicate="'+Number(item.duplicate_count||1)+'">'
            + '<span class="wp-item-ref">' + highlightText(item.boq_no || '—', q) + '</span>'
            + '<span class="wp-item-disc">' + highlightText(item.discipline, q) + '</span>'
            + '<span class="wp-item-task">' + highlightText(item.task, q) + '</span>'
            + '<span class="wp-item-mat">' + highlightText(item.material_description || '—', q) + '</span>'
            + '</div>';
    });
    body.innerHTML = html;
}

function filterActivityList() {
    const q = document.getElementById('activitySearchQ').value.trim();
    if (!q) { renderActivityList(activityData); return; }
    const ql = q.toLowerCase();
    const filtered = activityData.filter(item =>
        (item.boq_no || '').toLowerCase().includes(ql) ||
        (item.task || '').toLowerCase().includes(ql) ||
        (item.discipline || '').toLowerCase().includes(ql) ||
        (item.material_description || '').toLowerCase().includes(ql)
    );
    renderActivityList(filtered, q);
}

function selectActivity(el) {
    if(Number(el.dataset.duplicate)>1){alert('This BOQ reference is duplicated. Resolve the duplicate measurable items before linking a Work Plan.');return;}
    document.getElementById('progressId').value = el.dataset.id;
    document.getElementById('activityDisplay').value = el.dataset.task;
    document.getElementById('wpDiscipline').value = el.dataset.disc;
    document.getElementById('wpBoqNo').value = el.dataset.boq;
    // Clear MAS selection since BOQ changed
    document.getElementById('masSubmittalId').value = '';
    document.getElementById('masRefDisplay').value = '';
    closeActivityModal();
}

/* --- MAS Ref Browse Modal --- */
let masRefData = [];

document.getElementById('btnBrowseMAS').addEventListener('click', function() {
    const pid = document.getElementById('wpProjectSelect').value;
    const boq = document.getElementById('wpBoqNo').value;
    if (!pid) { alert('Please select a project first.'); return; }
    if (!boq) { alert('Please select an activity first to set the BOQ No.'); return; }

    document.getElementById('masRefModal').style.display = 'flex';
    document.getElementById('masRefSearchQ').value = '';
    document.getElementById('masRefResults').innerHTML = '<p class="wp-empty">Loading...</p>';

    fetch('ajax_mas_by_boq.php?project_id=' + encodeURIComponent(pid) + '&boq_no=' + encodeURIComponent(boq))
        .then(r => r.json())
        .then(data => {
            masRefData = data;
            renderMasRefList(data);
        })
        .catch(() => {
            document.getElementById('masRefResults').innerHTML = '<p class="wp-empty">Failed to load submittals.</p>';
        });

    document.getElementById('masRefSearchQ').focus();
});

function closeMasRefModal() {
    document.getElementById('masRefModal').style.display = 'none';
}

function renderMasRefList(items) {
    const body = document.getElementById('masRefResults');
    if (!items.length) {
        body.innerHTML = '<p class="wp-empty">No submittals found matching this BOQ No.</p>';
        return;
    }
    let html = '<table class="mas-ref-table"><thead><tr><th>MAS Ref.</th><th>Material Description</th><th>BOQ Ref.</th><th>Approved Date</th><th>Status</th></tr></thead><tbody>';
    items.forEach(r => {
        html += '<tr class="selectable" data-id="'+r.id+'" data-ref="'+escH(r.submittal_reference)+'" data-mat="'+escH(r.material_description)+'">';
        html += '<td>'+escH(r.submittal_reference)+'</td>';
        html += '<td>'+escH(r.material_description)+'</td>';
        html += '<td>'+escH(r.boq_ref_no)+'</td>';
        html += '<td>'+(r.approved_date||'')+'</td>';
        html += '<td>'+escH(r.status)+'</td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    body.innerHTML = html;
    body.querySelectorAll('tr.selectable').forEach(tr => {
        tr.addEventListener('click', function() {
            document.getElementById('masSubmittalId').value = this.dataset.id;
            document.getElementById('masRefDisplay').value = this.dataset.ref + ' — ' + this.dataset.mat;
            closeMasRefModal();
        });
    });
}

function filterMasRefList() {
    const q = document.getElementById('masRefSearchQ').value.toLowerCase();
    if (!q) { renderMasRefList(masRefData); return; }
    const filtered = masRefData.filter(r =>
        (r.submittal_reference || '').toLowerCase().includes(q) ||
        (r.material_description || '').toLowerCase().includes(q)
    );
    renderMasRefList(filtered);
}

/* --- Auto-fill completion percentage from status --- */
const statusPctMap = {
    'Work Pending': 0,
    'Working on Progress': 35,
    'Work Completed': 100
};
function autoFillPct() {
    const sel = document.getElementById('wpStatusSelect');
    const pctField = document.getElementById('wpCompletionPct');
    if (sel && pctField) {
        const pct = Object.prototype.hasOwnProperty.call(statusPctMap,sel.value) ? statusPctMap[sel.value] : 0;
        pctField.value = pct + '%';
    }
}

/* --- Utility --- */
function escH(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function highlightText(text, query) {
    if (!query || !text) return escH(text || '');
    const escaped = escH(text);
    const qEsc = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return escaped.replace(new RegExp('(' + qEsc + ')', 'gi'), '<mark style="background:#FEF08A;color:#172033;padding:1px 2px;border-radius:3px;font-weight:700">$1</mark>');
}

const afterPhotoInput=document.getElementById('afterPhotoInput');
if(afterPhotoInput){
    const validateAfterPhotos=function(){
        const existing=parseInt(afterPhotoInput.dataset.existingCount||'0',10);
        const removed=document.querySelectorAll('input[name="remove_after_photo[]"]:checked').length;
        const selected=afterPhotoInput.files?afterPhotoInput.files.length:0;
        if(existing-removed+selected>5){afterPhotoInput.setCustomValidity('Maximum 5 after-work photos are allowed. Remove an existing photo or select fewer files.');}
        else{afterPhotoInput.setCustomValidity('');}
    };
    afterPhotoInput.addEventListener('change',validateAfterPhotos);
    document.querySelectorAll('input[name="remove_after_photo[]"]').forEach(el=>el.addEventListener('change',validateAfterPhotos));
}

/* Close modals on Escape */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeActivityModal(); closeMasRefModal(); closeViewModal(); }
});
</script>

<?php include __DIR__.'/../../../includes/footer.php'; ?>
