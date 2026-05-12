# DB Chatbot

AI-powered database chatbot. Connect a MySQL database, chat with it in plain English, and build training data.

## Setup

1. **Enable OpenSSL** in `C:\xampp\php\php.ini` — uncomment `extension=openssl` (usually already active)
2. **Start XAMPP** — Apache + MySQL
3. **Install dependencies:**
   ```
   cd C:\xampp\htdocs\sql-chatbot\server
   composer install
   ```
4. **Configure `.env`** — copy `.env.example` to `.env` and fill in values
5. **Import schema** via phpMyAdmin — import `db_schema.sql`
6. **Open** `http://localhost/sql-chatbot/client/index.html`

## Usage

1. Register / login
2. Create a Project — enter your target MySQL database credentials and Test Connection
3. Chat — ask questions in plain English, Claude generates and executes SQL
4. Training — generate question→SQL pairs, approve/reject, export as JSONL

## Stack

- **Frontend:** AngularJS 1.8, Bootstrap 4.6, highlight.js
- **Backend:** PHP 8.x, PDO, firebase/php-jwt
- **AI:** Anthropic Claude API
- **DB:** MySQL / MariaDB
