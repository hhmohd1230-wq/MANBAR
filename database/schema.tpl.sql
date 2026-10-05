-- MANBAR database schema (template).  {{PK}} and {{ENGINE}} are replaced by database/build.php
-- Portable on purpose: no ENUMs, no DB-specific defaults. Timestamps are written by the app.

CREATE TABLE universities (
  id {{PK}},
  name VARCHAR(150) NOT NULL,
  short_name VARCHAR(20) NOT NULL,
  domain VARCHAR(120) NOT NULL UNIQUE,
  active INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) {{ENGINE}};

CREATE TABLE roster (
  id {{PK}},
  university_id INT NOT NULL,
  student_id VARCHAR(20) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  major VARCHAR(120) NULL,
  faculty VARCHAR(120) NULL,
  year_level INT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (university_id, student_id),
  FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE users (
  id {{PK}},
  university_id INT NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  google_sub VARCHAR(64) NULL,
  student_id VARCHAR(20) NULL,
  full_name VARCHAR(150) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'student',      -- student | teacher | admin
  status VARCHAR(20) NOT NULL DEFAULT 'active',     -- active | suspended
  verified INT NOT NULL DEFAULT 0,                  -- 1 = found in university roster / approved by admin
  avatar_url VARCHAR(500) NULL,
  theme INT NOT NULL DEFAULT 0,                     -- cover gradient index
  headline VARCHAR(160) NULL,
  bio TEXT NULL,
  major VARCHAR(120) NULL,
  faculty VARCHAR(120) NULL,
  year_level INT NULL,
  department VARCHAR(120) NULL,
  website VARCHAR(255) NULL,
  linkedin VARCHAR(255) NULL,
  github VARCHAR(255) NULL,
  discord VARCHAR(255) NULL,
  whatsapp VARCHAR(32) NULL,
  phone VARCHAR(32) NULL,
  cover_image VARCHAR(255) NULL,
  name_style VARCHAR(20) NOT NULL DEFAULT 'classic',
  profile_effect VARCHAR(20) NOT NULL DEFAULT 'none',
  points INT NOT NULL DEFAULT 0,
  profile_complete INT NOT NULL DEFAULT 0,
  product_tour_completed INT NOT NULL DEFAULT 0,
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (university_id) REFERENCES universities(id)
) {{ENGINE}};
CREATE INDEX idx_users_student ON users(student_id);

CREATE TABLE user_skills (
  id {{PK}},
  user_id INT NOT NULL,
  name VARCHAR(60) NOT NULL,
  kind VARCHAR(10) NOT NULL DEFAULT 'skill',        -- skill | interest
  UNIQUE (user_id, name, kind),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};
CREATE INDEX idx_skill_name ON user_skills(name);

CREATE TABLE follows (
  follower_id INT NOT NULL,
  followed_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (follower_id, followed_id),
  FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE posts (
  id {{PK}},
  user_id INT NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'idea',
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  tags VARCHAR(255) NULL,                           -- comma separated, lower-case
  image VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'visible',    -- visible | hidden
  pinned INT NOT NULL DEFAULT 0,
  share_of INT NULL,
  project_id INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};
CREATE INDEX idx_posts_created ON posts(created_at);
CREATE INDEX idx_posts_type ON posts(type);

CREATE TABLE comments (
  id {{PK}},
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  parent_id INT NULL,
  body TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};
CREATE INDEX idx_comments_post ON comments(post_id);

CREATE TABLE reactions (
  id {{PK}},
  user_id INT NOT NULL,
  target_type VARCHAR(10) NOT NULL,                 -- post | comment
  target_id INT NOT NULL,
  kind VARCHAR(12) NOT NULL,                        -- like | love | insight | celebrate | support
  created_at DATETIME NOT NULL,
  UNIQUE (user_id, target_type, target_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};
CREATE INDEX idx_reactions_target ON reactions(target_type, target_id);

CREATE TABLE bookmarks (
  user_id INT NOT NULL,
  post_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, post_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE reports (
  id {{PK}},
  reporter_id INT NOT NULL,
  target_type VARCHAR(10) NOT NULL,                 -- post | comment | service | project
  target_id INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',       -- open | resolved | dismissed
  created_at DATETIME NOT NULL,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE projects (
  id {{PK}},
  owner_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  needed_skills VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',       -- open | in_progress | completed | closed
  max_members INT NOT NULL DEFAULT 5,
  outcome TEXT NULL,
  cover_image VARCHAR(255) NULL,
  cover_theme INT NOT NULL DEFAULT 0,
  hidden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE project_members (
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  role VARCHAR(60) NOT NULL DEFAULT 'Member',
  joined_at DATETIME NOT NULL,
  PRIMARY KEY (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE project_applications (
  id {{PK}},
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',    -- pending | accepted | rejected
  created_at DATETIME NOT NULL,
  UNIQUE (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE project_tasks (
  id {{PK}},
  project_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  assignee_id INT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'todo',       -- todo | doing | done
  due_date DATE NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE project_messages (
  id {{PK}},
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  is_update INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE services (
  id {{PK}},
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'other',
  price INT NOT NULL DEFAULT 0,                     -- AED, 0 = free / skill swap
  delivery_days INT NOT NULL DEFAULT 3,
  status VARCHAR(20) NOT NULL DEFAULT 'active',     -- active | paused | hidden
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE service_requests (
  id {{PK}},
  service_id INT NOT NULL,
  buyer_id INT NOT NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',    -- pending | accepted | declined | completed
  created_at DATETIME NOT NULL,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE service_reviews (
  id {{PK}},
  service_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  rating INT NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (service_id, reviewer_id),
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE courses (
  id {{PK}},
  author_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'general',
  level VARCHAR(20) NOT NULL DEFAULT 'beginner',
  theme INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'published',  -- published | hidden
  created_at DATETIME NOT NULL,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE lessons (
  id {{PK}},
  course_id INT NOT NULL,
  position INT NOT NULL DEFAULT 1,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  video_url VARCHAR(255) NULL,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE enrollments (
  course_id INT NOT NULL,
  user_id INT NOT NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (course_id, user_id),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE lesson_progress (
  lesson_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (lesson_id, user_id),
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE mentor_profiles (
  user_id INT NOT NULL PRIMARY KEY,
  expertise VARCHAR(255) NOT NULL,
  about TEXT NULL,
  availability VARCHAR(255) NULL,
  active INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE mentorship_requests (
  id {{PK}},
  mentor_id INT NOT NULL,
  student_id INT NOT NULL,
  topic VARCHAR(200) NOT NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',    -- pending | accepted | declined | completed
  session_at DATETIME NULL,
  feedback TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE badges (
  id {{PK}},
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(20) NOT NULL DEFAULT 'star',
  tone VARCHAR(12) NOT NULL DEFAULT 'green'
) {{ENGINE}};

CREATE TABLE user_badges (
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, badge_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE point_log (
  id {{PK}},
  user_id INT NOT NULL,
  points INT NOT NULL,
  reason VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE notifications (
  id {{PK}},
  user_id INT NOT NULL,
  type VARCHAR(30) NOT NULL,
  text VARCHAR(255) NOT NULL,
  link VARCHAR(255) NULL,
  is_read INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};
CREATE INDEX idx_notif_user ON notifications(user_id, is_read);

CREATE TABLE messages (
  id {{PK}},
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  body TEXT NOT NULL,
  attachment_path VARCHAR(500) NULL,
  attachment_name VARCHAR(255) NULL,
  attachment_type VARCHAR(100) NULL,
  attachment_size INT NULL,
  is_read INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};
CREATE INDEX idx_messages_pair ON messages(sender_id, receiver_id);

CREATE TABLE message_typing (
  user_id INT NOT NULL,
  receiver_id INT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, receiver_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) {{ENGINE}};

CREATE TABLE settings (
  k VARCHAR(60) NOT NULL PRIMARY KEY,
  v VARCHAR(500) NULL
) {{ENGINE}};

CREATE TABLE audit_log (
  id {{PK}},
  admin_id INT NULL,
  action VARCHAR(60) NOT NULL,
  detail VARCHAR(255) NULL,
  created_at DATETIME NOT NULL
) {{ENGINE}};
