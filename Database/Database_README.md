# Database Setup Guide

This directory contains all SQL scripts needed to set up the ResearchHub database.

## 📋 Execution Order

Run these SQL files **in the following order**:

### 1. CREATE.sql
**Purpose**: Creates the database structure
- Creates `projectDB3` database
- Creates 14 tables with proper relationships
- Sets up foreign key constraints

**Run:**
```bash
mysql -u root -p < CREATE.sql
```

### 2. INSERT.sql
**Purpose**: Loads sample data for testing
- 10 affiliations (universities)
- 10 users (with hashed passwords)
- 10 sources (journals, conferences)
- 11 authors
- 10 documents (research papers)
- Tags, citations, and relationships


### 3. Create_Grant.sql
**Purpose**: Sets up database users and permissions
- Creates `admin` user with full privileges
- Creates `app_user` with limited permissions
- Grants appropriate access based on roles

**Run:**
```bash
mysql -u root -p < Create_Grant.sql
```


**Run:**
```bash
mysql -u root -p projectDB3 < INSERT.sql
```

**Test Accounts** (all passwords: `password123`):
- Admin: `robert.lee@nyu.edu`
- User: `jessieLi@nyu.edu.cn`

### 4. AdvancedFinal.sql
**Purpose**: Implements advanced database features
- Creates `action_log` table for audit trail
- Creates stored functions for role checking
- Creates BEFORE triggers for permission validation
- Creates AFTER triggers for activity logging

**Run:**
```bash
mysql -u root -p projectDB3 < AdvancedFinal.sql
```

## 🚀 Quick Setup (All at Once)

```bash
# From the database directory
mysql -u root -p < CREATE.sql && \
mysql -u root -p projectDB3 < INSERT.sql && \
mysql -u root -p < Create_Grant.sql && \
mysql -u root -p projectDB3 < AdvancedFinal.sql
```

## 🔑 Database Users Created

| User | Password | Permissions |
|------|----------|-------------|
| `admin` | `admin_password` | Full CRUD on all tables, EXECUTE stored procedures |
| `app_user` | `app_user_password` | Read-only on core tables, Full CRUD on user content |

## 📊 Database Schema Overview

### Core Tables (14 total):
1. **Affiliation** - Institutions
2. **Source** - Publication sources
3. **Tag** - Keywords/topics
4. **User** - Application users
5. **Author** - Paper authors
6. **Document** - Research papers
7. **Collection** - User collections
8. **Notes** - User notes
9. **Comment** - User comments
10. **Citation** - Paper citations
11. **CollectionDocument** - Collection-document mapping
12. **DocumentAuthor** - Document-author mapping
13. **DocumentTag** - Document-tag mapping
14. **action_log** - Audit trail

## ⚠️ Troubleshooting

### Error: "Access denied for user 'admin'@'localhost'"
Make sure you ran `Create_Grant.sql` after `CREATE.sql`.

### Error: "Table 'projectDB3.action_log' doesn't exist"
Run `AdvancedFinal.sql` to create triggers and the action_log table.

### Error: "Cannot add foreign key constraint"
Ensure you ran `CREATE.sql` completely before running other scripts.

## 🔄 Resetting the Database

To completely reset and start fresh:

```bash
mysql -u root -p -e "DROP DATABASE IF EXISTS projectDB3;"
mysql -u root -p -e "DROP USER IF EXISTS 'admin'@'localhost';"
mysql -u root -p -e "DROP USER IF EXISTS 'app_user'@'localhost';"

# Then run setup again
mysql -u root -p < CREATE.sql
mysql -u root -p projectDB3 < INSERT.sql
mysql -u root -p < Create_Grant.sql
mysql -u root -p projectDB3 < AdvancedFinal.sql
```