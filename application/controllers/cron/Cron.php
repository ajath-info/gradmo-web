<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->model('db_model');
        $this->load->library('common');
        // notification_service is also in autoload; load explicitly for CLI safety.
        $this->load->library('notification_service');
    }

    /**
     * Monthly Attendance Report (previous calendar month), batch-wise to every active student.
     *
     * Crontab (run on the 1st of each month at 08:00 server time):
     * 0 8 1 * * /usr/bin/php /path/to/education/index.php cron/cron attendance_report_monthly
     *
     * Windows Task Scheduler example:
     *   Program: C:\xampp\php\php.exe
     *   Arguments: C:\xampp\htdocs\education\index.php cron/cron attendance_report_monthly
     *   Trigger: Monthly, Day 1, 08:00
     */
    public function attendance_report_monthly()
    {
        // SMTP to many recipients is slow; avoid max_execution_time kills.
        @set_time_limit(0);
        ignore_user_abort(true);

        // IMPORTANT: use "first day of last month" — plain "-1 month" breaks on day 31
        // (e.g. 31 Jul → wrongly becomes July, not June).
        $month = date('Y-m', strtotime('first day of last month'));
        $month_label = date('F Y', strtotime($month . '-01'));

        if (!$this->db->table_exists('email_notification_logs')) {
            echo "ERROR: email_notification_logs table missing. Run installer/email_notification_logs.sql\n";
            return;
        }

        $tpl = $this->db_model->select_data(
            'id',
            'templates',
            array('purpose' => 'attendance_report_monthly', 'template_for' => 'email', 'status' => '1'),
            1
        );
        if (empty($tpl[0]['id'])) {
            echo "ERROR: email template purpose=attendance_report_monthly is missing or inactive.\n";
            return;
        }

        $students = $this->db
            ->select('sb.student_id, sb.batch_id, s.name, s.email')
            ->from('student_batchs sb')
            ->join('students s', 's.id = sb.student_id')
            ->where('sb.status', 1)
            ->where('s.status', 1)
            ->get()
            ->result_array();

        $sent_count = 0;
        $skip_no_email = 0;
        $skip_already = 0;
        $fail_count = 0;

        foreach ($students as $student) {
            $student_id = (int) $student['student_id'];
            $batch_id = (int) $student['batch_id'];
            $email = trim((string) $student['email']);

            if ($email === '') {
                $skip_no_email++;
                continue;
            }

            if ($this->common->email_already_sent(
                'attendance_report_monthly',
                $student_id,
                'student',
                $batch_id,
                $month
            )) {
                $skip_already++;
                continue;
            }

            $attendance = $this->getAttendanceSummary($student_id, $batch_id, $month);

            $result = $this->notification_service->common_send_email_push(array(
                'purpose' => 'attendance_report_monthly',
                'user_id' => $student_id,
                'user_type' => 'student',
                'to_email' => $email,
                'push' => false,
                'in_app' => false,
                'dynamic_var' => array(
                    'STUDENT_NAME' => $student['name'],
                    'MONTH' => $month_label,
                    'MONTH_NAME' => $month_label,
                    'CURRENT_YEAR' => date('Y'),
                    'PRESENT_DAYS' => $attendance['present'],
                    'ABSENT_DAYS' => $attendance['absent'],
                    'TOTAL_MARKED_DAYS' => $attendance['total_days'],
                    'ATTENDANCE_PERCENTAGE' => $attendance['percentage'],
                ),
            ));

            if (!empty($result['status'])) {
                $sent_count++;
                $this->common->email_log_sent(
                    'attendance_report_monthly',
                    $student_id,
                    'student',
                    $batch_id,
                    $month,
                    $email
                );
            } else {
                $fail_count++;
            }
        }

        echo 'Attendance report for ' . $month_label
            . ' completed. Emails sent: ' . $sent_count
            . ' | skipped(no email): ' . $skip_no_email
            . ' | skipped(already sent): ' . $skip_already
            . ' | failed: ' . $fail_count
            . "\n";
    }

    /**
     * Attendance summary for one student+batch in YYYY-MM.
     * Report only Present / Absent (late & half_day count as Present).
     */
    private function getAttendanceSummary($student_id, $batch_id, $month)
    {
        $rows = $this->db
            ->where('student_id', (int) $student_id)
            ->where('batch_id', (int) $batch_id)
            ->like('date', $month, 'after')
            ->get('attendance')
            ->result_array();

        $present = 0;
        $absent = 0;

        foreach ($rows as $row) {
            $status = strtolower(trim(isset($row['day_status']) ? (string) $row['day_status'] : ''));
            if ($status === 'half' || $status === 'halfday') {
                $status = 'half_day';
            }

            // Empty / present / late / half_day → Present. Only explicit absent → Absent.
            if ($status === 'absent') {
                $absent++;
            } else {
                $present++;
            }
        }

        $total = $present + $absent;
        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

        return array(
            'present' => $present,
            'absent' => $absent,
            'total_days' => $total,
            'percentage' => $percentage,
        );
    }
}
