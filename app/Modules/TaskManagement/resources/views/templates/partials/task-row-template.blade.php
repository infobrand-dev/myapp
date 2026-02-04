<template id="task-row-template">
<div class="card border task-row">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="card-title mb-0">Task</h4>
            <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.task-row').remove()">Remove</button>
        </div>
        <div class="mb-3">
            <label class="form-label">Judul Task</label>
            <input type="text" name="tasks[__INDEX__][title]" class="form-control form-control-sm" value="__TITLE__" required>
        </div>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <label class="form-label mb-0">Subtasks</label>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-subtask">Tambah Subtask</button>
        </div>
        <div class="subtasks-container" data-index="__INDEX__"></div>
    </div>
</div>
</template>
