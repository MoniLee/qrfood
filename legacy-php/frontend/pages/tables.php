<?php
declare(strict_types=1);
$pageTitle = 'Tables';
$extraScripts = ['tables.js'];
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';
?>
<main id="main-content">
    <header class="page-header">
        <div>
            <h1 class="page-title">Tables</h1>
            <p class="page-sub">Create tables and set availability for dine-in</p>
        </div>
    </header>

    <section class="card">
        <div class="card-body">
            <h2 class="card-title">Add or edit table</h2>
            <form id="tableForm" class="no-print" style="display:grid;gap:1rem;">
                <input type="hidden" id="tableId" value="">
                <div style="display:grid;gap:1rem;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="tableLabel">Label</label>
                        <input class="form-input" id="tableLabel" maxlength="32" required placeholder="e.g. T6">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="tableCap">Capacity</label>
                        <input class="form-input" id="tableCap" type="number" min="1" value="4" required>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="tableStatus">Status</label>
                        <select class="form-select" id="tableStatus">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Save table</button>
                    <button type="button" class="btn btn-secondary" id="tableReset">Clear</button>
                </div>
            </form>

            <div class="table-wrap table-cards-sm" style="margin-top:1.5rem;">
                <table class="data-table" id="tablesTable">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th class="no-print">Quick</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tablesBody">
                        <?php foreach ($tables as $t): ?>
                            <tr data-table-id="<?= (int) $t['id'] ?>">
                                <td data-label="Label"><strong><?= e((string) $t['label']) ?></strong></td>
                                <td data-label="Capacity"><?= (int) $t['capacity'] ?></td>
                                <td data-label="Status">
                                    <span class="badge <?= ($t['status'] ?? '') === 'occupied' ? 'badge-occ' : 'badge-avail' ?>"><?= e((string) $t['status']) ?></span>
                                </td>
                                <td data-label="Quick" class="no-print">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline btn-sm tbl-st" data-id="<?= (int) $t['id'] ?>" data-status="available">Available</button>
                                        <button type="button" class="btn btn-outline btn-sm tbl-st" data-id="<?= (int) $t['id'] ?>" data-status="occupied">Occupied</button>
                                    </div>
                                </td>
                                <td data-label="Actions" class="no-print">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline btn-sm tbl-edit" data-id="<?= (int) $t['id'] ?>">Edit</button>
                                        <button type="button" class="btn btn-danger btn-sm tbl-del" data-id="<?= (int) $t['id'] ?>">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
