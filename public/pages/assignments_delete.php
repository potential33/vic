<?php
// /public/app/public/pages/assignments_delete.php
$pageTitle = 'Unassign teacher';

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
$type = $_GET['type'] ?? ($_POST['type'] ?? 'subject');
if (!in_array($type, ['subject', 'class_teacher'], true)) {
    throw new AppException('Invalid assignment type.');
}

$assign = $type === 'class_teacher'
    ? fetch_one(
        "SELECT cta.id, t.full_name AS teacher,
                CONCAT(c.name, ' ', ca.arm) AS class_label, ses.name AS session
         FROM class_teacher_assignments cta
         JOIN teachers t ON t.id = cta.teacher_id
         JOIN class_arms ca ON ca.id = cta.class_arm_id
         JOIN classes c ON c.id = ca.class_id
         JOIN sessions ses ON ses.id = cta.session_id
         WHERE cta.id = ?",
        [$id]
    )
    : fetch_one(
        "SELECT ts.id, t.full_name AS teacher, sub.name AS subject,
                CONCAT(c.name, ' ', ca.arm) AS class_label, ses.name AS session
         FROM teacher_subjects ts
         JOIN teachers t ON t.id = ts.teacher_id
         JOIN subjects sub ON sub.id = ts.subject_id
         JOIN class_arms ca ON ca.id = ts.class_arm_id
         JOIN classes c ON c.id = ca.class_id
         JOIN sessions ses ON ses.id = ts.session_id
         WHERE ts.id = ?",
        [$id]
    );
if (!$assign) throw new AppException('Assignment not found.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($type === 'class_teacher') {
            unassign_class_teacher($id);
        } else {
            unassign_teacher($id);
        }
        flash_set('success', 'Assignment removed.');
        redirect(route_link('assignments'));
    } catch (AppException $e) {
        flash_set('error', $e->getMessage());
        redirect(route_link('assignments'));
    }
}
?>
<h1>Unassign teacher</h1>
<div class="card">
    <p>
        <strong><?= e($assign['teacher']) ?></strong>
        <?php if ($type === 'class_teacher'): ?>
            will no longer be the class/form teacher for
        <?php else: ?>
            will no longer be assigned to teach <strong><?= e($assign['subject']) ?></strong> in
        <?php endif; ?>
        <strong><?= e($assign['class_label']) ?></strong> (<em><?= e($assign['session']) ?></em>).
    </p>
    <?php if ($type !== 'class_teacher'): ?>
    <p class="muted">
        Existing grades entered by this teacher are <strong>not</strong> affected —
        they remain in the results table.
    </p>
    <?php endif; ?>
    <form method="post" style="display:flex; gap:.5rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($id) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <button class="btn danger" type="submit">Yes, unassign</button>
        <a class="btn secondary" href="<?= e(route_link('assignments')) ?>">Cancel</a>
    </form>
</div>