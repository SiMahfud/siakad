<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Assessment Recap') ?></h1>
        <a href="<?= route_to('guru_assessment_recap_select') ?>" class="btn btn-sm btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Select Recap Context
        </a>
    </div>

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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Recap for: <?= esc($classInfo['class_name'] ?? 'N/A Class') ?> - <?= esc($subjectInfo['subject_name'] ?? 'N/A Subject') ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($studentsWithAssessments)) : ?>
                <?php foreach ($studentsWithAssessments as $studentData) : ?>
                    <div class="student-recap mb-4">
                        <h5 class="text-dark">
                            <i class="bi bi-person-fill"></i> <?= esc($studentData['student_name']) ?>
                            <small class="text-muted">(NISN: <?= esc($studentData['student_nisn'] ?? 'N/A') ?>)</small>
                        </h5>

                        <?php if (!empty($studentData['assessments'])) : ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Score</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($studentData['assessments'] as $assessment) : ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?= $assessment['assessment_type'] === 'SUMATIF' ? 'success' : 'info' ?>">
                                                        <?= esc(ucfirst(strtolower($assessment['assessment_type']))) ?>
                                                    </span>
                                                </td>
                                                <td><?= esc($assessment['assessment_title']) ?></td>
                                                <td><?= esc(date('d M Y', strtotime($assessment['assessment_date']))) ?></td>
                                                <td><?= $assessment['assessment_type'] === 'SUMATIF' ? esc($assessment['score'] ?? '-') : '-' ?></td>
                                                <td><?= nl2br(esc($assessment['description'] ?? '-')) ?></td>
                                                <td>
                                                    <a href="<?= route_to('guru_assessment_edit', $assessment['id']) ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="<?= route_to('guru_assessment_delete', $assessment['id']) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this assessment entry for <?= esc($studentData['student_name']) ?>: \'<?= esc($assessment['assessment_title']) ?>\'?')">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p class="text-muted ms-3">No assessments found for this student in this subject and class.</p>
                        <?php endif; ?>
                    </div>
                    <hr class="my-3">
                <?php endforeach; ?>
            <?php else : ?>
                <div class="alert alert-info">
                    No assessment data found for the selected class and subject.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    .student-recap h5 {
        border-bottom: 2px solid #e3e6f0;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .table-sm td, .table-sm th {
        padding: 0.4rem;
    }
</style>
<?= $this->endSection() ?>
