<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // TEMP: show errors while debugging the cron (remove once working).
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $this->load->database();
        $this->load->model('db_model');
        $this->load->library('common');
    }

    /**
     * Monthly Attendance Report
     * Cron:
     * 0 8 1 * * /usr/bin/php /path/to/index.php cron attendance_report_monthly
     */
    public function attendance_report_monthly()
    {
        // Sending many emails over SMTP is slow; a normal web request would hit
        // max_execution_time (~30s) and 500. Cron jobs should run without a time limit.
        @set_time_limit(0);
        ignore_user_abort(true);

        $month = date('Y-m', strtotime('-1 month'));

        $students = $this->db
            ->select('
                sb.student_id,
                sb.batch_id,
                s.name,
                s.email
            ')
            ->from('student_batchs sb')
            ->join('students s', 's.id = sb.student_id')
            ->where('sb.status', 1)
            ->where('s.status', 1)
            ->get()
            ->result_array();

        $sent_count = 0;

        foreach ($students as $student)
        {
            $student_id = (int)$student['student_id'];
            $batch_id   = (int)$student['batch_id'];
            $email      = trim($student['email']);

            if ($email == '') {
                continue;
            }

            // Skip if already sent
            if (
                $this->common->email_already_sent(
                    'attendance_report_monthly',
                    $student_id,
                    'student',
                    $batch_id,
                    $month
                )
            ) {
                continue;
            }

            $attendance = $this->getAttendanceSummary(
                $student_id,
                $batch_id,
                $month
            );

            $result = $this->common->send_email(array(
                'purpose'   => 'attendance_report_monthly',
                'user_id'   => $student_id,
                'user_type' => 'student',
                'to_email'  => $email,
                'dynamic_var' => array(
                    'STUDENT_NAME'          => $student['name'],
                    'MONTH'                => date('F Y', strtotime($month . '-01')),
                    'MONTH_NAME'           => date('F Y', strtotime($month . '-01')),
                    'CURRENT_YEAR'         => date('Y'),
                    'PRESENT_DAYS'         => $attendance['present'],
                    'LATE_DAYS'            => $attendance['late'],
                    'ABSENT_DAYS'          => $attendance['absent'],
                    'ATTENDANCE_PERCENTAGE'=> $attendance['percentage']
                )
            ));

            if (!empty($result['status']))
            {
                $sent_count++;

                $this->common->email_log_sent(
                    'attendance_report_monthly',
                    $student_id,
                    'student',
                    $batch_id,
                    $month,
                    $email
                );
            }
        }

        echo "Attendance report completed. Emails sent: ".$sent_count;
    }

    /**
     * Calculate attendance summary
     */
    private function getAttendanceSummary($student_id, $batch_id, $month)
    {
        $rows = $this->db
            ->where('student_id', $student_id)
            ->where('batch_id', $batch_id)
            ->like('date', $month, 'after')
            ->get('attendance')
            ->result_array();

        $present = 0;
        $late    = 0;
        $absent  = 0;

        foreach ($rows as $row)
        {
            $status = strtolower(trim($row['day_status']));

            if ($status == 'present') {
                $present++;
            }
            elseif ($status == 'late') {
                $late++;
            }
            else {
                $absent++;
            }
        }

        $total = $present + $late + $absent;

        $percentage = $total > 0
            ? round((($present + $late) / $total) * 100, 2)
            : 0;

        return array(
            'present'    => $present,
            'late'       => $late,
            'absent'     => $absent,
            'total_days' => $total,
            'percentage' => $percentage
        );
    }
}