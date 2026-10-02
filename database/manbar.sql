-- MANBAR database (MySQL / MariaDB). Import with phpMyAdmin -> Import.
CREATE DATABASE IF NOT EXISTS `manbar` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `manbar`;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE universities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  short_name VARCHAR(20) NOT NULL,
  domain VARCHAR(120) NOT NULL UNIQUE,
  active INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roster (
  id INT AUTO_INCREMENT PRIMARY KEY,
  university_id INT NOT NULL,
  student_id VARCHAR(20) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  major VARCHAR(120) NULL,
  faculty VARCHAR(120) NULL,
  year_level INT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (university_id, student_id),
  FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  university_id INT NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  google_sub VARCHAR(64) NULL,
  student_id VARCHAR(20) NULL,
  full_name VARCHAR(150) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'student',      
  status VARCHAR(20) NOT NULL DEFAULT 'active',     
  verified INT NOT NULL DEFAULT 0,                  
  avatar_url VARCHAR(500) NULL,
  theme INT NOT NULL DEFAULT 0,                     
  headline VARCHAR(160) NULL,
  bio TEXT NULL,
  major VARCHAR(120) NULL,
  faculty VARCHAR(120) NULL,
  year_level INT NULL,
  department VARCHAR(120) NULL,
  website VARCHAR(255) NULL,
  linkedin VARCHAR(255) NULL,
  github VARCHAR(255) NULL,
  points INT NOT NULL DEFAULT 0,
  profile_complete INT NOT NULL DEFAULT 0,
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (university_id) REFERENCES universities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_users_student ON users(student_id);

CREATE TABLE user_skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(60) NOT NULL,
  kind VARCHAR(10) NOT NULL DEFAULT 'skill',        
  UNIQUE (user_id, name, kind),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_skill_name ON user_skills(name);

CREATE TABLE follows (
  follower_id INT NOT NULL,
  followed_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (follower_id, followed_id),
  FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'idea',
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  tags VARCHAR(255) NULL,                           
  image VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'visible',    
  pinned INT NOT NULL DEFAULT 0,
  share_of INT NULL,
  project_id INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_posts_created ON posts(created_at);

CREATE INDEX idx_posts_type ON posts(type);

CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  parent_id INT NULL,
  body TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_comments_post ON comments(post_id);

CREATE TABLE reactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  target_type VARCHAR(10) NOT NULL,                 
  target_id INT NOT NULL,
  kind VARCHAR(12) NOT NULL,                        
  created_at DATETIME NOT NULL,
  UNIQUE (user_id, target_type, target_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_reactions_target ON reactions(target_type, target_id);

CREATE TABLE bookmarks (
  user_id INT NOT NULL,
  post_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, post_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  target_type VARCHAR(10) NOT NULL,                 
  target_id INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',       
  created_at DATETIME NOT NULL,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  needed_skills VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',       
  max_members INT NOT NULL DEFAULT 5,
  outcome TEXT NULL,
  hidden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_members (
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR(60) NOT NULL DEFAULT 'Member',
  joined_at DATETIME NOT NULL,
  PRIMARY KEY (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',    
  created_at DATETIME NOT NULL,
  UNIQUE (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  assignee_id INT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'todo',       
  due_date DATE NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  is_update INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'other',
  price INT NOT NULL DEFAULT 0,                     
  delivery_days INT NOT NULL DEFAULT 3,
  status VARCHAR(20) NOT NULL DEFAULT 'active',     
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  buyer_id INT NOT NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',    
  created_at DATETIME NOT NULL,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (service_id, reviewer_id),
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'general',
  level VARCHAR(20) NOT NULL DEFAULT 'beginner',
  theme INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'published',  
  created_at DATETIME NOT NULL,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lessons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  position INT NOT NULL DEFAULT 1,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  video_url VARCHAR(255) NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enrollments (
  course_id INT NOT NULL,
  user_id INT NOT NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (course_id, user_id),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lesson_progress (
  lesson_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (lesson_id, user_id),
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mentor_profiles (
  user_id INT NOT NULL PRIMARY KEY,
  expertise VARCHAR(255) NOT NULL,
  about TEXT NULL,
  availability VARCHAR(255) NULL,
  active INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mentorship_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mentor_id INT NOT NULL,
  student_id INT NOT NULL,
  topic VARCHAR(200) NOT NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',    
  session_at DATETIME NULL,
  feedback TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(20) NOT NULL DEFAULT 'star',
  tone VARCHAR(12) NOT NULL DEFAULT 'green'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, badge_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE point_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  points INT NOT NULL,
  reason VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(30) NOT NULL,
  text VARCHAR(255) NOT NULL,
  link VARCHAR(255) NULL,
  is_read INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_notif_user ON notifications(user_id, is_read);

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  body TEXT NOT NULL,
  is_read INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_messages_pair ON messages(sender_id, receiver_id);

CREATE TABLE settings (
  k VARCHAR(60) NOT NULL PRIMARY KEY,
  v VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NULL,
  action VARCHAR(60) NOT NULL,
  detail VARCHAR(255) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO universities (name, short_name, domain, active, created_at) VALUES ('Al Ain University','AAU','aau.ac.ae',1,'2026-10-02 16:08:51');
INSERT INTO universities (name, short_name, domain, active, created_at) VALUES ('Abu Dhabi University','ADU','adu.ac.ae',0,'2026-10-02 16:08:51');
INSERT INTO universities (name, short_name, domain, active, created_at) VALUES ('United Arab Emirates University','UAEU','uaeu.ac.ae',0,'2026-10-02 16:08:51');
INSERT INTO roster (university_id, student_id, full_name, major, faculty, year_level, created_at) VALUES (1,'202020280','Yaman Mhd Laith AlNasri','Software Engineering','College of Engineering',4,'2026-10-02 16:08:51');
INSERT INTO roster (university_id, student_id, full_name, major, faculty, year_level, created_at) VALUES (1,'202211424','Tamim Ahmed Alzein','Software Engineering','College of Engineering',4,'2026-10-02 16:08:51');
INSERT INTO roster (university_id, student_id, full_name, major, faculty, year_level, created_at) VALUES (1,'202210908','Ghaith Shujaa Alsalim','Software Engineering','College of Engineering',4,'2026-10-02 16:08:51');
INSERT INTO roster (university_id, student_id, full_name, major, faculty, year_level, created_at) VALUES (1,'202212000','Muhammad Toufeeq','Software Engineering','College of Engineering',4,'2026-10-02 16:08:51');
INSERT INTO roster (university_id, student_id, full_name, major, faculty, year_level, created_at) VALUES (1,'202210821','Rami Loay Albaini','Software Engineering','College of Engineering',4,'2026-10-02 16:08:51');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('profile_pro','Profile Pro','Completed your profile','user','green');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('first_post','First Voice','Published your first post','chat','blue');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('idea_machine','Idea Machine','Shared 5 ideas','bulb','amber');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('conversationalist','Conversationalist','Wrote 10 comments','chat','violet');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('team_player','Team Player','Joined a project team','users','teal');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('project_leader','Project Leader','Started a project','flag','rose');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('service_pro','Service Pro','Listed a service in the marketplace','briefcase','orange');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('five_star','Five Star','Received a 5-star review','star','amber');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('learner','Curious Mind','Enrolled in a course','book','blue');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('graduate','Graduate','Completed a course','cap','green');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('mentee','Mentee','Got accepted by a mentor','heart','rose');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('popular','Rising Star','Gained 5 followers','fire','orange');
INSERT INTO badges (code, name, description, icon, tone) VALUES ('centurion','Centurion','Reached 100 points','trophy','amber');
INSERT INTO settings (k, v) VALUES ('registration_open','1');
INSERT INTO settings (k, v) VALUES ('site_notice','');

SET FOREIGN_KEY_CHECKS = 1;
