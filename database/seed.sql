-- ============================================================
--  Anniyappa Publications Portal – Seed / Demo Data
--  Version 2  (matches schema_v2)
--  Safe to run after schema.sql. Uses INSERT IGNORE.
-- ============================================================
USE `anniyappa_portal`;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- ROLES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Faculty'),
(3, 'Student'),
(4, 'Author');

-- ─────────────────────────────────────────────────────────────
-- USERS  (password = 'password123')
-- Hash generated with: password_hash('password123', PASSWORD_BCRYPT)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `users` (`id`, `email`, `password`, `role_id`) VALUES
(1, 'admin@anniyappa.com',   '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 1),
(2, 'faculty@anniyappa.com', '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 2),
(3, 'student@anniyappa.com', '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 3),
(4, 'author@anniyappa.com',  '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 4),
(5, 'kumar@anniyappa.com',   '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 2),
(6, 'priya@anniyappa.com',   '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 3),
(7, 'arun@anniyappa.com',    '$2y$10$KslbLz.F3rHcTroWMW2RmudoVE8s./muNemrT29CTjaCP0jKKRk4G', 3);

-- ─────────────────────────────────────────────────────────────
-- USER PROFILES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `user_profiles` (`user_id`, `full_name`, `phone`, `address`, `bio`) VALUES
(1, 'Administrator',      '+91 80 4912 3456',  'Office Suite 45, Knowledge Park, Bangalore',    'System administrator for the Anniyappa Publications web portal.'),
(2, 'Dr. R. Anniyappa',   '+91 98765 43210',   'Faculty Quarters, Knowledge Park, Bangalore',   'Senior editor of academic journals, specialist in Artificial Intelligence and ML.'),
(3, 'Rajesh Kumar',       '+91 99887 76655',   'Student Residence Block, Bangalore',            'Final year Computer Science student specialising in Web Technologies.'),
(4, 'Dr. V. Prasad',      '+91 91234 56789',   'Author Society, Hyderabad',                     'Renowned researcher and author of multiple reference books on Data Science.'),
(5, 'Prof. S. Kumar',     '+91 92233 44556',   'Faculty Quarters, Knowledge Park, Bangalore',   'Professor of Web Technologies and lead developer of the SB Academic Initiative.'),
(6, 'Priya Sharma',       '+91 90123 45678',   'Nungambakkam, Chennai',                         'Second year B.E. student, keen interest in AI and research publishing.'),
(7, 'Arun Selvam',        '+91 88001 23456',   'T. Nagar, Chennai',                             'Engineering final year student looking for internship opportunities.');

-- ─────────────────────────────────────────────────────────────
-- AUTHORS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `authors` (`id`, `user_id`, `name`, `bio`) VALUES
(1, 2, 'Dr. R. Anniyappa', 'Senior editor of academic journals, specialist in Artificial Intelligence and ML.'),
(2, 4, 'Dr. V. Prasad', 'Renowned researcher and author of multiple reference books on Data Science.'),
(3, 5, 'Prof. S. Kumar', 'Professor of Web Technologies and lead developer of the SB Academic Initiative.'),
(4, NULL, 'Prof. S. Mukerjee', 'Blockchain researcher and health informatics specialist.'),
(5, NULL, 'Dr. M. Reddy', 'Cloud solutions architect and enterprise systems author.'),
(6, NULL, 'Prof. A. K. Sen', 'IoT designer and embedded systems professor.');

-- ─────────────────────────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `categories` (`id`, `name`, `type`) VALUES
(1,  'Computer Science',       'Book'),
(2,  'Artificial Intelligence','Book'),
(3,  'Blockchain',             'Book'),
(4,  'Data Science',           'Book'),
(5,  'Cloud Computing',        'Book'),
(6,  'IoT Systems',            'Book'),
(7,  'Academic Writing',       'Book'),
(8,  'Research News',          'Blog'),
(9,  'Internship Updates',     'Blog'),
(10, 'Student Achievements',   'Blog'),
(11, 'Technology Trends',      'Blog'),
(12, 'Engineering',            'Course'),
(13, 'Research Methods',       'Course');

-- ─────────────────────────────────────────────────────────────
-- BOOKS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `books` (`id`, `title`, `author`, `category_id`, `description`, `price`, `cover_image`, `pdf_path`, `stock`, `isbn`, `edition`, `pages`, `is_featured`, `status`) VALUES
(1, 'Handbook of AI in Education',         'Dr. R. Anniyappa',   2, 'Comprehensive guide exploring AI implementations in higher education, curriculum personalisation, and automated grading.', 499.00, 'ai_education.png',    'AI_in_Education_Sample.pdf',            25, '978-81-956789-1-2', '3rd Edition', 420, 1, 'Active'),
(2, 'Advanced Web Technologies',           'Prof. S. Kumar',      1, 'In-depth reference on modern JavaScript architectures, backend databases, MVC systems, and responsive designs.',          599.00, 'web_tech.png',        'Advanced_Web_Tech_Sample.pdf',          40, '978-81-956789-2-9', '2nd Edition', 550, 1, 'Active'),
(3, 'Blockchain in Healthcare',            'Prof. S. Mukerjee',  3, 'Analysing distributed ledger technology for electronic health records, drug supply chains, and data security.',            799.00, 'blockchain.png',      'Blockchain_Healthcare_Sample.pdf',      15, '978-81-956789-3-6', '1st Edition', 370, 1, 'Active'),
(4, 'Data Science Fundamentals',           'Dr. V. Prasad',       4, 'Essential guide covering statistics, data analysis pipelines, regression models, and introduction to ML with Python.',    399.00, 'data_science.png',    'Data_Science_Fundamentals_Sample.pdf',  50, '978-81-956789-4-3', '4th Edition', 480, 1, 'Active'),
(5, 'Cloud Architecture Patterns',         'Dr. M. Reddy',        5, 'Design book detailing microservices, containerisation, serverless architectures, and AWS/Azure deployment schemas.',      699.00, 'cloud_arch.png',      'Cloud_Architecture_Sample.pdf',         30, '978-81-956789-5-0', '2nd Edition', 500, 1, 'Active'),
(6, 'IoT & Smart Systems',                 'Prof. A. K. Sen',     6, 'Introductory manual to Raspberry Pi, Arduino, sensor integration protocols, and communications in industrial setups.',   450.00, 'iot_systems.png',     'IoT_Smart_Systems_Sample.pdf',          10, '978-81-956789-6-7', '1st Edition', 350, 1, 'Active'),
(7, 'Principles of Academic Writing',      'Dr. R. Anniyappa',   7, 'Critical handbook outlining formatting guides, journal guidelines, citation conventions, and plagiarism metrics.',         0.00,   'academic_writing.png','Principles_of_Academic_Writing.pdf',    999,'978-81-956789-7-4', '5th Edition', 280, 1, 'Active'),
(8, 'Machine Learning with Python',        'Dr. V. Prasad',       2, 'Hands-on guide to sklearn, pandas, neural networks, and production-ready ML pipelines using real datasets.',            549.00, 'ml_python.png',       NULL,                                    35, '978-81-956789-8-1', '1st Edition', 430, 0, 'Active'),
(9, 'Database Systems: Theory & Practice', 'Prof. S. Kumar',      1, 'Complete reference for relational database design, normalisation, indexing, transactions, and NoSQL alternatives.',      479.00, 'database_sys.png',    NULL,                                    20, '978-81-956789-9-8', '3rd Edition', 510, 0, 'Active');

-- ─────────────────────────────────────────────────────────────
-- BOOK AUTHORS MAPPING
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `book_authors` (`book_id`, `author_id`) VALUES
(1, 1),
(2, 3),
(3, 4),
(4, 2),
(5, 5),
(6, 6),
(7, 1),
(8, 2),
(9, 3);

-- ─────────────────────────────────────────────────────────────
-- REVIEWS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `reviews` (`book_id`, `user_id`, `rating`, `review_text`) VALUES
(1, 3, 5, 'Excellent introduction to AI in education — very thorough and practical.'),
(2, 3, 4, 'Very structured, covers modern concepts with solid code examples.'),
(4, 6, 5, 'Perfect for beginners. The Python examples are clean and easy to follow.'),
(7, 3, 5, 'Extremely helpful for paper submission. Must-read for every postgraduate student!'),
(5, 7, 4, 'Great real-world cloud patterns; AWS section is particularly comprehensive.');

-- ─────────────────────────────────────────────────────────────
-- COURSES (LMS)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `courses` (`id`, `title`, `description`, `instructor_name`, `instructor_id`, `category`, `duration`, `price`, `level`, `thumbnail`, `status`) VALUES
(1, 'Academic Writing and Publishing Systems',   'Learn standard academic writing guidelines, plagiarism controls, indexing rules, LaTeX formatting, and journal editorial pipelines.', 'Dr. R. Anniyappa', 2, 'Research Methods', '6 Weeks',  0.00,   'Beginner',     'course_writing.png', 'Active'),
(2, 'Full Stack Web Engineering with PHP',       'Complete hands-on course covering HTML5, CSS3, JavaScript, PHP 8, and PDO MySQL database management from scratch.',                 'Prof. S. Kumar',    5, 'Engineering',     '10 Weeks', 999.00, 'Intermediate', 'course_web.png',     'Active'),
(3, 'Introduction to Machine Learning',          'Covers supervised, unsupervised learning, neural networks, and model evaluation using Python scikit-learn library.',               'Dr. V. Prasad',     4, 'Engineering',     '8 Weeks',  799.00, 'Intermediate', 'course_ml.png',      'Active'),
(4, 'Research Methodology & Data Analysis',      'A structured approach to defining research questions, choosing methodology, collecting data, and writing publishable findings.',    'Dr. R. Anniyappa', 2, 'Research Methods', '4 Weeks',  0.00,   'Beginner',     'course_research.png','Active');

-- ─────────────────────────────────────────────────────────────
-- COURSE LESSONS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `course_lessons` (`id`, `course_id`, `title`, `content`, `video_url`, `sort_order`) VALUES
(1, 1, 'Introduction to Scholarly Literature',    'What makes literature scholarly — peer reviews, research indices, and impact factors.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 1),
(2, 1, 'Structuring Your Research Paper',         'Detailed breakdown of Abstract, Introduction, Methodology, Results, and Discussions (IMRAD format).', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 2),
(3, 1, 'Citation Styles: APA, MLA & IEEE',        'Understanding APA 7th edition, MLA 9th edition, and IEEE citation formats with worked examples.', NULL, 3),
(4, 2, 'PHP Session Control & PDO Connections',   'Understanding standard PHP sessions, cookie structures, and initialising safe database states using PDO.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 1),
(5, 2, 'Bootstrap 5 — Responsive Layout Systems', 'Grid system, flex utilities, components, and dark mode implementation with Bootstrap 5.3.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 2),
(6, 2, 'MVC Architecture in PHP Projects',        'Structuring PHP projects using Model-View-Controller separation with routing and templating.', NULL, 3),
(7, 3, 'Python for Data Science Essentials',      'NumPy, Pandas, Matplotlib and data preprocessing pipeline from raw CSV to cleaned dataset.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 1),
(8, 3, 'Supervised Learning Algorithms',          'Linear regression, logistic regression, decision trees, and random forests with sklearn examples.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 2);

-- ─────────────────────────────────────────────────────────────
-- COURSE ENROLLMENTS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `course_enrollments` (`course_id`, `student_id`, `progress_percent`) VALUES
(1, 3, 65),
(2, 3, 20),
(1, 6, 30),
(3, 6, 10),
(2, 7,  0);

-- ─────────────────────────────────────────────────────────────
-- QUIZZES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `quizzes` (`id`, `course_id`, `title`) VALUES
(1, 1, 'Academic Writing Concepts Quiz'),
(2, 2, 'PHP & Database Fundamentals Quiz');

-- ─────────────────────────────────────────────────────────────
-- QUIZ QUESTIONS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `quiz_questions` (`quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(1, 'What does the acronym IMRAD stand for?',          'Introduction, Methodology, Results, and Discussion', 'Introduction, Materials, Research, and Data', 'Index, Method, Review, and Analysis', 'Investigation, Materials, Results, and Documentation', 'A'),
(1, 'Which indexing is considered high-standard?',     'Google Scholar', 'Scopus / Web of Science', 'Yahoo Directories', 'Wikipedia', 'B'),
(1, 'APA citation format is published by?',            'American Psychological Association', 'American Publishing Authority', 'Academic Paper Association', 'Advanced Publishing Agency', 'A'),
(2, 'Which PHP function safely hashes a password?',    'md5()', 'sha1()', 'password_hash()', 'crypt()', 'C'),
(2, 'What does PDO stand for?',                        'PHP Data Object', 'PHP Data Operations', 'PHP Database Override', 'Procedural Database Object', 'A'),
(2, 'Which Bootstrap class creates a flex container?', 'd-block', 'd-flex', 'container-fluid', 'row-flex', 'B');

-- ─────────────────────────────────────────────────────────────
-- INTERNSHIPS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `internships` (`id`, `title`, `description`, `domain`, `duration_weeks`, `stipend`, `seats`) VALUES
(1, 'Content Curation & Publishing',     'Learn scholarly typesetting, editorial workflows, metadata indexing, and proofreading of academic journals.',          'Editorial',       8,  2000.00, 10),
(2, 'Web Portal Development',            'Contribute to building our PHP/MySQL educational portal. Work on dashboard modules, API integration, and security.',   'Web Development', 12, 3500.00, 8),
(3, 'Technical Writing & Research',      'Conduct literature reviews, draft research briefs, compile technical documentation, and write publication reviews.',   'Research',        6,  1500.00, 15),
(4, 'Data Analytics Internship',         'Work with real-world datasets using Python, Pandas and Tableau. Generate analytics reports for academic performance.',  'Data Science',    8,  3000.00, 6),
(5, 'Digital Marketing for Education',  'Manage social media campaigns, SEO strategies, and email marketing for Anniyappa Publications outreach programs.',     'Marketing',       6,  2500.00, 5);

-- ─────────────────────────────────────────────────────────────
-- INTERNSHIP APPLICATIONS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `applications` (`internship_id`, `student_id`, `resume_path`, `status`) VALUES
(2, 3, 'resumes/rajesh_cv.pdf',  'Approved'),
(1, 6, 'resumes/priya_cv.pdf',   'Pending'),
(3, 7, 'resumes/arun_cv.pdf',    'Pending');

-- ─────────────────────────────────────────────────────────────
-- ATTENDANCE
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `attendance` (`student_id`, `date`, `status`) VALUES
(3, '2026-06-10', 'Present'),
(3, '2026-06-11', 'Present'),
(3, '2026-06-12', 'Absent'),
(3, '2026-06-13', 'Present'),
(3, '2026-06-14', 'Present'),
(3, '2026-06-15', 'Present'),
(6, '2026-06-14', 'Present'),
(6, '2026-06-15', 'Absent');

-- ─────────────────────────────────────────────────────────────
-- ASSIGNMENTS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `assignments` (`internship_id`, `student_id`, `title`, `description`, `status`, `grade`) VALUES
(2, 3, 'Build a Dynamic Login System',  'Create a complete PHP/MySQL login system with role-based redirection.',   'Graded', 'A'),
(2, 3, 'Implement Book Catalog Module', 'Build a CRUD-based book listing with search and filter functionality.',   'Submitted', NULL),
(1, 6, 'Proofread Chapter 3',           'Review and annotate editorial errors in the provided sample chapter PDF.', 'Pending', NULL);

-- ─────────────────────────────────────────────────────────────
-- CERTIFICATES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `certificates` (`user_id`, `type`, `reference_id`, `certificate_code`, `issue_date`) VALUES
(3, 'Internship', 2, 'CERT-AP-INT-2026-001', '2026-06-01'),
(3, 'Course',     1, 'CERT-AP-CRS-2026-002', '2026-05-20');

-- ─────────────────────────────────────────────────────────────
-- RESEARCH PROJECTS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `research_projects` (`id`, `title`, `description`, `faculty_id`, `status`) VALUES
(1, 'Deep Learning Models in Diagnostic Healthcare',     'Open collaboration on convolutional neural networks in analysing medical MRI scans for disease prediction.', 2, 'Open'),
(2, 'Blockchain for Academic Credential Verification',  'Exploring decentralised ledger systems to authenticate and verify academic certificates.', 5, 'In_Progress'),
(3, 'NLP-Based Automated Research Summarisation',       'Applying natural language processing to auto-generate abstracts from full-length research papers.',  2, 'Open');

-- ─────────────────────────────────────────────────────────────
-- PROPOSALS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `proposals` (`project_id`, `author_id`, `proposal_title`, `abstract`, `file_path`, `status`) VALUES
(1, 3, 'CNN-Based Tumour Detection in MRI', 'Proposing a ResNet-50 transfer learning approach to detect brain tumours in MRI datasets with 94% accuracy.', 'proposals/cnn_mri_proposal.pdf', 'Under_Review'),
(2, 7, 'Ethereum Smart Contracts for Certificates', 'Using Ethereum smart contracts to issue tamper-proof digital transcripts verified by employers.', 'proposals/eth_cert_proposal.pdf', 'Pending');

-- ─────────────────────────────────────────────────────────────
-- EVENTS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `events` (`id`, `title`, `description`, `speaker`, `event_date`, `event_time`, `venue`, `type`, `seats_available`, `registration_fee`, `meet_link`, `status`) VALUES
(1, 'International Scholarly Publishing Webinar', 'Learn how to get your research paper accepted in Scopus index journals from leading international editors.', 'Dr. R. Anniyappa', '2026-07-10', '10:30:00', 'Google Meet', 'Webinar', 200, 0.00,   'https://meet.google.com/abc-defg-hij', 'Upcoming'),
(2, 'Hands-on Technical Writing Workshop',        'A comprehensive training session on typesetting with LaTeX and bibliography management tools.',             'Prof. S. Kumar',   '2026-08-05', '14:00:00', 'Hall A, SB Campus, Chennai', 'Workshop', 60,  299.00, NULL, 'Upcoming'),
(3, 'AI in Education — Industry Panel Discussion','Panel of 5 industry leaders discuss how generative AI is reshaping personalized learning platforms.',        'Various Panelists', '2026-07-25', '16:00:00', 'Zoom',   'Conference', 500, 0.00, 'https://zoom.us/j/123456789', 'Upcoming'),
(4, 'Book Launch: Machine Learning with Python',  'Join the official virtual book launch of the bestselling Machine Learning with Python by Dr. V. Prasad.',   'Dr. V. Prasad',    '2026-06-30', '18:00:00', 'YouTube Live', 'Launch Event', 0, 0.00, 'https://youtube.com/live/anniyappa', 'Upcoming');

-- ─────────────────────────────────────────────────────────────
-- EVENT REGISTRATIONS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `event_registrations` (`event_id`, `user_id`, `attendee_name`, `phone`) VALUES
(1, 3, 'Rajesh Kumar',   '+91 99887 76655'),
(1, 6, 'Priya Sharma',   '+91 90123 45678'),
(3, 7, 'Arun Selvam',    '+91 88001 23456'),
(4, 3, 'Rajesh Kumar',   '+91 99887 76655');

-- ─────────────────────────────────────────────────────────────
-- BLOG POSTS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `blog_posts` (`id`, `title`, `slug`, `content`, `excerpt`, `category`, `tags`, `author`, `featured_image`, `status`) VALUES
(1, 'Understanding the Scopus Indexing Guidelines',
    'understanding-scopus-indexing-guidelines',
    '<p>Getting a research paper published requires complying with <strong>Scopus guidelines</strong>, which verify content validity, editor qualifications, citation impacts, and peer-review integrity.</p><p>Scopus evaluates journals on a rolling basis and considers h-index, CiteScore, and SNIP metrics before granting or renewing indexing status. Authors should ensure their work adheres to ethical research standards and is submitted only to non-predatory journals.</p>',
    'Learn the essential Scopus compliance criteria every academic author must follow before journal submission.',
    'Research News', 'scopus,indexing,academic,journal,publishing', 'Dr. R. Anniyappa', 'scopus_guidelines.jpg', 'Published'),

(2, 'The Future of AI in Publishing Platforms',
    'future-ai-publishing-platforms',
    '<p>AI is transforming publication systems by automating plagiarism screenings, suggesting editorial changes, and indexing content metadata rapidly.</p><p>Large language models (LLMs) are now being deployed to assist peer reviewers in identifying methodological inconsistencies and statistical errors, significantly reducing the average review turnaround from weeks to days.</p>',
    'Discover how artificial intelligence is reshaping academic publishing with automation and smart editorial tools.',
    'Technology Trends', 'AI,publishing,automation,LLM,editorial', 'Dr. R. Anniyappa', 'ai_publishing.jpg', 'Published'),

(3, 'Top 5 Internship Tips for Engineering Students',
    'top-5-internship-tips-engineering-students',
    '<p>Landing a strong internship can be the deciding factor between a good career and a great one. Here are five tips every engineering student must follow.</p><ol><li>Build a clean, one-page resume.</li><li>Contribute to GitHub open source projects.</li><li>Network on LinkedIn and attend webinars.</li><li>Apply early — before semester exams start.</li><li>Prepare for technical interviews with DSA practice.</li></ol>',
    'Five proven strategies to secure a high-quality engineering internship and make the most of it.',
    'Internship Updates', 'internship,students,engineering,career,tips', 'Admin', 'internship_tips.jpg', 'Published'),

(4, 'How to Write a Winning Research Proposal',
    'how-to-write-winning-research-proposal',
    '<p>A research proposal is your opportunity to convince reviewers that your study is worth funding and publishing. Key sections include the <strong>problem statement</strong>, literature review, methodology, timeline, and expected outcomes.</p><p>Keep your abstract under 300 words and align your methodology with the research gap you identified. Use standard citation formats (APA/IEEE) and proofread thoroughly before submission.</p>',
    'Step-by-step guide to crafting a compelling research proposal that gets accepted.',
    'Research News', 'research,proposal,methodology,academic', 'Dr. R. Anniyappa', 'research_proposal.jpg', 'Draft');

-- ─────────────────────────────────────────────────────────────
-- BLOG COMMENTS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `blog_comments` (`post_id`, `author_name`, `author_email`, `comment`, `status`) VALUES
(1, 'Rajesh Kumar',   'student@anniyappa.com', 'Very clear and concise — helped me format my final year manuscript.', 'Approved'),
(2, 'Priya Sharma',   'priya@anniyappa.com',   'Loved the section on LLMs in peer review. Very insightful!',          'Approved'),
(1, 'Dr. A. Mehta',   'mehta@university.edu',  'Could you add more details about Scopus CiteScore calculation?',       'Pending'),
(3, 'Arun Selvam',    'arun@anniyappa.com',     'These tips are exactly what I needed before my campus recruitment!',  'Pending');

-- ─────────────────────────────────────────────────────────────
-- CART
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `cart` (`user_id`, `book_id`, `quantity`) VALUES
(3, 3, 1),
(3, 5, 2);

-- ─────────────────────────────────────────────────────────────
-- COUPONS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `coupons` (`code`, `discount_percent`, `expiry_date`, `active`) VALUES
('ANNIYAPPA10',  10, '2026-12-31', 1),
('WELCOME20',    20, '2026-12-31', 1),
('STUDENT15',    15, '2026-12-31', 1),
('LAUNCH30',     30, '2026-07-31', 1);

-- ─────────────────────────────────────────────────────────────
-- ORDERS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `orders` (`id`, `user_id`, `invoice_number`, `total_amount`, `discount_amount`, `coupon_code`, `payment_method`, `transaction_id`, `status`) VALUES
(1, 3, 'INV-2026-0001', 1098.00, 122.00, 'ANNIYAPPA10', 'UPI',  'UPI20260601001', 'Delivered'),
(2, 6, 'INV-2026-0002',  799.00,   0.00, NULL,          'Card', 'CARD2026060202', 'Delivered'),
(3, 7, 'INV-2026-0003',  449.00,   0.00, NULL,          'COD',  'COD2026060303',  'Pending'),
(4, 3, 'INV-2026-0004',  699.00, 209.70, 'LAUNCH30',    'UPI',  'UPI20260605004', 'Processing');

-- ─────────────────────────────────────────────────────────────
-- ORDER ITEMS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `order_items` (`order_id`, `book_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 499.00),
(1, 2, 1, 599.00),
(2, 3, 1, 799.00),
(3, 6, 1, 450.00),
(4, 5, 1, 699.00);

-- ─────────────────────────────────────────────────────────────
-- INQUIRIES
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `inquiries` (`name`, `email`, `phone`, `subject`, `inquiry_type`, `message`, `status`) VALUES
('Dr. A. Sharma',  'sharma@university.edu',   '+91 97001 23456', 'Journal Publishing Query',         'Publishing',  'I would like to know the typical review turnaround duration for the Computer Science Journal.', 'New'),
('Ms. R. Nair',    'nair.r@college.ac.in',    '+91 99112 34567', 'Bulk Book Order Inquiry',           'E-Commerce',  'We want to order 50 copies of Advanced Web Technologies for our college library. Kindly share the institutional discount details.', 'New'),
('Mr. P. Joseph',  'joseph@startup.io',       '+91 88223 34455', 'Author Collaboration Proposal',     'Research',    'Our startup is working on a technical manual and would like to collaborate with Anniyappa Publications for co-authorship.', 'Read'),
('Arun Selvam',    'arun@anniyappa.com',       '+91 88001 23456', 'Internship Certificate Request',   'Internship',  'I have completed my internship but have not received the digital certificate. Kindly check and issue it.', 'Resolved');

-- ─────────────────────────────────────────────────────────────
-- GALLERY
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `gallery` (`title`, `image_path`, `category`, `sort_order`) VALUES
('Book Launch 2025 – AI Edition',          'gallery/book_launch_2025.jpg',   'Events',      1),
('International Research Webinar',         'gallery/webinar_2025.jpg',       'Events',      2),
('Technical Writing Workshop – Batch 3',  'gallery/workshop_batch3.jpg',    'Workshops',   3),
('Campus Connect Drive – IIT Chennai',    'gallery/campus_iit.jpg',         'Outreach',    4),
('Award Ceremony – Best Research Paper',  'gallery/award_ceremony.jpg',     'Achievements',5),
('Office Library & Reading Room',         'gallery/library_reading.jpg',    'Facilities',  6);

-- ─────────────────────────────────────────────────────────────
-- TESTIMONIALS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `testimonials` (`name`, `designation`, `message`, `rating`, `is_featured`) VALUES
('Dr. K. Venkatesh',   'Professor, IIT Madras',          'Anniyappa Publications continues to set the gold standard in academic publishing in South India. A treasure trove of engineering knowledge.', 5, 1),
('Ms. Divya R.',       'Final Year Student, Anna University', 'The LMS courses helped me secure a research internship at a reputed lab. The structured content is top class.', 5, 1),
('Mr. Sathish B.',     'Software Engineer, Infosys',     'I used the Web Technologies book during placement prep. Comprehensive, well-written, and worth every rupee!', 4, 1),
('Dr. P. Nair',        'Researcher, NIT Trichy',         'The research collaboration platform connected me with excellent faculty mentors and led to two co-authored papers.', 5, 1);

-- ─────────────────────────────────────────────────────────────
-- SITE SETTINGS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_val`) VALUES
('site_name',             'Anniyappa Publications'),
('site_tagline',          'Excellence in Academic Publishing'),
('site_email',            'info@anniyappa.com'),
('site_phone',            '+91 80 4912 3456'),
('site_address',          'Knowledge Park, Bangalore - 560001, Karnataka, India'),
('site_facebook',         'https://facebook.com/anniyappapublications'),
('site_twitter',          'https://twitter.com/anniyappa_pub'),
('site_instagram',        'https://instagram.com/anniyappapublications'),
('site_youtube',          'https://youtube.com/anniyappapublications'),
('maintenance_mode',      '0'),
('items_per_page',        '12'),
('currency_symbol',       '₹'),
('smtp_host',             'smtp.gmail.com'),
('smtp_port',             '587');

SET FOREIGN_KEY_CHECKS = 1;
