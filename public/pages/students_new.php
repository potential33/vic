<?php
// /public/app/public/pages/students_new.php
$pageTitle = 'Add student';
$isAdmin = auth_role_is('admin');
$teacherId = auth_teacher_id();
if (!$isAdmin && $teacherId === null) {
    http_response_code(403);
    throw new AppException('Only administrators and assigned class teachers can add students.');
}

if ($isAdmin) {
    $sessions = fetch_all("SELECT id, name FROM sessions ORDER BY name DESC");
    $classArms = fetch_all(
        "SELECT ca.id, CONCAT(c.name, ' ', ca.arm) AS class_label
         FROM class_arms ca
         JOIN classes c ON c.id = ca.class_id
         ORDER BY c.name, ca.arm"
    );
} else {
    $teacherAssignments = class_teacher_assignments_for_teacher($teacherId);
    if (!$teacherAssignments) {
        http_response_code(403);
        throw new AppException('Only class teachers can add students. Ask an administrator to assign you to a class.');
    }
    $sessions = [];
    foreach ($teacherAssignments as $assignment) {
        $id = (int) $assignment['session_id'];
        if (!isset($sessions[$id])) {
            $sessions[$id] = ['id' => $id, 'name' => $assignment['session']];
        }
    }
    $sessions = array_values($sessions);
    usort($sessions, static fn(array $a, array $b): int => strcmp($b['name'], $a['name']));
}

$sessionId = (int) ($_POST['session_id'] ?? ($_GET['session_id'] ?? ($sessions[0]['id'] ?? 0)));
$selectedSessionName = '';
foreach ($sessions as $session) {
    if ((int) $session['id'] === $sessionId) {
        $selectedSessionName = $session['name'];
        break;
    }
}
if (!$isAdmin) {
    $classArms = class_teacher_assignments_for_teacher($teacherId, $sessionId);
}
$classArmId = (int) ($_POST['class_arm_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$isAdmin && !is_class_teacher_for(
            $teacherId,
            (int) ($_POST['class_arm_id'] ?? 0),
            (int) ($_POST['session_id'] ?? 0)
        )) {
            http_response_code(403);
            throw new AppException('You can only add students to a class and session assigned to you.');
        }
        $id = student_create(
            $_POST['admission_no'] ?? '',
            $_POST['full_name'] ?? '',
            (int) ($_POST['class_arm_id'] ?? 0),
            (int) ($_POST['session_id'] ?? 0),
            ($_POST['gender'] ?? '') === '' ? null : $_POST['gender'],
            ($_POST['date_of_birth'] ?? '') === '' ? null : $_POST['date_of_birth']
        );
        flash_set(
            'success',
            'Student created and enrolled. Login username: '
                . strtolower(trim($_POST['admission_no'] ?? ''))
                . '; temporary password: password123. The student must change it at first login.'
        );
        redirect(route_link('students/view', ['id' => $id]));
    } catch (AppException $e) {
        $error = $e->getMessage();
    }
}
?>
<h1>Add student</h1>

<?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!$isAdmin): ?>
    <form method="get" class="card">
        <input type="hidden" name="r" value="students/new">
        <div class="field">
            <label for="student-session-filter">Session</label>
            <select id="student-session-filter" name="session_id" onchange="this.form.submit()" required>
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= (int) $s['id'] === $sessionId ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

<?php endif; ?>

<form method="post" class="card">
  <?= csrf_field() ?>
    <?php if (!$isAdmin): ?>
        <input type="hidden" name="session_id" value="<?= e($sessionId) ?>">
    <?php endif; ?>
    <div class="field">
        <label>Admission number *</label>
        <input name="admission_no" required value="<?= e(old('admission_no')) ?>">
    </div>
    <div class="field">
        <label>Full name *</label>
        <input name="full_name" required value="<?= e(old('full_name')) ?>">
    </div>
    <div class="field">
        <label>Gender</label>
        <select name="gender">
            <option value="">—</option>
            <option value="M" <?= old('gender') === 'M' ? 'selected' : '' ?>>Male</option>
            <option value="F" <?= old('gender') === 'F' ? 'selected' : '' ?>>Female</option>
        </select>
    </div>
    <div class="field">
        <label>Date of birth</label>
        <input type="date" name="date_of_birth" value="<?= e(old('date_of_birth')) ?>">
    </div>
    <h2>Class enrollment</h2>
    <div class="field">
        <label for="student-session">Session *</label>
        <?php if ($isAdmin): ?>
            <select id="student-session" name="session_id" required>
                <option value="">— Select session —</option>
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= (int)$s['id'] === $sessionId ? 'selected' : '' ?>>
                        <?= e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <p class="muted"><?= e($selectedSessionName) ?></p>
        <?php endif; ?>
    </div>
    <div class="field">
        <label for="student-class-arm">Class arm *</label>
        <select id="student-class-arm" name="class_arm_id" required>
            <option value="">— Select class —</option>
            <?php foreach ($classArms as $ca): ?>
                <option value="<?= e($ca['class_arm_id'] ?? $ca['id']) ?>" <?= (int)($ca['class_arm_id'] ?? $ca['id']) === $classArmId ? 'selected' : '' ?>>
                    <?= e($ca['class_label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="actions">
        <button class="btn" type="submit" <?= !$classArms ? 'disabled' : '' ?>>Save and enroll</button>
        <a class="btn secondary" href="<?= e(route_link('students')) ?>">Cancel</a>
    </div>
</form>