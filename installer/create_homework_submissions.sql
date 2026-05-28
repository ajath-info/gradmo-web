-- Homework student submissions (optional manual install; API also creates this table at runtime)
CREATE TABLE IF NOT EXISTS `homework_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `homework_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT 0,
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL DEFAULT 0,
  `subject_id` int(11) NOT NULL DEFAULT 0,
  `submission_text` text,
  `attachment` varchar(255) NOT NULL DEFAULT '',
  `marks` decimal(10,2) DEFAULT NULL,
  `remark` text,
  `eval_status` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_at` datetime NOT NULL,
  `evaluated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_homework_id` (`homework_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_eval_status` (`eval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teacher handout PDF on assignments (if missing)
-- ALTER TABLE `homeworks` ADD COLUMN `attachment` VARCHAR(255) NOT NULL DEFAULT '' AFTER `description`;
