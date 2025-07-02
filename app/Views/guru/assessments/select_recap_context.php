<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Select Context for Assessment Recap') ?></h1>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Choose Class and Subject for Recap</h6>
        </div>
        <div class="card-body">
            <form action="<?= esc($formAction ?? site_url('guru/assessments/show-recap')) ?>" method="get" id="recapContextForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_id_recap" class="form-label">Class: <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_id_recap" class="form-select" required>
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
                        <label for="subject_id_recap" class="form-label">Subject: <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id_recap" class="form-select" required disabled>
                            <option value="">-- Select Subject --</option>
                            <?php /* Options will be populated by JavaScript */ ?>
                        </select>
                        <div id="subject_loader_recap" style="display: none;" class="mt-1">
                            <small class="text-muted"><i class="bi bi-arrow-repeat"></i> Loading subjects...</small>
                        </div>
                        <div id="no_subject_message_recap" style="display: none;" class="mt-1">
                            <small class="text-warning">No subjects assigned for you in this class or no class selected.</small>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" id="showRecapButton" class="btn btn-info" disabled>
                        <i class="bi bi-eye"></i> Show Recap
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
    const classSelectRecap = document.getElementById('class_id_recap');
    const subjectSelectRecap = document.getElementById('subject_id_recap');
    const showRecapButton = document.getElementById('showRecapButton');
    const subjectLoaderRecap = document.getElementById('subject_loader_recap');
    const noSubjectMessageRecap = document.getElementById('no_subject_message_recap');

    function fetchSubjectsRecap(classId) {
        subjectSelectRecap.innerHTML = '<option value="">-- Loading... --</option>';
        subjectSelectRecap.disabled = true;
        showRecapButton.disabled = true;
        subjectLoaderRecap.style.display = 'block';
        noSubjectMessageRecap.style.display = 'none';

        if (!classId) {
            subjectSelectRecap.innerHTML = '<option value="">-- Select Subject --</option>';
            subjectLoaderRecap.style.display = 'none';
            return;
        }

        const ajaxUrl = `<?= site_url('guru/assessments/ajax/get-subjects-for-class/') ?>${classId}`;

        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                subjectSelectRecap.innerHTML = '<option value="">-- Select Subject --</option>';
                if (data && data.length > 0) {
                    data.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = subject.subject_name + (subject.is_pilihan == 1 ? ' (Pilihan)' : '');
                        subjectSelectRecap.appendChild(option);
                    });
                    subjectSelectRecap.disabled = false;
                    noSubjectMessageRecap.style.display = 'none';
                } else {
                    noSubjectMessageRecap.style.display = 'block';
                    subjectSelectRecap.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error fetching subjects for recap:', error);
                subjectSelectRecap.innerHTML = '<option value="">Error loading subjects</option>';
                noSubjectMessageRecap.style.display = 'none';
            })
            .finally(() => {
                subjectLoaderRecap.style.display = 'none';
                toggleShowRecapButtonState();
            });
    }

    function toggleShowRecapButtonState() {
        if (classSelectRecap.value && subjectSelectRecap.value && !subjectSelectRecap.disabled) {
            showRecapButton.disabled = false;
        } else {
            showRecapButton.disabled = true;
        }
    }

    classSelectRecap.addEventListener('change', function () {
        fetchSubjectsRecap(this.value);
    });

    subjectSelectRecap.addEventListener('change', function() {
        toggleShowRecapButtonState();
    });

    if (classSelectRecap.value) {
        fetchSubjectsRecap(classSelectRecap.value);
    } else {
        subjectSelectRecap.innerHTML = '<option value="">-- Select Subject --</option>';
        subjectSelectRecap.disabled = true;
        showRecapButton.disabled = true;
    }
});
</script>
<?= $this->endSection() ?>
