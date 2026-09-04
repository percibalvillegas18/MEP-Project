<?php include __DIR__.'/../../../includes/header.php'; ?>
<?php if ($showForm): ?>
<section class="panel submittal-form-panel">
    <div class="panel-head">
        <div><h2><?= $edit ? 'Edit Submittal' : 'Add Material Submittal' ?></h2><p class="muted">Register a new material submittal entry.</p></div>
    </div>
    <form method="post" class="form-grid submittal-form-grid">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <input type="hidden" name="progress_id" id="boqProgressIdInput" value="<?= e((string)($edit['progress_id'] ?? '')) ?>">

        <label>Project <span class="req">*</span>
            <select name="project_id" id="submittalProjectSelect" required>
                <option value="">-- Select Project --</option>
                <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == ($edit['project_id'] ?? 0) ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Discipline <span class="req">*</span>
            <select name="discipline" required>
                <option value="">-- Select Discipline --</option>
                <?php foreach ($disciplineList as $d): ?>
                <option value="<?= e($d) ?>" <?= ($edit && ($edit['discipline'] ?? '') === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="boq-ref-field">
            <label>BOQ Ref. No.
                <div class="boq-ref-input-wrap">
                    <input name="boq_ref_no" id="boqRefNoInput" value="<?= e($edit['boq_ref_no'] ?? '') ?>" placeholder="Enter or search BOQ" readonly>
                    <button type="button" class="btn boq-search-btn" id="boqSearchBtn" onclick="openBoqModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Search
                    </button>
                </div>
            </label>
        </div>

        <label>Submittal Reference
            <input name="submittal_reference" value="<?= e($edit['submittal_reference'] ?? '') ?>" placeholder="e.g. SUB-HVAC-001">
        </label>

        <label class="span-2">Material Description
            <input name="material_description" value="<?= e($edit['material_description'] ?? '') ?>">
        </label>

        <div class="mfr-ref-field">
            <label>Manufacturer
                <div class="mfr-input-wrap">
                    <input name="manufacturer" id="mfrInput" value="<?= e($edit['manufacturer'] ?? '') ?>">
                    <button type="button" class="btn mfr-search-btn" id="mfrSearchBtn" onclick="openMfrModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Mfr List
                    </button>
                </div>
            </label>
        </div>

        <label>Country Origin
            <input name="country_origin" id="countryOriginInput" value="<?= e($edit['country_origin'] ?? '') ?>">
        </label>

        <label>Submittal Revision No.
            <input name="submittal_revision_no" value="<?= e($edit['submittal_revision_no'] ?? '') ?>" placeholder="e.g. Rev.0">
        </label>

        <label>Date Submitted
            <input type="date" name="submitted_date" value="<?= e($edit['submitted_date'] ?? '') ?>">
        </label>

        <label>Date Approve
            <input type="date" name="approved_date" value="<?= e($edit['approved_date'] ?? '') ?>">
        </label>

        <label>Status
            <select name="status">
                <?php foreach ($statusCodes as $code): ?>
                <option value="<?= e($code) ?>" <?= $code === ($edit['status'] ?? 'P') ? 'selected' : '' ?>><?= e($code) ?> – <?= e($statusLabels[$code]) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="span-2">Consultant Comments
            <textarea name="consultant_comments" rows="3"><?= e($edit['consultant_comments'] ?? '') ?></textarea>
        </label>

        <label class="span-2">Notes
            <textarea name="notes" rows="3"><?= e($edit['notes'] ?? '') ?></textarea>
        </label>

        <div class="span-2 mas-file-field">
            <label>Attached MAS File
                <div class="mas-file-input-wrap">
                    <?php $editMasHref=safe_document_href($edit['mas_file_link']??null);$hasLink=$editMasHref!==null; ?>
                    <input name="mas_file_link" id="masFileLinkInput"
                           value="<?= e($edit['mas_file_link'] ?? '') ?>"
                           placeholder="Paste file link here (URL)"
                           <?= $hasLink ? 'readonly class="mas-locked"' : '' ?>>
                    <?php if ($hasLink): ?>
                    <span class="mas-filename" id="masFileName"><?= e(basename(parse_url($edit['mas_file_link'], PHP_URL_PATH) ?: $edit['mas_file_link'])) ?></span>
                    <?php else: ?>
                    <span class="mas-filename" id="masFileName" style="display:none"></span>
                    <?php endif; ?>
                    <a href="<?= $hasLink ? e($editMasHref) : '#' ?>" target="_blank" rel="noopener noreferrer"
                       class="btn mas-view-btn <?= $hasLink ? '' : 'disabled' ?>"
                       id="masViewBtn" title="View MAS File"
                       <?= $hasLink ? '' : 'onclick="return false;"' ?>>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                    <button type="button" class="btn mas-clear-btn" id="masClearBtn" title="<?= $hasLink ? 'Remove file link' : 'Clear' ?>" <?= $hasLink ? '' : 'style="display:none"' ?>>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </label>
        </div>

        <div class="form-actions submittal-form-actions">
            <button class="btn" type="submit"><?= $edit ? 'Update Submittal' : 'Add Submittal' ?></button>
            <a class="btn ghost" href="submittals.php">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div><h2>Submittal Register</h2><p class="muted">Material submittals grouped by Project and Discipline.</p></div>
        <div class="panel-head-actions">
            <?php if (!$showForm && can_manage_submittals()): ?><a href="?add=1" class="btn">+ Add Material Submittal</a><?php endif; ?>
            <form class="search">
                <input name="q" placeholder="Search..." value="<?= e($q) ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <?php foreach ($statusCodes as $code): ?>
                    <option value="<?= e($code) ?>" <?= $filterStatus === $code ? 'selected' : '' ?>><?= e($code) ?> – <?= e($statusLabels[$code]) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn">Filter</button>
            </form>
        </div>
    </div>

    <div class="status-legend">
        <span class="legend-title">Status Legend:</span>
        <span class="legend-item"><i class="legend-dot" style="background:#22C55E"></i>A – Approved</span>
        <span class="legend-item"><i class="legend-dot" style="background:#84CC16"></i>B – Approved w/ Comments</span>
        <span class="legend-item"><i class="legend-dot" style="background:#F59E0B"></i>C – Resubmit</span>
        <span class="legend-item"><i class="legend-dot" style="background:#EF4444"></i>D – Rejected</span>
        <span class="legend-item"><i class="legend-dot" style="background:#3B82F6"></i>UR – Under Review</span>
        <span class="legend-item"><i class="legend-dot" style="background:#6B7280"></i>P – Planned</span>
    </div>

    <?php if (empty($grouped)): ?>
        <p class="empty">No submittals found.</p>
    <?php endif; ?>

    <?php foreach ($grouped as $projectName => $disciplines): ?>
    <div class="submittal-project-group">
        <div class="submittal-project-header" onclick="toggleSubmittalGroup(this)">
            <h3><span class="disc-toggle">&#9660;</span> <?= e($projectName) ?></h3>
        </div>
        <div class="submittal-project-body">
        <?php foreach ($disciplines as $discName => $items): ?>
            <div class="submittal-disc-group">
                <div class="submittal-disc-header">
                    <h4><?= e($discName) ?></h4>
                    <small><?= count($items) ?> submittal<?= count($items) !== 1 ? 's' : '' ?></small>
                </div>
                <div class="table-wrap">
                    <table class="submittal-table">
                        <thead><tr>
                            <th>Sub.Ref#</th>
                            <th>BOQ Ref.</th>
                            <th>Material Description</th>
                            <th>Manufacturer</th>
                            <th>Rev. No.</th>
                            <th>Date Submitted</th>
                            <th>Date Approve</th>
                            <th>Status</th>
                            <th>Consultant Comments</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($items as $r):
                            $sc = $r['status'] ?? 'P';
                            $statusColor = match($sc) {
                                'A'  => '#22C55E',
                                'B'  => '#84CC16',
                                'C'  => '#F59E0B',
                                'D'  => '#EF4444',
                                'UR' => '#3B82F6',
                                default => '#6B7280'
                            };
                            $statusFg = in_array($sc, ['C'], true) ? '#172033' : '#fff';
                        ?>
                        <tr>
                            <td data-label="Sub.Ref#"><?= e($r['submittal_reference'] ?? '') ?></td>
                            <td data-label="BOQ Ref."><?= e($r['boq_ref_no'] ?? '') ?></td>
                            <td data-label="Material Description"><?= e($r['material_description'] ?? '') ?></td>
                            <td data-label="Manufacturer"><?= e($r['manufacturer'] ?? '') ?></td>
                            <td data-label="Rev. No."><?= e($r['submittal_revision_no'] ?? '') ?></td>
                            <td data-label="Date Submitted"><?= e($r['submitted_date'] ?? '') ?></td>
                            <td data-label="Date Approve"><?= e($r['approved_date'] ?? '') ?></td>
                            <td data-label="Status"><span class="status-badge" style="background:<?= $statusColor ?>;color:<?= $statusFg ?>"><?= e($sc) ?> – <?= e($statusLabels[$sc] ?? $sc) ?></span></td>
                            <td data-label="Consultant Comments"><?= e($r['consultant_comments'] ?? '') ?></td>
                            <td data-label="Notes"><?= e($r['notes'] ?? '') ?></td>
                            <td class="actions" data-label="Actions">
                                <?php $rowMasHref=safe_document_href($r['mas_file_link']??null);if($rowMasHref!==null): ?>
                                <a href="<?= e($rowMasHref) ?>" target="_blank" rel="noopener noreferrer" class="act-icon act-mas" title="Open MAS File">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                </a>
                                <?php endif; ?>
                                <a href="#" class="act-icon act-view" title="View" onclick="openViewModal(<?= $r['id'] ?>);return false;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <?php if (can_manage_submittals()): ?><a href="?edit=<?= $r['id'] ?>" class="act-icon act-edit" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a><?php endif; ?>
                                <?php if (has_role('admin')): ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this submittal?')">
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
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<!-- BOQ Search Modal -->
<div class="boq-modal-overlay" id="boqModal" style="display:none" onclick="if(event.target===this)closeBoqModal()">
    <div class="boq-modal">
        <div class="boq-modal-header">
            <h3>Select BOQ Item</h3>
            <button type="button" class="btn ghost" onclick="closeBoqModal()">&times;</button>
        </div>
        <div class="boq-modal-search">
            <input type="text" id="boqModalSearch" placeholder="Search BOQ items..." oninput="filterBoqList()">
        </div>
        <div class="boq-modal-body" id="boqModalBody">
            <p class="muted" style="padding:20px;text-align:center">Select a project first, then click Search.</p>
        </div>
    </div>
</div>

<!-- Manufacturer List Modal -->
<div class="boq-modal-overlay" id="mfrModal" style="display:none" onclick="if(event.target===this)closeMfrModal()">
    <div class="boq-modal">
        <div class="boq-modal-header">
            <h3>Select Manufacturer</h3>
            <button type="button" class="btn ghost" onclick="closeMfrModal()">&times;</button>
        </div>
        <div class="boq-modal-search">
            <input type="text" id="mfrModalSearch" placeholder="Search manufacturer or country..." oninput="filterMfrList()">
        </div>
        <div class="boq-modal-body" id="mfrModalBody">
            <p class="muted" style="padding:20px;text-align:center">Loading manufacturers...</p>
        </div>
    </div>
</div>

<!-- View Submittal Modal -->
<div class="view-modal-overlay" id="viewModal" style="display:none" onclick="if(event.target===this)closeViewModal()">
    <div class="view-modal">
        <div class="view-modal-header">
            <span style="font-weight:700;font-size:14px;color:#334155">Material Submittal Details</span>
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

<style>
/* Panel head actions */
.panel-head-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.req{color:#EF4444}

/* Form grid */
.submittal-form-grid{grid-template-columns:1fr 1fr;gap:14px 20px}
.submittal-form-grid .span-2{grid-column:1/-1}
.submittal-form-actions{grid-column:1/-1;justify-content:flex-end}

/* BOQ Ref. No. field */
.boq-ref-input-wrap{display:flex;gap:6px}
.boq-ref-input-wrap input{flex:1}
.boq-search-btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;font-size:12px;white-space:nowrap}

/* Status legend */
.status-legend{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:10px 16px;margin-bottom:16px;background:#f8fafc;border-radius:8px;border:1px solid #e5e7eb}
.legend-title{font-weight:700;font-size:12px;color:#172033}
.legend-item{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#334155}
.legend-dot{display:inline-block;width:14px;height:14px;border-radius:3px;flex-shrink:0}

/* Status badge */
.status-badge{display:inline-block;padding:3px 10px;border-radius:5px;font-weight:700;font-size:11px;white-space:nowrap;letter-spacing:.02em}

/* Project group */
.submittal-project-group{margin-bottom:16px;border:1px solid #e2e7ef;border-radius:10px;overflow:hidden}
.submittal-project-header{background:#f1f5f9;padding:12px 16px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background .2s}
.submittal-project-header:hover{background:#e2e8f0}
.submittal-project-header h3{margin:0;font-size:15px;color:#172033}
.submittal-project-body{padding:0 12px 12px}

/* Discipline sub-group */
.submittal-disc-group{margin-top:12px}
.submittal-disc-header{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#0D9488;border-radius:6px 6px 0 0}
.submittal-disc-header h4{margin:0;font-size:13px;color:#fff;font-weight:700}
.submittal-disc-header small{color:#D1FAE5;font-size:11px}

/* Submittal table */
.submittal-table{width:100%;border-collapse:collapse;font-size:12px;min-width:1050px}
.submittal-table thead th{background:#f8fafc;font-weight:700;text-align:center;padding:9px 8px;border:1px solid #e5e7eb;white-space:nowrap;font-size:11px}
.submittal-table tbody td{padding:7px 8px;border:1px solid #e5e7eb;vertical-align:middle;text-align:center}
.submittal-table td:nth-child(3),
.submittal-table td:nth-child(9),
.submittal-table td:nth-child(10){text-align:left}

/* Collapse toggle */
.disc-toggle{font-size:12px;margin-right:4px;display:inline-block;transition:transform .2s}
.submittal-project-header.collapsed .disc-toggle{transform:rotate(-90deg)}

/* BOQ Modal */
.boq-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;display:flex;align-items:center;justify-content:center}
.boq-modal{background:#fff;border-radius:12px;width:95%;max-width:900px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.boq-modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e7eb}
.boq-modal-header h3{margin:0;font-size:16px}
.boq-modal-search{padding:12px 20px;border-bottom:1px solid #e5e7eb}
.boq-modal-search input{width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px}
.boq-modal-body{overflow-y:auto;flex:1;padding:0}
.boq-list-header{display:grid;grid-template-columns:10ch 1fr 1fr 1.4fr;gap:10px;padding:8px 20px;background:#f8fafc;border-bottom:2px solid #e5e7eb;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.boq-item{display:grid;grid-template-columns:10ch 1fr 1fr 1.4fr;gap:10px;padding:10px 20px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;align-items:start}
.boq-item:hover{background:#f0fdf4}
.boq-item.boq-duplicate{background:#fff7ed;border-left:4px solid #f97316;cursor:not-allowed}
.boq-item.boq-duplicate .boq-item-ref::after{content:' Duplicate';color:#c2410c;font-size:10px;text-transform:uppercase;margin-left:6px}
.boq-item-ref{font-weight:700;color:#0D9488;font-size:13px}
.boq-item-disc{font-size:12px;color:#64748b}
.boq-item-task{font-size:13px;color:#172033}
.boq-item-mat{font-size:12px;color:#475569;word-break:break-word;line-height:1.4}
.boq-highlight{background:#FEF08A;color:#172033;padding:1px 2px;border-radius:3px;font-weight:700}
.boq-empty{padding:30px;text-align:center;color:#94a3b8;font-size:13px}

/* Manufacturer field */
.mfr-input-wrap{display:flex;gap:6px}
.mfr-input-wrap input{flex:1}
.mfr-search-btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;font-size:12px;white-space:nowrap}
.mfr-item{display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s}
.mfr-item:hover{background:#f0fdf4}
.mfr-item-name{font-weight:700;color:#0D9488;flex:1;font-size:13px}
.mfr-item-country{font-size:12px;color:#64748b;min-width:120px;text-align:right}
.mfr-item-country::before{content:'';display:inline-block;width:14px;height:14px;margin-right:5px;vertical-align:middle;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='2' y1='12' x2='22' y2='12'/%3E%3Cpath d='M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z'/%3E%3C/svg%3E") no-repeat center}

/* MAS File field */
.mas-file-field{margin-bottom:0}
.mas-file-input-wrap{display:flex;gap:8px;align-items:center}
.mas-file-input-wrap input{flex:1}
.mas-file-input-wrap input.mas-locked{background:#f1f5f9;color:#64748b;cursor:not-allowed}
.mas-filename{flex:1;padding:7px 12px;font-size:13px;font-weight:600;color:#0D9488;background:#f0fdfa;border:1px solid #99f6e4;border-radius:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:6px}
.mas-filename::before{content:'';display:inline-block;width:16px;height:16px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%230D9488' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpolyline points='14 2 14 8 20 8'/%3E%3C/svg%3E") no-repeat center;flex-shrink:0}
.mas-view-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;font-size:12px;font-weight:600;white-space:nowrap;color:#0D9488;border:1px solid #0D9488;border-radius:6px;text-decoration:none;transition:all .15s}
.mas-view-btn:hover:not(.disabled){background:#0D9488;color:#fff}
.mas-view-btn.disabled{opacity:.4;cursor:not-allowed;pointer-events:none;color:#94a3b8;border-color:#cbd5e1}
.mas-clear-btn{display:inline-flex;align-items:center;padding:6px 8px;font-size:12px;color:#EF4444;border:1px solid #fca5a5;border-radius:6px;background:#fff;cursor:pointer;transition:all .15s}
.mas-clear-btn:hover{background:#FEF2F2;border-color:#EF4444}
.sv-mas-link{display:inline-flex;align-items:center;gap:5px;color:#0D9488;font-weight:600;text-decoration:none;font-size:13px;transition:color .15s}
.sv-mas-link:hover{color:#0a7a70;text-decoration:underline}

/* Action icon buttons */
.actions{white-space:nowrap;display:flex;align-items:center;gap:4px;justify-content:center}
.act-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:28px;border-radius:5px;border:1px solid transparent;text-decoration:none;transition:all .15s;cursor:pointer;position:relative;background:none}
.act-icon svg{flex-shrink:0}
.act-mas{color:#3B82F6;border-color:#93c5fd}
.act-mas:hover{background:#EFF6FF;border-color:#3B82F6}
.act-view{color:#0D9488;border-color:#99f6e4}
.act-view:hover{background:#f0fdfa;border-color:#0D9488}
.act-edit{color:#F59E0B;border-color:#fde68a}
.act-edit:hover{background:#FFFBEB;border-color:#F59E0B}
.act-delete{color:#EF4444;border-color:#fca5a5;padding:0;font-size:0;line-height:1}
.act-delete:hover{background:#FEF2F2;border-color:#EF4444}
/* Tooltip */
.act-icon[title]{position:relative}
.act-icon[title]:hover::after{content:attr(title);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);padding:4px 10px;background:#1e293b;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;border-radius:5px;pointer-events:none;z-index:10}
.act-icon[title]:hover::before{content:'';position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1e293b;pointer-events:none;z-index:10}

/* View Modal Overlay */
.view-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}
.view-modal{background:#fff;border-radius:12px;width:100%;max-width:800px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.25);overflow:hidden}
.view-modal-header{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb}
.view-modal-header-actions{display:flex;gap:8px;align-items:center}
.view-modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;padding:4px 8px;border-radius:4px}
.view-modal-close:hover{background:#e5e7eb;color:#172033}
.view-modal-print{display:inline-flex;align-items:center;gap:4px;padding:5px 14px;font-size:12px;font-weight:600;color:#0D9488;border:1px solid #0D9488;border-radius:6px;background:none;cursor:pointer;transition:all .15s}
.view-modal-print:hover{background:#0D9488;color:#fff}
.view-modal-body{overflow-y:auto;flex:1;padding:0}

/* Professional Submittal View Form */
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
    .submittal-form-grid{grid-template-columns:1fr}
    .submittal-form-grid .span-2{grid-column:1}
    .status-legend{flex-direction:column;align-items:flex-start;gap:6px}
}
@media(max-width:700px){
    .submittal-table{min-width:0}
    .submittal-table thead{display:none}
    .submittal-table tbody{display:block}
    .submittal-table tr{display:block;margin-bottom:12px;border:1px solid #e2e7ef;border-radius:10px;padding:10px;background:#fff}
    .submittal-table td{display:flex;justify-content:space-between;gap:10px;border:0;border-bottom:1px solid #f1f4f9;padding:7px 2px;text-align:right}
    .submittal-table td:last-child{border-bottom:0}
    .submittal-table td::before{content:attr(data-label);font-size:11px;font-weight:800;text-transform:uppercase;color:#8a95a8;text-align:left;flex:0 0 40%}
}
</style>

<script>
// Submittal data for View modal
const submittalData = <?php
    $viewData = [];
    foreach ($rows as $r) {
        $sc = $r['status'] ?? 'P';
        $viewData[$r['id']] = [
            'project_name'         => $r['project_name'] ?? '',
            'discipline'           => $r['discipline'] ?? '',
            'boq_ref_no'           => $r['boq_ref_no'] ?? '',
            'submittal_reference'  => $r['submittal_reference'] ?? '',
            'material_description' => $r['material_description'] ?? '',
            'manufacturer'         => $r['manufacturer'] ?? '',
            'country_origin'       => $r['country_origin'] ?? '',
            'submittal_revision_no'=> $r['submittal_revision_no'] ?? '',
            'submitted_date'       => $r['submitted_date'] ?? '',
            'approved_date'        => $r['approved_date'] ?? '',
            'status'               => $sc,
            'status_label'         => ($statusLabels[$sc] ?? $sc),
            'mas_file_link'        => safe_document_href($r['mas_file_link']??null) ?? '',
            'consultant_comments'  => $r['consultant_comments'] ?? '',
            'notes'                => $r['notes'] ?? '',
        ];
    }
    echo json_encode($viewData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES);
?>;

const statusColors = {
    'A':  {bg:'#22C55E', fg:'#fff'},
    'B':  {bg:'#84CC16', fg:'#fff'},
    'C':  {bg:'#F59E0B', fg:'#172033'},
    'D':  {bg:'#EF4444', fg:'#fff'},
    'UR': {bg:'#3B82F6', fg:'#fff'},
    'P':  {bg:'#6B7280', fg:'#fff'}
};

function openViewModal(id) {
    const d = submittalData[id];
    if (!d) return;
    const sc = statusColors[d.status] || statusColors['P'];
    const badge = '<span class="status-badge" style="background:'+sc.bg+';color:'+sc.fg+'">'+escH(d.status)+' &ndash; '+escH(d.status_label)+'</span>';

    // MAS File link row
    let masRow = '';
    if (d.mas_file_link) {
        const masName = extractFilename(d.mas_file_link);
        masRow = '<div class="sv-field full">' +
            '<div class="sv-field-label">Attached MAS File</div>' +
            '<div class="sv-field-value"><a href="'+escH(d.mas_file_link)+'" target="_blank" rel="noopener noreferrer" class="sv-mas-link">' +
            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> ' +
            escH(masName) + '</a></div></div>';
    } else {
        masRow = '<div class="sv-field full">' +
            '<div class="sv-field-label">Attached MAS File</div>' +
            '<div class="sv-field-value" style="color:#94a3b8;font-style:italic">No file attached</div></div>';
    }

    document.getElementById('viewModalBody').innerHTML =
    '<div class="sv-form">' +
        '<div class="sv-logos">' +
            '<img src="assets/img/logo-reversed-white.png" alt="MEP Projects Portal">' +
            '<img src="assets/img/aalatech-logo.png" alt="Aala Tech">' +
        '</div>' +
        '<div class="sv-title-bar"><h2>Material Submittal Form</h2></div>' +
        '<div class="sv-field-grid">' +
            svField('Project Name', d.project_name) +
            svField('Discipline', d.discipline) +
            svField('BOQ Ref. No.', d.boq_ref_no) +
            svField('MAS Ref. No#', d.submittal_reference) +
            svField('Material Description', d.material_description, true) +
            svField('Manufacturer', d.manufacturer) +
            svField('Country Origin', d.country_origin) +
            svField('Submittal Rev. #', d.submittal_revision_no) +
            svField('Date Submitted', d.submitted_date) +
            svField('Date Approved', d.approved_date) +
            svFieldRaw('Submittal Status', badge) +
            masRow +
            '<div class="sv-field full">' +
                '<div class="sv-field-label">Consultant Comments</div>' +
                '<div class="sv-field-value sv-textarea">' + escH(d.consultant_comments || '—') + '</div>' +
            '</div>' +
            '<div class="sv-field full">' +
                '<div class="sv-field-label">Notes</div>' +
                '<div class="sv-field-value sv-textarea">' + escH(d.notes || '—') + '</div>' +
            '</div>' +
        '</div>' +
        '<div class="sv-footer"><small>MEP Projects Portal &bull; Generated on ' + new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) + '</small></div>' +
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

let boqData = [];

function openBoqModal() {
    const projectSelect = document.getElementById('submittalProjectSelect');
    const projectId = projectSelect ? projectSelect.value : '';
    if (!projectId) {
        alert('Please select a project first.');
        return;
    }
    document.getElementById('boqModal').style.display = 'flex';
    document.getElementById('boqModalSearch').value = '';
    document.getElementById('boqModalBody').innerHTML = '<p class="boq-empty">Loading...</p>';

    fetch('ajax_boq_list.php?project_id=' + encodeURIComponent(projectId))
        .then(r => r.json())
        .then(data => {
            boqData = data;
            renderBoqList(data);
        })
        .catch(() => {
            document.getElementById('boqModalBody').innerHTML = '<p class="boq-empty">Failed to load BOQ items.</p>';
        });
}

function closeBoqModal() {
    document.getElementById('boqModal').style.display = 'none';
}

function highlightText(text, query) {
    if (!query || !text) return escH(text || '');
    const escaped = escH(text);
    const qEsc = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return escaped.replace(new RegExp('(' + qEsc + ')', 'gi'), '<mark class="boq-highlight">$1</mark>');
}

function renderBoqList(items, query) {
    const body = document.getElementById('boqModalBody');
    if (!items.length) {
        body.innerHTML = '<p class="boq-empty">No BOQ items found for this project.</p>';
        return;
    }
    const q = query || '';
    let html = '<div class="boq-list-header"><span>BOQ No.</span><span>Discipline</span><span>Task</span><span>Material Description</span></div>';
    items.forEach(item => {
        const ref = item.boq_no || '—';
        const duplicateClass = Number(item.duplicate_count) > 1 ? ' boq-duplicate' : '';
        html += '<div class="boq-item'+duplicateClass+'" onclick="selectBoq(this)" data-id="'+Number(item.id)+'" data-ref="' + escH(item.boq_no) + '" data-duplicate="'+Number(item.duplicate_count||1)+'">'
            + '<span class="boq-item-ref">' + highlightText(ref, q) + '</span>'
            + '<span class="boq-item-disc">' + highlightText(item.discipline, q) + '</span>'
            + '<span class="boq-item-task">' + highlightText(item.task, q) + '</span>'
            + '<span class="boq-item-mat">' + highlightText(item.material_description || '—', q) + '</span>'
            + '</div>';
    });
    body.innerHTML = html;
}

function filterBoqList() {
    const q = document.getElementById('boqModalSearch').value.trim();
    if (!q) { renderBoqList(boqData); return; }
    const ql = q.toLowerCase();
    const filtered = boqData.filter(item =>
        (item.boq_no || '').toLowerCase().includes(ql) ||
        (item.task || '').toLowerCase().includes(ql) ||
        (item.discipline || '').toLowerCase().includes(ql) ||
        (item.material_description || '').toLowerCase().includes(ql)
    );
    renderBoqList(filtered, q);
}

function selectBoq(el) {
    if(Number(el.getAttribute('data-duplicate'))>1){alert('This BOQ reference is duplicated. Resolve the duplicate measurable items before linking a submittal.');return;}
    const ref = el.getAttribute('data-ref');
    document.getElementById('boqProgressIdInput').value = el.getAttribute('data-id');
    document.getElementById('boqRefNoInput').value = ref;
    closeBoqModal();
}

function escH(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function toggleSubmittalGroup(el) {
    const body = el.closest('.submittal-project-group').querySelector('.submittal-project-body');
    const icon = el.querySelector('.disc-toggle');
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

// ---- Manufacturer List Modal ----
let mfrData = [];

function openMfrModal() {
    document.getElementById('mfrModal').style.display = 'flex';
    document.getElementById('mfrModalSearch').value = '';
    document.getElementById('mfrModalBody').innerHTML = '<p class="boq-empty">Loading...</p>';

    fetch('ajax_mfr_list.php')
        .then(r => r.json())
        .then(data => {
            mfrData = data;
            renderMfrList(data);
        })
        .catch(() => {
            document.getElementById('mfrModalBody').innerHTML = '<p class="boq-empty">Failed to load manufacturers.</p>';
        });
}

function closeMfrModal() {
    document.getElementById('mfrModal').style.display = 'none';
}

function renderMfrList(items) {
    const body = document.getElementById('mfrModalBody');
    if (!items.length) {
        body.innerHTML = '<p class="boq-empty">No manufacturers found.</p>';
        return;
    }
    let html = '';
    items.forEach(item => {
        html += '<div class="mfr-item" onclick="selectMfr(this)" data-mfr="' + escH(item.manufacturer) + '" data-country="' + escH(item.country_origin || '') + '">'
            + '<span class="mfr-item-name">' + escH(item.manufacturer) + '</span>'
            + '<span class="mfr-item-country">' + escH(item.country_origin || '—') + '</span>'
            + '</div>';
    });
    body.innerHTML = html;
}

function filterMfrList() {
    const q = document.getElementById('mfrModalSearch').value.toLowerCase();
    if (!q) { renderMfrList(mfrData); return; }
    const filtered = mfrData.filter(item =>
        (item.manufacturer || '').toLowerCase().includes(q) ||
        (item.country_origin || '').toLowerCase().includes(q)
    );
    renderMfrList(filtered);
}

function selectMfr(el) {
    const mfr = el.getAttribute('data-mfr');
    const country = el.getAttribute('data-country');
    document.getElementById('mfrInput').value = mfr;
    document.getElementById('countryOriginInput').value = country;
    closeMfrModal();
}

// MAS File link — lock/unlock, filename display, view toggle
const masInput = document.getElementById('masFileLinkInput');
const masViewBtn = document.getElementById('masViewBtn');
const masClearBtn = document.getElementById('masClearBtn');
const masFileName = document.getElementById('masFileName');

function extractFilename(url) {
    try {
        const path = new URL(url).pathname;
        const name = path.substring(path.lastIndexOf('/') + 1);
        return decodeURIComponent(name) || url;
    } catch(e) {
        const parts = url.split('/');
        return parts[parts.length - 1] || url;
    }
}

function lockMasField() {
    const val = masInput.value.trim();
    if (!val) return;
    masInput.readOnly = true;
    masInput.classList.add('mas-locked');
    masFileName.textContent = extractFilename(val);
    masFileName.style.display = '';
    masInput.style.display = 'none';
    masViewBtn.href = val;
    masViewBtn.classList.remove('disabled');
    masViewBtn.onclick = null;
    masClearBtn.style.display = '';
    masClearBtn.title = 'Remove file link';
}

function unlockMasField() {
    masInput.value = '';
    masInput.readOnly = false;
    masInput.classList.remove('mas-locked');
    masInput.style.display = '';
    masFileName.style.display = 'none';
    masViewBtn.href = '#';
    masViewBtn.classList.add('disabled');
    masViewBtn.onclick = function() { return false; };
    masClearBtn.style.display = 'none';
    masInput.focus();
}

if (masInput && masViewBtn && masClearBtn && masFileName) {
    // On paste or input — auto-lock when a URL is entered
    masInput.addEventListener('change', function() {
        const val = this.value.trim();
        if (val) {
            lockMasField();
        }
    });
    masInput.addEventListener('paste', function() {
        setTimeout(() => {
            const val = this.value.trim();
            if (val) lockMasField();
        }, 50);
    });

    // Clear/remove button
    masClearBtn.addEventListener('click', function() {
        unlockMasField();
    });

    // If already locked on load (edit mode with existing link)
    if (masInput.readOnly && masInput.value.trim()) {
        masInput.style.display = 'none';
        masFileName.style.display = '';
    }
}

// Close modals on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeBoqModal(); closeMfrModal(); closeViewModal(); }
});
</script>

<?php include __DIR__.'/../../../includes/footer.php'; ?>
