<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Input Assessment Scores') ?></h1>
        <a href="<?= site_url('guru/assessments') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Change Class/Subject
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Inputting for: Class: <?= esc($classInfo['class_name'] ?? 'N/A') ?> | Subject: <?= esc($subjectInfo['subject_name'] ?? 'N/A') ?>
            </h6>
        </div>
        <div class="card-body">
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
            <?php
            $validation_errors = session()->getFlashdata('validation_errors');
            if (!empty($validation_errors)) :
                // Prepare a student name map for easier lookup in errors
                $studentNamesMap = [];
                if (!empty($students) && is_array($students)) {
                    foreach ($students as $student) {
                        $studentNamesMap[$student['id']] = $student['full_name'];
                    }
                }
            ?>
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading">Please correct the following errors:</h6>
                    <ul>
                        <?php foreach ($validation_errors as $studentId => $studentErrorArray) : ?>
                            <?php
                                $studentDisplayName = isset($studentNamesMap[$studentId]) ? $studentNamesMap[$studentId] . " (ID: " . $studentId . ")" : "Student ID: " . $studentId;
                            ?>
                            <?php foreach ($studentErrorArray as $index => $fieldErrors) : ?>
                                <?php foreach ($fieldErrors as $field => $errorMsg) : ?>
                                    <li>For <strong><?= esc($studentDisplayName) ?></strong>, Entry #<?= esc($index + 1) ?>: Field '<em><?= esc(ucfirst(str_replace('_', ' ', $field))) ?></em>' - <?= esc($errorMsg) ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>


            <form action="<?= site_url('guru/assessments/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="class_id" value="<?= esc($classInfo['id'] ?? '') ?>">
                <input type="hidden" name="subject_id" value="<?= esc($subjectInfo['id'] ?? '') ?>">

                <p><strong>Instructions:</strong> Fill in the assessment details for each student. You can add multiple assessment entries per student using the "Add Row" button for that student. Ensure "Assessment Date" and "Type" are selected for each entry you wish to save. "Score" is primarily for Summative types.</p>

                <div class="table-responsive">
                    <table class="table table-bordered" id="assessmentTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Student Name</th>
                                <th style="width: 15%;">Type <span class="text-danger">*</span></th>
                                <th style="width: 20%;">Assessment Title/Topic</th>
                                <th style="width: 10%;">Date <span class="text-danger">*</span></th>
                                <th style="width: 10%;">Score</th>
                                <th style="width: 20%;">Description/Feedback</th>
                                <th style="width: 5%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students) && is_array($students)) : ?>
                                <?php foreach ($students as $student) : ?>
                                    <tr class="student-row" data-student-id="<?= esc($student['id']) ?>">
                                        <td><?= esc($student['full_name']) ?><br><small>(NISN: <?= esc($student['nisn']) ?>)</small></td>
                                        <td colspan="5">
                                            <table class="table table-sm mb-0 inner-assessment-table">
                                                <tbody class="assessment-entries-for-student">
                                                    <!-- Initial row for assessment entry -->
                                                    <tr>
                                                        <td style="width: 23%; border-top: none;">
                                                            <select name="assessments[<?= esc($student['id']) ?>][0][assessment_type]" class="form-select form-select-sm assessment-type">
                                                                <option value="">Select Type</option>
                                                                <option value="FORMATIF">Formatif</option>
                                                                <option value="SUMATIF">Sumatif</option>
                                                            </select>
                                                        </td>
                                                        <td style="width: 31%; border-top: none;"><input type="text" name="assessments[<?= esc($student['id']) ?>][0][assessment_title]" class="form-control form-control-sm" placeholder="e.g., Quiz Bab 1"></td>
                                                        <td style="width: 15%; border-top: none;"><input type="date" name="assessments[<?= esc($student['id']) ?>][0][assessment_date]" class="form-control form-control-sm"></td>
                                                        <td style="width: 15%; border-top: none;"><input type="number" step="0.01" name="assessments[<?= esc($student['id']) ?>][0][score]" class="form-control form-control-sm score-input" placeholder="0-100"></td>
                                                        <td style="width: 31%; border-top: none;"><textarea name="assessments[<?= esc($student['id']) ?>][0][description]" class="form-control form-control-sm" rows="1" placeholder="Feedback/Notes"></textarea></td>
                                                         <td style="width: 15%; border-top: none;"><button type="button" class="btn btn-danger btn-sm remove-assessment-row"><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm add-assessment-row" data-student-id="<?= esc($student['id']) ?>">
                                                <i class="bi bi-plus-circle"></i> Add Row
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="text-center">No students found in this class. Please ensure students are assigned to this class/rombel.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($students)) : ?>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save All Assessments</button>
                    <a href="<?= site_url('guru/assessments') ?>" class="btn btn-secondary">Cancel</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.add-assessment-row').forEach(button => {
        button.addEventListener('click', function () {
            const studentId = this.dataset.studentId;
            const assessmentEntriesContainer = this.closest('.student-row').querySelector('.assessment-entries-for-student');
            const newIndex = assessmentEntriesContainer.children.length;

            const newRowHtml = `
                <tr>
                    <td style="width: 23%; border-top: none;">
                        <select name="assessments[${studentId}][${newIndex}][assessment_type]" class="form-select form-select-sm assessment-type">
                            <option value="">Select Type</option>
                            <option value="FORMATIF">Formatif</option>
                            <option value="SUMATIF">Sumatif</option>
                        </select>
                    </td>
                    <td style="width: 31%; border-top: none;"><input type="text" name="assessments[${studentId}][${newIndex}][assessment_title]" class="form-control form-control-sm" placeholder="e.g., Quiz Bab 1"></td>
                    <td style="width: 15%; border-top: none;"><input type="date" name="assessments[${studentId}][${newIndex}][assessment_date]" class="form-control form-control-sm"></td>
                    <td style="width: 15%; border-top: none;"><input type="number" step="0.01" name="assessments[${studentId}][${newIndex}][score]" class="form-control form-control-sm score-input" placeholder="0-100"></td>
                    <td style="width: 31%; border-top: none;"><textarea name="assessments[${studentId}][${newIndex}][description]" class="form-control form-control-sm" rows="1" placeholder="Feedback/Notes"></textarea></td>
                    <td style="width: 15%; border-top: none;"><button type="button" class="btn btn-danger btn-sm remove-assessment-row"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
            assessmentEntriesContainer.insertAdjacentHTML('beforeend', newRowHtml);
        });
    });

    // Event delegation for removing rows
    document.getElementById('assessmentTable').addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-assessment-row') || e.target.closest('.remove-assessment-row'))) {
            const button = e.target.classList.contains('remove-assessment-row') ? e.target : e.target.closest('.remove-assessment-row');
            const rowToRemove = button.closest('tr');
            const studentEntryContainer = rowToRemove.closest('.assessment-entries-for-student');
            if (studentEntryContainer.children.length > 1) { // Always keep at least one row
                rowToRemove.remove();
            } else {
                alert('At least one assessment entry row must remain for each student.');
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
