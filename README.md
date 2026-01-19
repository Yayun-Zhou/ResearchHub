# ResearchHub

A full-stack web application for managing academic research papers, enabling researchers to collaborate, organize documents, track citations, and build personal collections.

## 🎯 Project Overview

ResearchHub is a database-driven research management platform built as a course project for CS-UY 3083 Introduction to Databases (Fall 2025). The application provides a comprehensive solution for academic researchers to:

- Import and manage research papers with detailed metadata
- Track citation relationships between documents
- Organize papers into personal collections
- Search and filter documents using advanced queries
- Add notes and comments to documents
- Collaborate with other researchers

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript
- **Architecture**: MVC pattern with separated controllers
- **Security**: PDO prepared statements, password hashing, session management

## 📊 Database Schema

The application uses a normalized(3NF) relational database with 14 tables:

### Core Tables
- `Document` - Research papers and publications
- `Author` - Paper authors
- `Source` - Publication sources (journals, conferences)
- `Tag` - Keywords and topics
- `User` - Application users
- `Collection` - User-created paper collections
- `Citation` - Citation relationships between documents
- `Affiliation` - Author institutional affiliations

### Junction Tables
- `DocumentAuthor` - Document-author relationships
- `DocumentTag` - Document-tag associations
- `CollectionDocument` - Collection-document memberships
- `Notes` - User notes on documents
- `Comment` - User comments on documents
- `action_log` - System activity logging

## ✨ Key Features

### 1. Document Management
- **Import Documents**: Admin can import research papers with metadata (title, abstract, authors, year, ISBN, etc.)
- **Edit & Delete**: Full CRUD operations on documents
- **Review System**: Three-state approval workflow (Pending/Approved/Rejected)
- **Citation Tracking**: Track which papers cite which papers

### 2. Search & Discovery
- **Advanced Search**: Multi-parameter filtering by title, author, tags, and year range
- **Relevance Scoring**: Weighted algorithm ranking results (Title×3, Abstract×2, Author×2, Tags×1)
- **Faceted Browsing**: Filter by publication source, research area, and status

### 3. User Features
- **Personal Collections**: Organize documents into custom collections(private/public)
- **Notes & Comments**: Add private/public notes and public comments
- **Activity Dashboard**: View statistics and recent activity
- **Profile Management**: Update profile information and password

### 4. Role-Based Access
- **Admin**: Import documents, review submissions, manage users and other user functions
- **User**: Search documents, create collections, add notes/comments

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server (or XAMPP for local development)

### Setup Steps

1. **Clone the repository**
```bash
git clone https://github.com/Yayun-Zhou/ResearchHub.git
cd ResearchHub
```

2. **Sample Database Setup**

Run the SQL files in this order:
```bash
# Step 1: Create database and tables
mysql -u root -p < database/CREATE.sql

# Step 2: Insert sample data for testing
mysql -u root -p projectDB3 < database/INSERT.sql

# Step 3: Create database users with appropriate permissions
mysql -u root -p < database/Create_Grant.sql

# Step 4: Create triggers and stored procedures for advanced features
mysql -u root -p projectDB3 < database/AdvancedFinal.sql
```

Or run all at once:
```bash
mysql -u root -p < database/CREATE.sql && \
mysql -u root -p projectDB3 < database/INSERT.sql && \
mysql -u root -p < database/Create_Grant.sql && \
mysql -u root -p projectDB3 < database/AdvancedFinal.sql
```

Or copy and paste the contents of each SQL file into a MySQL client like phpMyAdmin.
Copy and paste the SQL files in this order:
CREATE.sql; INSERT.sql; Create_Grant.sql; AdvancedFinal.sql

3. **Configure Database Connection**

The default configuration in `includes/connect.php` uses:
- Database: `projectDB3`
- Admin user: `admin` / `admin_password`
- App user: `app_user` / `app_user_password`

If you need to change these, edit `includes/connect.php`:
```php
$host   = "localhost";
$dbName = "projectDB3";
// Users are automatically selected based on role
```

4. **Configure Web Server**

For Apache, point document root to the ResearchHub directory, or place in `htdocs` for XAMPP.

5. **Access the Application**
Local Access (recommended):
```
http://localhost/ResearchHub
```

Remote Access (for demonstration purposes only):
```
https://hypernormal-nontopographical-nathaniel.ngrok-free.dev/ResearchHub/
```

To access the application via the ngrok URL, the author must:
1. Start the local Apache/PHP server
2. Expose the local port using ngrok in the terminal - ngrok http 80
3. Keep the terminal session running

### Default Login Credentials

If you loaded the sample data (`INSERT.sql`), you can use these accounts:

**Admin Account:**
- Email: `robert.lee@nyu.edu`
- Password: `rl@Nyu2024`
- Role: Admin (can import and review documents)

**Regular User Account:**
- Email: `jessieLi@nyu.edu.cn`
- Password: `sLu2025!`
- Role: Student (can search, comment, create collections)

## 📁 Project Structure

```
ResearchHub/
├── database/
│   ├── CREATE.sql           # Database and table creation
│   ├── Create_Grant.sql     # User permissions setup
│   ├── INSERT.sql           # Sample data (optional)
│   └── AdvancedFinal.sql    # Triggers and stored procedures
├── assets/
│   ├── css/                 # Stylesheets
│       └── globals.css
├── controllers/             # Backend logic controllers
│   ├── login_handler.php
│   ├── signup_handler.php
│   ├── submit_document.php
│   ├── add_comment.php
│   ├── add_note.php
│   ├── create_collection.php
│   └── ...
├── includes/
│   └── connect.php          # Database connection
├── dashboard.php            # Main dashboard
├── search.php               # Basic search interface
├── advanced_search.php      # Advanced search with filters
├── document_view.php        # Document detail view
├── import_document.php      # Admin: import documents
├── review_documents.php     # Admin: review submissions
├── collections.php          # View user collections
├── collection_view.php      # View single collection
├── notes.php                # View user notes
├── comments.php             # View user comments
├── user_account.php         # User profile
├── login.php
├── signup.php
├── logout.php
└── README.md
```

## 🔒 Security Features

- **SQL Injection Prevention**: Parameterized queries using PDO prepared statements
- **Password Security**: PHP `password_hash()` with bcrypt
- **Session Management**: Secure session cookies with httponly and samesite flags
- **Role-Based Access Control**: Separate database users for Admin and User roles with granular permissions
- **Input Validation**: Server-side validation and sanitization
- **Ownership Enforcement**: Database triggers prevent users from modifying others' content

## 🎯 Advanced Database Features

### Triggers & Stored Procedures
The application uses database-level enforcement for business logic:

- **Ownership Validation**: BEFORE triggers prevent users from updating/deleting others' comments, notes, and collections
- **Activity Logging**: AFTER triggers automatically log all INSERT/UPDATE/DELETE operations to `action_log` table
- **Permission Checking**: Stored functions verify user roles before allowing operations
- **Data Integrity**: Triggers ensure referential integrity and enforce business rules

### Role-Based Permissions (at Database Level)
```sql
-- Admin: Full CRUD on all tables
-- User: Read-only on core tables (Document, Author, Source, Tag)
--       Full CRUD on own content (Notes, Comments, Collections)
```

### Audit Trail
Every user action is logged with:
- UserID and Role
- Table and Action Type (INSERT/UPDATE/DELETE)
- Target record ID
- Timestamp
- Additional context information

## 🎓 Learning Outcomes

This project demonstrates:
- Relational database design with normalization
- Complex SQL queries with JOINs, aggregations, and subqueries
- MVC architecture pattern implementation
- Secure web application development practices
- User authentication and authorization
- CRUD operations and transaction management

## 📈 Future Enhancements

- [ ] Export citations in BibTeX/EndNote format
- [ ] Full-text PDF upload and storage
- [ ] Email notifications for new documents
- [ ] Collaborative collections with sharing
- [ ] API endpoints for external integrations
- [ ] Advanced analytics dashboard
- [ ] Docker containerization

## 👥 Authors
- **Yayun Zhou** - *New York University Shanghai* - [GitHub](https://github.com/Yayun-Zhou)
- **Feiying Huang** - *New York University Shanghai*
- **Huixuan Liu** - *New York University Shanghai*

## 📝 License

This project was created as a course assignment for educational purposes.

## 🙏 Acknowledgments

- Database Systems Course - New York University
- Course Instructor: Salim Arfaoui
- PHP and MySQL documentation

---

**Note**: This is an educational project. For production use, additional security hardening, error handling, and testing would be required.