CREATE DATABASE IF NOT EXISTS sql_chatbot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sql_chatbot;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  db_host VARCHAR(255),
  db_port INT DEFAULT 3306,
  db_name VARCHAR(100),
  db_user VARCHAR(100),
  db_password_encrypted TEXT,
  is_connected TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  role ENUM('user','bot') NOT NULL,
  message TEXT NOT NULL,
  sql_query TEXT,
  query_result JSON,
  tokens_input INT,
  tokens_output INT,
  response_time_ms INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS training_pairs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  question TEXT NOT NULL,
  sql_query TEXT NOT NULL,
  explanation TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
