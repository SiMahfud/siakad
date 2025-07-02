<?= $this->extend('layouts/admin_default') // Menggunakan layout admin default untuk konsistensi ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Select Context for Assessment') ?></h1>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Choose Class and Subject</h6>
        </div>
        <div class="card-body">
            <form action="<?= esc($formAction ?? site_url('guru/assessments/input')) ?>" method="get" id="assessmentContextForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_id" class="form-label">Class: <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php if (!empty($classes) && is_array($classes)) : ?>
                                <?php foreach ($classes as $class_item) : ?>
                                    <option value="<?= esc($class_item['id']) ?>" <?= ((isset($selectedClassId) && $selectedClassId == $class_item['id'])) ? 'selected' : '' ?>>
                                        <?= esc($class_item['class_name']) ?> (<?= esc($class_item['academic_year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>No classes assigned or available.</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="subject_id" class="form-label">Subject: <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-select" required disabled>
                            <option value="">-- Select Subject --</option>
                            <?php /* Options will be populated by JavaScript */ ?>
                        </select>
                        <div id="subject_loader" style="display: none;" class="mt-1">
                            <small class="text-muted"><i class="bi bi-arrow-repeat"></i> Loading subjects...</small>
                        </div>
                        <div id="no_subject_message" style="display: none;" class="mt-1">
                            <small class="text-warning">No subjects assigned for you in this class or no class selected.</small>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" id="proceedButton" class="btn btn-primary" disabled>
                        <i class="bi bi-arrow-right-circle"></i> Proceed to Input Scores
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelect = document.getElementById('class_id');
    const subjectSelect = document.getElementById('subject_id');
    const proceedButton = document.getElementById('proceedButton');
    const subjectLoader = document.getElementById('subject_loader');
    const noSubjectMessage = document.getElementById('no_subject_message');

    // Store the initially selected subject ID if available (e.g., from query param on page load/back)
    // This part is tricky without knowing if $selectedSubjectId is passed from controller for this specific scenario
    // const initialSelectedSubjectId = '<?= isset($selected_subject_id) ? esc($selected_subject_id, 'js') : '' ?>';

    function fetchSubjects(classId) {
        subjectSelect.innerHTML = '<option value="">-- Loading... --</option>'; // Clear and show loading
        subjectSelect.disabled = true;
        proceedButton.disabled = true;
        subjectLoader.style.display = 'block';
        noSubjectMessage.style.display = 'none';

        if (!classId) {
            subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
            subjectLoader.style.display = 'none';
            // noSubjectMessage.style.display = 'block'; // Or hide it if class is not selected
            return;
        }

        // Adjust the URL to use route_to if available and configured for JS, or build manually
        const ajaxUrl = `<?= site_url('guru/assessments/ajax/get-subjects-for-class/') ?>${classId}`;

        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>'; // Reset
                if (data && data.length > 0) {
                    data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id; // Assuming 'id' is the subject ID from AJAX
                        option.textContent = subject.subject_name + (subject.is_pilihan == 1 ? ' (Pilihan)' : '');
                        // if (subject.id == initialSelectedSubjectId) {
                        //     option.selected = true;
                        // }
                        subjectSelect.appendChild(option);
                    });
                    subjectSelect.disabled = false;
                    noSubjectMessage.style.display = 'none';
                } else {
                    // subjectSelect.innerHTML = '<option value="" disabled>No subjects available</option>';
                    noSubjectMessage.style.display = 'block';
                    subjectSelect.disabled = true; // Keep it disabled
                }
            })
            .catch(error => {
                console.error('Error fetching subjects:', error);
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                noSubjectMessage.style.display = 'none'; // Or show a specific error message
            })
            .finally(() => {
                subjectLoader.style.display = 'none';
                // Re-evaluate proceed button state after subjects are loaded or if an error occurs
                toggleProceedButtonState();
            });
    }

    function toggleProceedButtonState() {
        if (classSelect.value && subjectSelect.value && !subjectSelect.disabled) {
            proceedButton.disabled = false;
        } else {
            proceedButton.disabled = true;
        }
    }

    classSelect.addEventListener('change', function () {
        fetchSubjects(this.value);
    });

    subjectSelect.addEventListener('change', function() {
        toggleProceedButtonState();
    });

    // Initial load: if a class is already selected (e.g. from GET param or wali kelas default)
    if (classSelect.value) {
        fetchSubjects(classSelect.value);
    } else {
        // Ensure subject dropdown is in a clean state if no class is initially selected
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        subjectSelect.disabled = true;
        proceedButton.disabled = true;
    }
});
</script>
<?= $this->endSection() ?>
