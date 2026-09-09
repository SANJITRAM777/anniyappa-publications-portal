-- ============================================================
--  Anniyappa Publications Portal – Full Database Schema
--  Version 2 (Phase 2 – Complete)
--  Run this file once in phpMyAdmin or MySQL CLI.
--  It is safe to re-run (uses IF NOT EXISTS / IF NOT EXISTS
--  guards and ALTER…ADD IF NOT EXISTS where supported).
-- ============================================================

CREATE DATABASE IF NOT EXISTS `anniyappa_portal`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `anniyappa_portal`;

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- 1. ROLES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 2. USERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(191) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role_id`    INT NOT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3. USER PROFILES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NOT NULL UNIQUE,
  `full_name`   VARCHAR(100) NOT NULL,
  `phone`       VARCHAR(20)  DEFAULT NULL,
  `address`     TEXT         DEFAULT NULL,
  `bio`         TEXT         DEFAULT NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_avatar.png',
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3b. AUTHORS (for book catalog and profile integration)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `authors` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NULL UNIQUE,
  `name`        VARCHAR(100) NOT NULL,
  `bio`         TEXT DEFAULT NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_avatar.png',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 4. INQUIRIES (extended for admin reply panel)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(100)  NOT NULL,
  `email`        VARCHAR(191)  NOT NULL,
  `phone`        VARCHAR(20)   DEFAULT NULL,
  `subject`      VARCHAR(255)  NOT NULL,
  `inquiry_type` VARCHAR(80)   DEFAULT 'General',
  `message`      TEXT          NOT NULL,
  `admin_reply`  TEXT          DEFAULT NULL,
  `replied_at`   TIMESTAMP     NULL DEFAULT NULL,
  `status`       ENUM('New','Read','Resolved') DEFAULT 'New',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. CATEGORIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('Book','Blog','Course') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6. BOOKS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `books` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(150) NOT NULL,
  `author`      VARCHAR(150) DEFAULT NULL,
  `category_id` INT          NOT NULL,
  `description` TEXT         NOT NULL,
  `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cover_image` VARCHAR(255) DEFAULT 'default_book.png',
  `pdf_path`    VARCHAR(255) DEFAULT NULL,
  `stock`       INT NOT NULL DEFAULT 0,
  `isbn`        VARCHAR(30)  DEFAULT NULL,
  `publisher`   VARCHAR(150) DEFAULT NULL,
  `edition`     VARCHAR(50)  DEFAULT NULL,
  `pages`       INT          DEFAULT NULL,
  `language`    VARCHAR(50)  DEFAULT 'English',
  `is_featured` TINYINT(1)   DEFAULT 0,
  `status`      ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6b. BOOK AUTHORS (junction table for books and authors)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `book_authors` (
  `book_id`   INT NOT NULL,
  `author_id` INT NOT NULL,
  PRIMARY KEY (`book_id`, `author_id`),
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 7. REVIEWS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reviews` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `book_id`     INT NOT NULL,
  `user_id`     INT NOT NULL,
  `rating`      INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_text` TEXT DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 8. DOWNLOADS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `downloads` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `book_id`       INT NOT NULL,
  `user_id`       INT NOT NULL,
  `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 9. COURSES (extended for admin LMS editor)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `courses` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `title`           VARCHAR(150) NOT NULL,
  `description`     TEXT         NOT NULL,
  `instructor_name` VARCHAR(150) DEFAULT NULL,
  `instructor_id`   INT          DEFAULT NULL,
  `category`        VARCHAR(100) DEFAULT NULL,
  `duration`        VARCHAR(80)  DEFAULT NULL,
  `price`           DECIMAL(10,2) DEFAULT 0.00,
  `level`           ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
  `thumbnail`       VARCHAR(255) DEFAULT 'default_course.jpg',
  `status`          ENUM('Active','Inactive','Draft') DEFAULT 'Active',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 10. COURSE LESSONS (used by admin/courses.php & student/courses.php)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `course_lessons` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`  INT  NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `content`    TEXT         DEFAULT NULL,
  `video_url`  VARCHAR(255) DEFAULT NULL,
  `sort_order` INT          DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 11. COURSE MATERIALS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `course_materials` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `lesson_id` INT          NOT NULL,
  `title`     VARCHAR(150) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `type`      VARCHAR(50)  DEFAULT 'PDF',
  FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 12. COURSE ENROLLMENTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`        INT NOT NULL,
  `student_id`       INT NOT NULL,
  `progress_percent` INT DEFAULT 0,
  `enrolled_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at`     TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `student_course_unique` (`student_id`, `course_id`),
  FOREIGN KEY (`course_id`)  REFERENCES `courses` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 13. QUIZZES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `course_id`  INT NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 14. QUIZ QUESTIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id`        INT NOT NULL,
  `question_text`  TEXT NOT NULL,
  `option_a`       VARCHAR(255) NOT NULL,
  `option_b`       VARCHAR(255) NOT NULL,
  `option_c`       VARCHAR(255) NOT NULL,
  `option_d`       VARCHAR(255) NOT NULL,
  `correct_option` ENUM('A','B','C','D') NOT NULL,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 15. QUIZ RESULTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `results` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id`      INT NOT NULL,
  `student_id`   INT NOT NULL,
  `score`        INT NOT NULL,
  `max_score`    INT NOT NULL,
  `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`quiz_id`)    REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 16. INTERNSHIPS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `internships` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `title`          VARCHAR(150) NOT NULL,
  `description`    TEXT         NOT NULL,
  `domain`         VARCHAR(100) NOT NULL,
  `duration_weeks` INT          NOT NULL DEFAULT 4,
  `stipend`        DECIMAL(10,2) DEFAULT 0.00,
  `seats`          INT          DEFAULT 0,
  `status`         ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 17. INTERNSHIP APPLICATIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `applications` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `internship_id`  INT NOT NULL,
  `student_id`     INT NOT NULL,
  `resume_path`    VARCHAR(255) NOT NULL,
  `cover_letter`   TEXT         DEFAULT NULL,
  `status`         ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `applied_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)    REFERENCES `users`        (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 18. ASSIGNMENTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assignments` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `internship_id`  INT          NOT NULL,
  `student_id`     INT          NOT NULL,
  `title`          VARCHAR(150) NOT NULL,
  `description`    TEXT         NOT NULL,
  `file_path`      VARCHAR(255) DEFAULT NULL,
  `grade`          VARCHAR(10)  DEFAULT NULL,
  `feedback`       TEXT         DEFAULT NULL,
  `submitted_at`   TIMESTAMP    NULL DEFAULT NULL,
  `status`         ENUM('Pending','Submitted','Graded') DEFAULT 'Pending',
  FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)    REFERENCES `users`        (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 19. ATTENDANCE
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT  NOT NULL,
  `date`       DATE NOT NULL,
  `status`     ENUM('Present','Absent') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `student_date_unique` (`student_id`, `date`),
  FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 20. RESEARCH PROJECTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `research_projects` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(255) NOT NULL,
  `description` TEXT         NOT NULL,
  `faculty_id`  INT          NOT NULL,
  `status`      ENUM('Open','In_Progress','Completed') DEFAULT 'Open',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 21. PROPOSALS (Research chapter submissions)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `proposals` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`      INT          NOT NULL,
  `author_id`       INT          NOT NULL,
  `proposal_title`  VARCHAR(255) NOT NULL,
  `abstract`        TEXT         NOT NULL,
  `file_path`       VARCHAR(255) NOT NULL,
  `status`          ENUM('Pending','Under_Review','Accepted','Rejected') DEFAULT 'Pending',
  `reviewer_notes`  TEXT         DEFAULT NULL,
  `submitted_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `research_projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`)  REFERENCES `users`             (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 22. EVENTS (extended for admin/events.php)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `title`             VARCHAR(255)  NOT NULL,
  `description`       TEXT          DEFAULT NULL,
  `speaker`           VARCHAR(150)  DEFAULT NULL,
  `event_date`        DATE          NOT NULL,
  `event_time`        TIME          DEFAULT NULL,
  `venue`             VARCHAR(255)  DEFAULT NULL,
  `type`              VARCHAR(80)   DEFAULT 'Webinar',
  `seats_available`   INT           DEFAULT 0,
  `registration_fee`  DECIMAL(10,2) DEFAULT 0.00,
  `meet_link`         VARCHAR(255)  DEFAULT NULL,
  `banner`            VARCHAR(255)  DEFAULT 'default_event.jpg',
  `status`            ENUM('Upcoming','Ongoing','Completed','Cancelled') DEFAULT 'Upcoming',
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 23. EVENT REGISTRATIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `event_registrations` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `event_id`       INT          NOT NULL,
  `user_id`        INT          NOT NULL,
  `attendee_name`  VARCHAR(150) DEFAULT NULL,
  `phone`          VARCHAR(20)  DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_event_unique` (`user_id`, `event_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 24. BLOG POSTS (replaces legacy `posts` table)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `title`          VARCHAR(255) NOT NULL,
  `slug`           VARCHAR(280) NOT NULL UNIQUE,
  `content`        LONGTEXT     NOT NULL,
  `excerpt`        TEXT         DEFAULT NULL,
  `category`       VARCHAR(100) DEFAULT NULL,
  `tags`           VARCHAR(255) DEFAULT NULL,
  `author`         VARCHAR(150) DEFAULT 'Admin',
  `featured_image` VARCHAR(255) DEFAULT 'default_blog.jpg',
  `status`         ENUM('Draft','Published') DEFAULT 'Draft',
  `views`          INT DEFAULT 0,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 25. BLOG COMMENTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `blog_comments` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `post_id`      INT          NOT NULL,
  `author_name`  VARCHAR(100) DEFAULT 'Anonymous',
  `author_email` VARCHAR(191) DEFAULT NULL,
  `comment`      TEXT         NOT NULL,
  `status`       ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 26. CART
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cart` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`  INT NOT NULL,
  `book_id`  INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  UNIQUE KEY `user_book_unique` (`user_id`, `book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 27. COUPONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `coupons` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `code`             VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` INT         NOT NULL DEFAULT 10,
  `expiry_date`      DATE        NOT NULL,
  `active`           TINYINT(1)  DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 28. ORDERS (extended with payment_method, discount_amount, updated_at)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT           NOT NULL,
  `invoice_number`  VARCHAR(100)  NOT NULL UNIQUE,
  `total_amount`    DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `coupon_code`     VARCHAR(50)   DEFAULT NULL,
  `payment_method`  VARCHAR(50)   DEFAULT NULL,
  `transaction_id`  VARCHAR(100)  DEFAULT NULL,
  `status`          ENUM('Pending','Processing','Shipped','Delivered','Cancelled','Refunded') DEFAULT 'Pending',
  `shipping_address` TEXT         DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 29. ORDER ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`   INT           NOT NULL,
  `book_id`    INT           NOT NULL,
  `quantity`   INT           NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`)  REFERENCES `books`  (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 30. CERTIFICATES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `certificates` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT          NOT NULL,
  `type`             ENUM('Internship','Course','Event') NOT NULL,
  `reference_id`     INT          NOT NULL,
  `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
  `issue_date`       DATE         NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 31. GALLERY
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `gallery` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(150)  NOT NULL,
  `image_path`  VARCHAR(255)  NOT NULL,
  `category`    VARCHAR(80)   DEFAULT 'General',
  `description` TEXT          DEFAULT NULL,
  `sort_order`  INT           DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 32. TESTIMONIALS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)  NOT NULL,
  `designation` VARCHAR(150)  DEFAULT NULL,
  `message`     TEXT          NOT NULL,
  `rating`      INT           DEFAULT 5,
  `photo`       VARCHAR(255)  DEFAULT 'default_avatar.png',
  `is_featured` TINYINT(1)    DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 33. SITE SETTINGS (key-value pairs for dynamic config)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(80) NOT NULL UNIQUE,
  `setting_val` TEXT        DEFAULT NULL,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
