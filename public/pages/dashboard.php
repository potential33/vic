<?php
// /public/app/public/pages/dashboard.php
$user = auth_user();
$role = $user['role'] ?? '';
$pageTitle = match ($role) {
    'admin' => 'School overview',
    'teacher' => 'Teaching hub',
    'student' => 'My school',
    default => 'Dashboard',
};
$currentSession = fetch_one("SELECT id, name FROM sessions ORDER BY name DESC LIMIT 1");
?>
<?php if ($role === 'admin'): ?>
    <?php
    $counts = [
        ['Students', (int) fetch_val("SELECT COUNT(*) FROM students"), 'Enrolled in the school'],
        ['Teachers', (int) fetch_val("SELECT COUNT(*) FROM teachers"), 'On the teaching team'],
        ['Classes', (int) fetch_val("SELECT COUNT(*) FROM classes"), 'Across both sections'],
        ['Subjects', (int) fetch_val("SELECT COUNT(*) FROM subjects"), 'Available to assign'],
        ['Enrollments', (int) fetch_val("SELECT COUNT(*) FROM enrollments"), 'All session records'],
        ['Assignments', (int) fetch_val("SELECT COUNT(*) FROM teacher_subjects"), 'Teacher and subject links'],
    ];
    ?>
    <section class="page-intro dashboard-intro">
        <div>
            <p class="eyebrow">ADMINISTRATION · SCHOOL OVERVIEW</p>
            <h1>Welcome back, <?= e($user['teacher_name'] ?? $user['username']) ?></h1>
            <p class="intro-copy">A clear view of your school, with the tools you need close at hand.</p>
        </div>
        <?php if ($currentSession): ?>
            <div class="session-pill"><span class="status-dot"></span>
                Current session <strong><?= e($currentSession['name']) ?></strong>
            </div>
        <?php endif; ?>
    </section>

    <section class="overview-grid" aria-label="School statistics">
        <?php foreach ($counts as [$label, $number, $description]): ?>
            <article class="overview-stat">
                <span class="overview-stat-label"><?= e($label) ?></span>
                <strong class="overview-stat-number"><?= e(number_format($number)) ?></strong>
                <span class="overview-stat-description"><?= e($description) ?></span>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">GET STARTED</p>
                <h2>Common tasks</h2>
            </div>
        </div>
        <div class="action-grid">
            <a class="action-card" href="<?= e(route_link('students/new')) ?>">
                <span class="action-icon">01</span>
                <span class="action-card-title">Add a student</span>
                <span class="action-card-copy">Create a student record and get them started.</span>
                <span class="action-arrow" aria-hidden="true">→</span>
            </a>
            <a class="action-card" href="<?= e(route_link('teachers/new')) ?>">
                <span class="action-icon">02</span>
                <span class="action-card-title">Add a teacher</span>
                <span class="action-card-copy">Set up a teacher profile and portal access.</span>
                <span class="action-arrow" aria-hidden="true">→</span>
            </a>
            <a class="action-card" href="<?= e(route_link('enrollments/new')) ?>">
                <span class="action-icon">03</span>
                <span class="action-card-title">Enroll a student</span>
                <span class="action-card-copy">Place a student in a class for this session.</span>
                <span class="action-arrow" aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <section class="dashboard-section workflow-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">ACADEMIC WORKFLOW</p>
                <h2>Keep the term moving</h2>
            </div>
        </div>
        <div class="workflow-list">
            <a href="<?= e(route_link('coverage')) ?>"><span>Review grade coverage</span><span>Check progress before reports are published →</span></a>
            <a href="<?= e(route_link('results/approve')) ?>"><span>Approve submitted results</span><span>Review and finalize teacher submissions →</span></a>
            <a href="<?= e(route_link('reports/publish')) ?>"><span>Publish report cards</span><span>Share completed results with students →</span></a>
        </div>
    </section>
<?php elseif ($role === 'teacher'): ?>
    <?php
    $teacherId = (int) ($user['teacher_id'] ?? 0);
    $sessionId = (int) ($currentSession['id'] ?? 0);
    $subjectCount = $sessionId
        ? (int) fetch_val(
            "SELECT COUNT(DISTINCT subject_id) FROM teacher_subjects WHERE teacher_id = ? AND session_id = ?",
            [$teacherId, $sessionId]
        )
        : 0;
    $classCount = $sessionId
        ? (int) fetch_val(
            "SELECT COUNT(*) FROM class_teacher_assignments WHERE teacher_id = ? AND session_id = ?",
            [$teacherId, $sessionId]
        )
        : 0;
    ?>
    <section class="page-intro dashboard-intro">
        <div>
            <p class="eyebrow">TEACHER PORTAL</p>
            <h1>Your teaching hub</h1>
            <p class="intro-copy">Everything you need to manage your classes and keep student results up to date.</p>
        </div>
        <?php if ($currentSession): ?>
            <div class="session-pill"><span class="status-dot"></span>
                Current session <strong><?= e($currentSession['name']) ?></strong>
            </div>
        <?php endif; ?>
    </section>

    <section class="overview-grid teacher-stats" aria-label="Your teaching assignments">
        <article class="overview-stat">
            <span class="overview-stat-label">Subjects assigned</span>
            <strong class="overview-stat-number"><?= e(number_format($subjectCount)) ?></strong>
            <span class="overview-stat-description">In the current session</span>
        </article>
        <article class="overview-stat">
            <span class="overview-stat-label">Class teacher roles</span>
            <strong class="overview-stat-number"><?= e(number_format($classCount)) ?></strong>
            <span class="overview-stat-description">Classes you are responsible for</span>
        </article>
    </section>

    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">YOUR WORKSPACE</p>
                <h2>Pick up where you left off</h2>
            </div>
        </div>
        <div class="action-grid teacher-actions">
            <a class="action-card action-card-featured" href="<?= e(route_link('results/entry')) ?>">
                <span class="action-icon">01</span>
                <span class="action-card-title">Enter grades</span>
                <span class="action-card-copy">Record and submit results for your classes.</span>
                <span class="action-arrow" aria-hidden="true">→</span>
            </a>
            <a class="action-card" href="<?= e(route_link('coverage')) ?>">
                <span class="action-icon">02</span>
                <span class="action-card-title">My coverage</span>
                <span class="action-card-copy">See what is complete and what still needs attention.</span>
                <span class="action-arrow" aria-hidden="true">→</span>
            </a>
            <a class="action-card" href="<?= e(route_link('assignments')) ?>">
                <span class="action-icon">03</span>
                <span class="action-card-title">My assignments</span>
                <span class="action-card-copy">Review your subject and class assignments.</span>
                <span class="action-arrow" aria-hidden="true">→</span>
            </a>
        </div>
    </section>
<?php else: ?>
    <?php
    $student = !empty($user['student_id'])
        ? fetch_one("SELECT full_name, admission_no FROM students WHERE id = ?", [(int) $user['student_id']])
        : null;
    $studentName = $student['full_name'] ?? ($user['username'] ?? 'Student');
    ?>
    <section class="student-welcome">
        <p class="eyebrow">YOUR STUDENT PORTAL</p>
        <h1>Welcome, <?= e($studentName) ?></h1>
        <p>Keep up with your learning and check your latest school report when it is ready.</p>
        <?php if (!empty($student['admission_no'])): ?>
            <span class="student-id">Admission no. <?= e($student['admission_no']) ?></span>
        <?php endif; ?>
    </section>
    <section class="student-report-card">
        <div>
            <p class="eyebrow">ACADEMIC PROGRESS</p>
            <h2>Your report card</h2>
            <p class="muted">View your published results, teacher remarks and term details.</p>
        </div>
        <a class="btn" href="<?= e(route_link('me/report')) ?>">View my report card <span aria-hidden="true">→</span></a>
    </section>
<?php endif; ?>
