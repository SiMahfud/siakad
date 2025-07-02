<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Teacher Subject Assignments') ?></h1>
        <a href="<?= route_to('admin_assignments.new') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg"></i> Add New Assignment
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">List of Assignments</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTableAssignments" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignments)) : ?>
                            <?php $i = 1; ?>
                            <?php foreach ($assignments as $item) : ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($item['teacher_name']) ?></td>
                                    <td><?= esc($item['class_name']) ?> (<?= esc($item['academic_year']) ?>)</td>
                                    <td><?= esc($item['subject_name']) ?></td>
                                    <td><?= esc(date('d M Y H:i', strtotime($item['created_at']))) ?></td>
                                    <td>
                                        <!-- Edit button can be added later if edit functionality is implemented -->
                                        <!-- <a href="<?= route_to('admin_assignments.edit', $item['id']) ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a> -->
                                        <form action="<?= route_to('admin_assignments.delete', $item['id']) ?>" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="_method" value="DELETE"> <!-- Method spoofing for DELETE -->
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center">No assignments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- You might want to add DataTables JS for sorting/pagination if the list grows -->
<!-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"> -->
<!-- <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> -->
<!-- <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script> -->
<!-- <script>
    // $(document).ready(function() {
    //     $('#dataTableAssignments').DataTable();
    // });
</script> -->

<?= $this->endSection() ?>
