<?php
// generate revised schedule for missed courses
function get_revised_schedule($conn, $student_id) {
    $student_id = intval($student_id);

    // get missed courses
    $missed = $conn->query("
        SELECT e.enrollment_id, e.course_id, e.term_name, c.course_code, c.course_name, c.credits, c.prereq_course_id
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = $student_id AND e.status = 'missed'
    ")->fetch_all(MYSQLI_ASSOC);

    if (empty($missed)) return null;

    // all available terms
    $all_terms = ['Fall 2025','Winter 2026','Spring 2026','Summer 2026','Fall 2026','Winter 2027','Spring 2027'];

    // get already-used terms
    $used_terms = $conn->query("
        SELECT DISTINCT term_name FROM enrollments
        WHERE student_id = $student_id AND status IN ('planned','enrolled')
    ")->fetch_all(MYSQLI_ASSOC);
    $used_terms = array_column($used_terms, 'term_name');

    // get completed courses
    $completed_ids = array_column(
        $conn->query("SELECT course_id FROM enrollments WHERE student_id = $student_id AND status = 'completed'")->fetch_all(MYSQLI_ASSOC),
        'course_id'
    );

    $suggestions = [];

    // process missed courses
    foreach ($missed as $m) {
        // check prerequisite met
        $prereq_met = true;
        if ($m['prereq_course_id']) {
            $prereq_met = in_array($m['prereq_course_id'], $completed_ids);
        }

        // find next available term
        $missed_index = array_search($m['term_name'], $all_terms);
        $next_term = null;
        for ($i = $missed_index + 1; $i < count($all_terms); $i++) {
            $next_term = $all_terms[$i];
            break;
        }

        if ($next_term) {
            $suggestions[] = [
                'enrollment_id' => $m['enrollment_id'],
                'course_id' => $m['course_id'],
                'course_code' => $m['course_code'],
                'course_name' => $m['course_name'],
                'credits' => $m['credits'],
                'from_term' => $m['term_name'],
                'to_term' => $next_term,
                'prereq_met' => $prereq_met,
            ];
        }
    }

    return empty($suggestions) ? null : $suggestions;
}
?>