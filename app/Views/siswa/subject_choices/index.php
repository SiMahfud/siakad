<?= $this->extend('layouts/admin_default') // Or a specific student layout ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'Elective Subject Choice') ?></h1>
    <p class="mb-4">
        Please choose your elective subjects for Academic Year: <strong><?= esc($academicYear ?? 'N/A') ?></strong>,
        Semester: <strong><?= ($semester ?? 0) == 1 ? 'Ganjil' : (($semester ?? 0) == 2 ? 'Genap' : 'N/A') ?></strong>.
        You can choose up to <strong><?= esc($maxChoices ?? 0) ?></strong> subjects.
    </p>
    <p>You have currently chosen: <strong id="currentChoiceCountDisplay"><?= esc($currentChoiceCount ?? 0) ?></strong> subject(s).</p>

    <div id="choiceAlertContainer"></div> <!-- For AJAX messages -->

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
            <h6 class="m-0 font-weight-bold text-primary">Available Elective Subjects</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($offerings)) : ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="subjectOfferingsTable">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Quota</th>
                                <th>Enrolled</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($offerings as $offering) : ?>
                                <?php
                                $isChosen = in_array($offering['id'], $studentChoicesMap);
                                $isFull = ($offering['max_quota'] !== null && $offering['current_enrollment'] >= $offering['max_quota']);
                                ?>
                                <tr id="offering-row-<?= $offering['id'] ?>">
                                    <td><?= esc($offering['subject_name']) ?></td>
                                    <td><?= esc($offering['subject_code']) ?></td>
                                    <td><?= nl2br(esc($offering['description'] ?? '')) ?></td>
                                    <td><?= esc($offering['max_quota'] ?? 'Unlimited') ?></td>
                                    <td id="enrollment-count-<?= $offering['id'] ?>"><?= esc($offering['current_enrollment']) ?></td>
                                    <td>
                                        <?php if ($isChosen) : ?>
                                            <button class="btn btn-danger btn-sm btn-unchoose" data-offering-id="<?= $offering['id'] ?>">
                                                <i class="fas fa-times-circle"></i> Unchoose
                                            </button>
                                        <?php elseif ($isFull) : ?>
                                            <button class="btn btn-secondary btn-sm" disabled>Full</button>
                                        <?php elseif ($currentChoiceCount >= $maxChoices) : ?>
                                             <button class="btn btn-success btn-sm btn-choose" data-offering-id="<?= $offering['id'] ?>" disabled>
                                                <i class="fas fa-check-circle"></i> Choose
                                            </button>
                                            <small class="text-muted d-block">Max choices reached</small>
                                        <?php else : ?>
                                            <button class="btn btn-success btn-sm btn-choose" data-offering-id="<?= $offering['id'] ?>">
                                                <i class="fas fa-check-circle"></i> Choose
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>No elective subjects are currently offered for this period, or the selection period is over.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
$(document).ready(function() {
    const csrfTokenName = '<?= csrf_token() ?>';
    const csrfTokenHash = '<?= csrf_hash() ?>';
    const processChoiceUrl = '<?= site_url('siswa/subject-choices/process') ?>';
    let currentChoiceCount = parseInt($('#currentChoiceCountDisplay').text()) || 0;
    const maxChoices = <?= esc($maxChoices ?? 0) ?>;

    function showAlert(type, message) {
        $('#choiceAlertContainer').html(
            `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`
        );
    }

    function updateButtonStates() {
        $('#currentChoiceCountDisplay').text(currentChoiceCount);
        $('.btn-choose').each(function() {
            if (currentChoiceCount >= maxChoices) {
                $(this).prop('disabled', true);
                if (!$(this).siblings('small.text-muted').length && !$(this).hasClass('btn-unchoose')) {
                     // Check if it's not already chosen (which would make it an unchoose button)
                    let offeringId = $(this).data('offering-id');
                    let isAlreadyChosen = $('.btn-unchoose[data-offering-id="' + offeringId + '"]').length > 0;
                    if (!isAlreadyChosen) {
                        $(this).after('<small class="text-muted d-block">Max choices reached</small>');
                    }
                }
            } else {
                $(this).prop('disabled', false);
                $(this).siblings('small.text-muted').remove();
            }
        });
    }

    // Initial button state update
    updateButtonStates();


    $('#subjectOfferingsTable').on('click', '.btn-choose, .btn-unchoose', function() {
        const button = $(this);
        const offeringId = button.data('offering-id');
        const action = button.hasClass('btn-choose') ? 'choose' : 'unchoose';

        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        let postData = {
            '<?= csrf_token() ?>': $('meta[name="csrf-token-name"]').attr('content') ? $('meta[name="csrf-token-name"]').attr('content') : csrfTokenHash, // Use dynamic if meta tag is set, else static
            offering_id: offeringId,
            action: action
        };

        // If using static csrfHash, it needs to be updated after each request if it's regenerated.
        // For simplicity, if CodeIgniter's CSRF is set to regenerate on each request, this AJAX might need adjustment
        // to fetch new hash or disable regeneration for AJAX. Assuming session-based CSRF or non-regenerating for now.

        $.ajax({
            url: processChoiceUrl,
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    currentChoiceCount = response.currentChoiceCount;

                    // Update enrollment count on the page
                    if (response.offeringId && typeof response.newEnrollment !== 'undefined') {
                        $('#enrollment-count-' + response.offeringId).text(response.newEnrollment);
                    }

                    // Toggle button
                    const row = button.closest('tr');
                    const quotaCell = row.find('td').eq(3); // Assuming quota is 4th cell
                    const enrollmentCell = row.find('td').eq(4); // Assuming enrollment is 5th cell
                    const actionCell = button.parent();

                    let newButtonHtml = '';
                    if (action === 'choose') {
                        newButtonHtml = `<button class="btn btn-danger btn-sm btn-unchoose" data-offering-id="${offeringId}"><i class="fas fa-times-circle"></i> Unchoose</button>`;
                    } else {
                        newButtonHtml = `<button class="btn btn-success btn-sm btn-choose" data-offering-id="${offeringId}"><i class="fas fa-check-circle"></i> Choose</button>`;
                    }
                    actionCell.html(newButtonHtml);

                } else {
                    showAlert('danger', response.message || 'An unknown error occurred.');
                    button.prop('disabled', false).html(action === 'choose' ? '<i class="fas fa-check-circle"></i> Choose' : '<i class="fas fa-times-circle"></i> Unchoose');
                }
                updateButtonStates(); // Update all buttons based on new count
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'An error occurred: ' + errorThrown;
                if(jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg = jqXHR.responseJSON.message;
                }
                showAlert('danger', errorMsg);
                button.prop('disabled', false).html(action === 'choose' ? '<i class="fas fa-check-circle"></i> Choose' : '<i class="fas fa-times-circle"></i> Unchoose');
                updateButtonStates();
            },
            complete: function() {
                 // Regenerate CSRF token if needed, for now assume it's valid for session or handled by CI.
                 // If CSRF regenerates per request:
                 // if (jqXHR.responseJSON && jqXHR.responseJSON.csrf_hash) {
                 //    csrfTokenHash = jqXHR.responseJSON.csrf_hash;
                 //    $('input[name="'+csrfTokenName+'"]').val(csrfTokenHash);
                 // }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
