<?php
// check if student completed prerequisite
function student_meets_prereq($conn, $student_id, $course_id) {
    $course_id = intval($course_id);
    $student_id = intval($student_id);

    $row = $conn->query("SELECT prereq_course_id FROM courses WHERE course_id = $course_id")->fetch_assoc();

    if (!$row || $row['prereq_course_id'] === null) {
        return true;
    }

    $prereq_id = intval($row['prereq_course_id']);

    $result = $conn->query("
        SELECT 1 FROM enrollments
        WHERE student_id = $student_id
          AND course_id = $prereq_id
          AND status = 'completed'
    ");

    return $result && $result->num_rows > 0;
}
?>