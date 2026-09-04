<?php include __DIR__.'/../../../includes/header.php'; ?>
<?php if ($showForm): ?>
<section class="panel proc-form-panel">
    <div class="panel-head">
        <div>
            <h2><?= $edit ? 'Edit Procurement Record' : 'Add Procurement Record' ?></h2>
            <p class="muted"><?= $edit ? 'Update procurement entry details.' : 'Register a new procurement entry.' ?></p>
        </div>
    </div>
    <form method="post" class="form-grid proc-form-grid" id="procForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <input type="hidden" name="submittal_reference_id" id="submittalRefId" value="<?= e($edit['submittal_reference_id'] ?? '') ?>">

        <!-- MAS Submittal Information -->
        <div class="proc-section-divider"><span>MAS Submittal Information</span></div>

        <label>Project <span class="req">*</span>
            <select name="project_id" id="projectSelect" required>
                <option value="">-- Select Project --</option>
                <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == ($edit['project_id'] ?? 0) ? 'selected' : '' ?>><?= e($p['project_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>MAS Submittal Ref No.
            <div class="mas-ref-wrap">
                <input type="text" id="masRefDisplay" value="<?=e($editMasReference)?>" readonly placeholder="Click Search to select">
                <button type="button" class="btn mas-search-btn" id="btnSearchMAS">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Search
                </button>
            </div>
        </label>

        <label>Material Description <span class="req">*</span>
            <input name="material_description" id="matDesc" required readonly value="<?= e($edit['material_description'] ?? '') ?>" placeholder="Auto-filled from MAS Search">
        </label>

        <label>Manufacturer
            <input name="manufacturer" id="masManufacturer" readonly value="<?= e($edit['manufacturer'] ?? '') ?>" placeholder="Auto-filled from MAS Search">
        </label>

        <label>BOQ Ref. No.
            <input name="boq_ref_no" id="boqRef" readonly value="<?= e($edit['boq_ref_no'] ?? '') ?>" placeholder="Auto-filled from MAS Search">
        </label>

        <label>MAS Approved Date
            <input type="date" name="approved_date" id="approvedDate" readonly value="<?= e($edit['approved_date'] ?? '') ?>">
        </label>

        <!-- Procurement Details -->
        <div class="proc-section-divider"><span>Procurement Details</span></div>

        <label class="span-2">Supplier
            <input name="supplier" value="<?= e($edit['supplier'] ?? '') ?>">
        </label>

        <label>Status
            <select name="status" id="statusSelect">
                <?php 
                $tooltips = [
                    'Not Started' => 'Preparing the required material quantities / Sending RFQ to Supplier',
                    'Purchase Order (PO) Issued' => 'PO has been sent to the supplier for confirmation. PO Issued, Awaiting Supplier Confirmation',
                    'Delivery Expected' => 'Delivery date pop-up picker',
                    'Good Received / Delivered' => 'Items physically received at site or warehouse.'
                ];
                foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>" title="<?= e($tooltips[$s] ?? '') ?>" <?= $s === ($edit['status'] ?? 'Not Started') ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>PO Date
            <input type="date" name="po_date" value="<?= e($edit['po_date'] ?? '') ?>">
        </label>
        <label>Required on Site Date
            <input type="date" name="required_date" value="<?= e($edit['required_date'] ?? '') ?>">
        </label>
        <label>Expected Delivery Date
            <input type="date" name="expected_delivery_date" id="expectedDeliveryDate" value="<?= e($edit['expected_delivery_date'] ?? '') ?>">
        </label>
        <label>Actual Delivery Date
            <input type="date" name="actual_delivery_date" value="<?= e($edit['actual_delivery_date'] ?? '') ?>">
        </label>
        <label>Currency
            <select name="currency"><?php foreach($currencies as $currency): ?><option value="<?=e($currency)?>" <?=$currency===($edit['currency']??DEFAULT_CURRENCY)?'selected':''?>><?=e($currency)?></option><?php endforeach; ?></select>
        </label>

        <!-- Remarks -->
        <label class="span-2">Remarks
            <textarea name="remarks" id="remarksField" rows="3"><?= e($edit['remarks'] ?? '') ?></textarea>
        </label>

        <div class="form-actions proc-form-actions">
            <button class="btn" type="submit"><?= $edit ? 'Update Record' : 'Add Record' ?></button>
            <a class="btn ghost" href="procurement.php">Cancel</a>
        </div>
    </form>

    <!-- Date Picker Pop-up for Delivery Expected -->
    <div id="datePickOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9998;"></div>
    <div id="datePickModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; padding:24px; border:1px solid #e3e9f2; box-shadow:0 20px 55px rgba(25,47,89,0.12); z-index:9999; border-radius:14px; width: 320px;">
        <h4 style="margin-top:0; color:#0F172A; margin-bottom:16px;">Expected Delivery Date</h4>
        <input type="date" id="popupDateInput" style="margin-bottom:20px; width:100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
        <div style="text-align:right; display:flex; gap:8px; justify-content:flex-end;">
            <button type="button" class="btn ghost" id="popupCancelBtn">Cancel</button>
            <button type="button" class="btn" id="popupOkBtn">OK</button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- MAS Submittal Search Modal -->
<div class="mas-modal-overlay" id="masModal" style="display:none" onclick="if(event.target===this)closeMasModal()">
    <div class="mas-modal">
        <div class="mas-modal-head">
            <h3>Select MAS Submittal (Approved / Approved as Noted)</h3>
            <button type="button" class="btn ghost" onclick="closeMasModal()">&times;</button>
        </div>
        <div class="mas-modal-body">
            <div class="mas-search-row">
                <input type="text" id="masSearchQ" placeholder="Search by reference, description, or BOQ...">
                <button id="masSearchBtn">Search</button>
            </div>
            <div id="masResults"><p class="mas-no-results">Select a project first, then click Search.</p></div>
        </div>
    </div>
</div>

<!-- View Procurement Detail Modal -->
<div class="view-modal-overlay" id="viewModal" style="display:none" onclick="if(event.target===this)closeViewModal()">
    <div class="view-modal">
        <div class="view-modal-header">
            <span style="font-weight:700;font-size:14px;color:#334155">Procurement Record Details</span>
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
            <h2>Procurement Register</h2>
            <p class="muted">Procurement records grouped by Project.</p>
        </div>
        <div class="panel-head-actions">
            <?php if (!$showForm && can_manage_procurement()): ?><a href="?add=1" class="btn">+ Add Procurement Record</a><?php endif; ?>
            <form class="search">
                <input name="q" placeholder="Search..." value="<?= e($q) ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn">Filter</button>
            </form>
        </div>
    </div>

    <div class="status-legend">
        <span class="legend-title">Status Legend:</span>
        <?php foreach ($statusColors as $sName => $sCol): ?>
        <span class="legend-item"><i class="legend-dot" style="background:<?= $sCol['bg'] ?>"></i><?= e($sName) ?></span>
        <?php endforeach; ?>
    </div>

    <?php if (empty($grouped)): ?>
        <p class="empty">No procurement records found.</p>
    <?php endif; ?>

    <?php foreach ($grouped as $projectName => $items): ?>
    <div class="proc-project-group">
        <div class="proc-project-header" onclick="toggleProcGroup(this)">
            <h3><span class="proc-toggle">&#9660;</span> <?= e($projectName) ?></h3>
            <small><?= count($items) ?> record<?= count($items) !== 1 ? 's' : '' ?></small>
        </div>
        <div class="proc-project-body">
            <div class="table-wrap">
                <table class="proc-table">
                    <thead><tr>
                        <th>MAS Ref.</th>
                        <th>BOQ Ref.</th>
                        <th>Material Description</th>
                        <th>Manufacturer</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Currency</th>
                        <th>Expected Delivery</th>
                        <th>Actual Delivery</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $sc = $r['status'] ?? 'Not Started';
                        $sCol = $statusColors[$sc] ?? ['bg' => '#6B7280', 'fg' => '#fff'];
                    ?>
                    <tr>
                        <td data-label="MAS Ref."><?= e($submittalMap[$r['submittal_reference_id']] ?? '') ?></td>
                        <td data-label="BOQ Ref."><?= e($r['boq_ref_no'] ?? '') ?></td>
                        <td data-label="Material Description"><?= e($r['material_description']) ?></td>
                        <td data-label="Manufacturer"><?= e($r['manufacturer'] ?? '') ?></td>
                        <td data-label="Supplier"><?= e($r['supplier']) ?></td>
                        <td data-label="Status"><span class="status-badge" style="background:<?= $sCol['bg'] ?>;color:<?= $sCol['fg'] ?>"><?= e($sc) ?></span></td>
                        <td data-label="Currency"><?=e($r['currency']??DEFAULT_CURRENCY)?></td>
                        <td data-label="Expected Delivery"><?= e($r['expected_delivery_date'] ?? '') ?></td>
                        <td data-label="Actual Delivery"><?= e($r['actual_delivery_date'] ?? '') ?></td>
                        <td data-label="Remarks"><?= e($r['remarks'] ?? '') ?></td>
                        <td class="actions" data-label="Actions">
                            <a href="#" class="act-icon act-view" title="View" onclick="openViewModal(<?= $r['id'] ?>);return false;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <?php if (can_manage_procurement()): ?><a href="?edit=<?= $r['id'] ?>" class="act-icon act-edit" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a><?php endif; ?>
                            <?php if (has_role('admin')): ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('Delete this procurement record?')">
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
.proc-form-grid{grid-template-columns:1fr 1fr;gap:14px 20px}
.proc-form-grid .span-2{grid-column:1/-1}
.proc-form-actions{grid-column:1/-1;justify-content:flex-end}

/* Section divider inside form */
.proc-section-divider{grid-column:1/-1;margin:6px 0 2px;padding-bottom:0}
.proc-section-divider span{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700}

/* MAS Ref field */
.mas-ref-wrap{display:flex;gap:6px}
.mas-ref-wrap input{flex:1}
.mas-search-btn{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;font-size:12px;white-space:nowrap}

/* Readonly fields */
input[readonly]{background:#f1f5f9;color:#64748b;cursor:not-allowed}

/* Status legend */
.status-legend{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:10px 16px;margin-bottom:16px;background:#f8fafc;border-radius:8px;border:1px solid #e5e7eb}
.legend-title{font-weight:700;font-size:12px;color:#172033}
.legend-item{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#334155}
.legend-dot{display:inline-block;width:14px;height:14px;border-radius:3px;flex-shrink:0}

/* Status badge */
.status-badge{display:inline-block;padding:3px 10px;border-radius:5px;font-weight:700;font-size:11px;white-space:nowrap;letter-spacing:.02em}

/* Project group (accordion) */
.proc-project-group{margin-bottom:16px;border:1px solid #e2e7ef;border-radius:10px;overflow:hidden}
.proc-project-header{background:#f1f5f9;padding:12px 16px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background .2s}
.proc-project-header:hover{background:#e2e8f0}
.proc-project-header h3{margin:0;font-size:15px;color:#172033}
.proc-project-header small{color:#64748b;font-size:12px;font-weight:600}
.proc-project-body{padding:0 12px 12px}

/* Collapse toggle */
.proc-toggle{font-size:12px;margin-right:4px;display:inline-block;transition:transform .2s}
.proc-project-header.collapsed .proc-toggle{transform:rotate(-90deg)}

/* Procurement table */
.proc-table{width:100%;border-collapse:collapse;font-size:12px;min-width:1050px}
.proc-table thead th{background:#f8fafc;font-weight:700;text-align:center;padding:9px 8px;border:1px solid #e5e7eb;white-space:nowrap;font-size:11px}
.proc-table tbody td{padding:7px 8px;border:1px solid #e5e7eb;vertical-align:middle;text-align:center}
.proc-table td:nth-child(3),
.proc-table td:nth-child(9){text-align:left}

/* Action icon buttons */
.actions{white-space:nowrap;display:flex;align-items:center;gap:4px;justify-content:center}
.act-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:28px;border-radius:5px;border:1px solid transparent;text-decoration:none;transition:all .15s;cursor:pointer;position:relative;background:none}
.act-icon svg{flex-shrink:0}
.act-view{color:#0D9488;border-color:#99f6e4}
.act-view:hover{background:#f0fdfa;border-color:#0D9488}
.act-edit{color:#F59E0B;border-color:#fde68a}
.act-edit:hover{background:#FFFBEB;border-color:#F59E0B}
.act-delete{color:#EF4444;border-color:#fca5a5;padding:0;font-size:0;line-height:1}
.act-delete:hover{background:#FEF2F2;border-color:#EF4444}

/* Tooltip (CSS-only) */
.act-icon[title]{position:relative}
.act-icon[title]:hover::after{content:attr(title);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);padding:4px 10px;background:#1e293b;color:#fff;font-size:11px;font-weight:600;white-space:nowrap;border-radius:5px;pointer-events:none;z-index:10}
.act-icon[title]:hover::before{content:'';position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1e293b;pointer-events:none;z-index:10}

/* MAS Modal */
.mas-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;display:flex;align-items:center;justify-content:center}
.mas-modal{background:#fff;border-radius:12px;width:95%;max-width:850px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.mas-modal-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e5e7eb}
.mas-modal-head h3{margin:0;font-size:1.05rem}
.mas-modal-body{padding:14px 18px;overflow-y:auto;flex:1}
.mas-search-row{display:flex;gap:8px;margin-bottom:12px}
.mas-search-row input{flex:1;padding:7px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:.9rem}
.mas-search-row button{padding:7px 14px;border:none;border-radius:6px;background:#3b82f6;color:#fff;cursor:pointer;font-size:.85rem;white-space:nowrap}
.mas-table{width:100%;border-collapse:collapse;font-size:.85rem}
.mas-table th,.mas-table td{padding:8px 10px;text-align:left;border-bottom:1px solid #e8eaed}
.mas-table th{background:#f8fafc;font-weight:600;position:sticky;top:0}
.mas-table tr.selectable{cursor:pointer}
.mas-table tr.selectable:hover{background:#eef3ff}
.mas-table .badge-sm{font-size:.75rem;padding:2px 8px;border-radius:4px;background:#e8f5e9;font-weight:600}
.mas-no-results{text-align:center;color:#888;padding:24px 0}

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
    .proc-form-grid{grid-template-columns:1fr}
    .proc-form-grid .span-2{grid-column:1}
    .status-legend{flex-direction:column;align-items:flex-start;gap:6px}
}
@media(max-width:700px){
    .proc-table{min-width:0}
    .proc-table thead{display:none}
    .proc-table tbody{display:block}
    .proc-table tr{display:block;margin-bottom:12px;border:1px solid #e2e7ef;border-radius:10px;padding:10px;background:#fff}
    .proc-table td{display:flex;justify-content:space-between;gap:10px;border:0;border-bottom:1px solid #f1f4f9;padding:7px 2px;text-align:right}
    .proc-table td:last-child{border-bottom:0}
    .proc-table td::before{content:attr(data-label);font-size:11px;font-weight:800;text-transform:uppercase;color:#8a95a8;text-align:left;flex:0 0 40%}
}
</style>

<script>
/* --- Embedded JSON blob for View modal (no extra AJAX) --- */
const procData = <?php
    $viewData = [];
    foreach ($rows as $r) {
        $sc = $r['status'] ?? 'Not Started';
        $viewData[$r['id']] = [
            'project_name'         => $r['project_name'] ?? '',
            'mas_ref'              => $submittalMap[$r['submittal_reference_id']] ?? '',
            'boq_ref_no'           => $r['boq_ref_no'] ?? '',
            'material_description' => $r['material_description'] ?? '',
            'manufacturer'         => $r['manufacturer'] ?? '',
            'approved_date'        => $r['approved_date'] ?? '',
            'po_date'              => $r['po_date'] ?? '',
            'required_date'        => $r['required_date'] ?? '',
            'expected_delivery_date'=> $r['expected_delivery_date'] ?? '',
            'actual_delivery_date' => $r['actual_delivery_date'] ?? '',
            'supplier'             => $r['supplier'] ?? '',
            'status'               => $sc,
            'remarks'              => $r['remarks'] ?? '',
        ];
    }
    echo json_encode($viewData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES);
?>;

const procStatusColors = {
    'Not Started':                {bg:'#6B7280', fg:'#fff'},
    'Purchase Order (PO) Issued': {bg:'#8B5CF6', fg:'#fff'},
    'Delivery Expected':          {bg:'#F59E0B', fg:'#172033'},
    'Good Received / Delivered':  {bg:'#22C55E', fg:'#fff'}
};

/* --- Dynamic Status Tooltips & Remarks Automation --- */
const statusSelect = document.getElementById('statusSelect');
const remarksField = document.getElementById('remarksField');
const dateModal = document.getElementById('datePickModal');
const dateOverlay = document.getElementById('datePickOverlay');
const popupDateInput = document.getElementById('popupDateInput');
const popupOkBtn = document.getElementById('popupOkBtn');
const popupCancelBtn = document.getElementById('popupCancelBtn');
const expectedDeliveryDate = document.getElementById('expectedDeliveryDate');

const tooltips = {
    'Not Started': 'Preparing the required material quantities / Sending RFQ to Supplier',
    'Purchase Order (PO) Issued': 'PO has been sent to the supplier for confirmation. PO Issued, Awaiting Supplier Confirmation',
    'Delivery Expected': 'Delivery date pop-up picker',
    'Good Received / Delivered': 'Items physically received at site or warehouse.'
};

function appendToRemarks(text) {
    if (!remarksField) return;
    let current = remarksField.value.trim();
    if (current && !current.endsWith('.')) {
        current += '.\n';
    } else if (current) {
        current += '\n';
    }
    remarksField.value = current + text;
}

if (statusSelect) {
    // Set dynamic title on the select box itself so it works across browsers
    statusSelect.title = tooltips[statusSelect.value] || '';

    statusSelect.addEventListener('change', function() {
        const val = this.value;
        this.title = tooltips[val] || '';

        if (val === 'Not Started') {
            appendToRemarks('Preparing the required material quantities / Sending RFQ to Supplier');
        } else if (val === 'Purchase Order (PO) Issued') {
            appendToRemarks('PO has been sent to the supplier for confirmation. PO Issued, Awaiting Supplier Confirmation');
        } else if (val === 'Delivery Expected') {
            dateModal.style.display = 'block';
            dateOverlay.style.display = 'block';
            popupDateInput.value = '';
        } else if (val === 'Good Received / Delivered') {
            appendToRemarks('Items physically received at site or warehouse.');
        }
    });
}

if (popupOkBtn) {
    popupOkBtn.addEventListener('click', function() {
        const d = popupDateInput.value;
        if(d) {
            expectedDeliveryDate.value = d;
        }
        dateModal.style.display = 'none';
        dateOverlay.style.display = 'none';
    });
}

if (popupCancelBtn) {
    popupCancelBtn.addEventListener('click', function() {
        dateModal.style.display = 'none';
        dateOverlay.style.display = 'none';
    });
}


/* --- View Modal --- */
function openViewModal(id) {
    const d = procData[id];
    if (!d) return;
    const sc = procStatusColors[d.status] || procStatusColors['Not Started'];
    const badge = '<span class="status-badge" style="background:'+sc.bg+';color:'+sc.fg+'">'+escH(d.status)+'</span>';

    document.getElementById('viewModalBody').innerHTML =
    '<div class="sv-form">' +
        '<div class="sv-logos">' +
            '<img src="assets/img/logo-reversed-white.png" alt="MEP Projects Portal">' +
            '<img src="assets/img/aalatech-logo.png" alt="Aala Tech">' +
        '</div>' +
        '<div class="sv-title-bar"><h2>Procurement Record</h2></div>' +
        '<div class="sv-field-grid">' +
            svField('Project Name', d.project_name, true) +
            svField('MAS Ref. No.', d.mas_ref) +
            svField('BOQ Ref. No.', d.boq_ref_no) +
            svField('Material Description', d.material_description, true) +
            svField('Manufacturer', d.manufacturer) +
            svField('MAS Approved Date', d.approved_date) +
            svField('PO Date', d.po_date) +
            svField('Required on Site', d.required_date) +
            svField('Expected Delivery', d.expected_delivery_date) +
            svField('Actual Delivery', d.actual_delivery_date) +
            svField('Supplier', d.supplier) +
            svFieldRaw('Status', badge) +
            '<div class="sv-field full">' +
                '<div class="sv-field-label">Remarks</div>' +
                '<div class="sv-field-value sv-textarea">' + escH(d.remarks || '—') + '</div>' +
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

/* --- Accordion toggle --- */
function toggleProcGroup(el) {
    const body = el.closest('.proc-project-group').querySelector('.proc-project-body');
    const icon = el.querySelector('.proc-toggle');
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

/* --- MAS Submittal Search Modal --- */
function openMasModal() {
    document.getElementById('masModal').style.display = 'flex';
    document.getElementById('masSearchQ').value = '';
    var pid = document.getElementById('projectSelect').value;
    if (pid) doMasSearch(pid, '');
    else document.getElementById('masResults').innerHTML = '<p class="mas-no-results">Please select a project first.</p>';
    document.getElementById('masSearchQ').focus();
}

function closeMasModal() {
    document.getElementById('masModal').style.display = 'none';
}

document.getElementById('masSearchBtn').addEventListener('click', function() {
    doMasSearch(document.getElementById('projectSelect').value, document.getElementById('masSearchQ').value);
});
document.getElementById('masSearchQ').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doMasSearch(document.getElementById('projectSelect').value, this.value); }
});

var btnSearch = document.getElementById('btnSearchMAS');
if (btnSearch) btnSearch.addEventListener('click', openMasModal);

function doMasSearch(pid, q) {
    var results = document.getElementById('masResults');
    if (!pid) { results.innerHTML = '<p class="mas-no-results">Please select a project first.</p>'; return; }
    results.innerHTML = '<p class="mas-no-results">Loading...</p>';
    var url = 'ajax_submittal_list.php?project_id=' + encodeURIComponent(pid);
    if (q) url += '&q=' + encodeURIComponent(q);
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            if (!data.length) { results.innerHTML = '<p class="mas-no-results">No approved submittals found for this project.</p>'; return; }
            var h = '<table class="mas-table"><thead><tr><th>MAS Ref.</th><th>Material Description</th><th>Manufacturer</th><th>BOQ Ref.</th><th>Approved Date</th><th>Status</th></tr></thead><tbody>';
            data.forEach(function(r) {
                h += '<tr class="selectable" data-id="'+r.id+'" data-ref="'+escH(r.submittal_reference)+'" data-mat="'+escH(r.material_description)+'" data-mfg="'+escH(r.manufacturer||'')+'" data-boq="'+escH(r.boq_ref_no)+'" data-approved="'+(r.approved_date||'')+'">';
                h += '<td>'+escH(r.submittal_reference)+'</td>';
                h += '<td>'+escH(r.material_description)+'</td>';
                h += '<td>'+escH(r.manufacturer||'')+'</td>';
                h += '<td>'+escH(r.boq_ref_no)+'</td>';
                h += '<td>'+(r.approved_date||'')+'</td>';
                h += '<td><span class="badge-sm">'+escH(r.status)+'</span></td>';
                h += '</tr>';
            });
            h += '</tbody></table>';
            results.innerHTML = h;
            results.querySelectorAll('tr.selectable').forEach(function(tr) {
                tr.addEventListener('click', function() {
                    document.getElementById('submittalRefId').value = this.dataset.id;
                    document.getElementById('masRefDisplay').value = this.dataset.ref;
                    document.getElementById('matDesc').value = this.dataset.mat;
                    document.getElementById('masManufacturer').value = this.dataset.mfg || '';
                    document.getElementById('approvedDate').value = this.dataset.approved || '';
                    document.getElementById('boqRef').value = this.dataset.boq;
                    closeMasModal();
                });
            });
        }
    };
    xhr.send();
}

/* --- Utility --- */
function escH(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

/* Close modals on Escape */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeMasModal(); closeViewModal(); }
});
</script>

<?php include __DIR__.'/../../../includes/footer.php'; ?>
