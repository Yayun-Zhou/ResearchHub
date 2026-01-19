-- create database projectDB3 in XAMPP
CREATE DATABASE IF NOT EXISTS projectDB3;
USE projectDB3;

-- Disable foreign key checks to avoid issues during table creation
SET FOREIGN_KEY_CHECKS = 0;

-- Create all necessary tables

-- ========================
-- 1. Affiliation
-- ========================
DROP TABLE IF EXISTS Affiliation;
CREATE TABLE Affiliation (
    AffiliationID INT AUTO_INCREMENT PRIMARY KEY,
    AffiliationName VARCHAR(255) NOT NULL
);

-- ========================
-- 2. Source
-- ========================
DROP TABLE IF EXISTS Source;
CREATE TABLE Source (
    SourceID INT AUTO_INCREMENT PRIMARY KEY,
    SourceType VARCHAR(100) NOT NULL,
    SourceName VARCHAR(255) NOT NULL,
    Language VARCHAR(100)
);

-- ========================
-- 3. Tag
-- ========================
DROP TABLE IF EXISTS Tag;
CREATE TABLE Tag (
    TagID INT AUTO_INCREMENT PRIMARY KEY,
    TagName VARCHAR(100) UNIQUE NOT NULL,
    TagDescription TEXT
);

-- ========================
-- 4. User
-- ========================
DROP TABLE IF EXISTS User;
CREATE TABLE User (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    UserName VARCHAR(255) NOT NULL,
    Email VARCHAR(255) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    AffiliationID INT,
    Role VARCHAR(100),
    FOREIGN KEY (AffiliationID) REFERENCES Affiliation(AffiliationID)
);

-- ========================
-- 5. Author
-- ========================
DROP TABLE IF EXISTS Author;
CREATE TABLE Author (
    AuthorID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    AffiliationID INT,
    AuthorArea VARCHAR(255),
    FOREIGN KEY (AffiliationID) REFERENCES Affiliation(AffiliationID)
);

-- ========================
-- 6. Document
-- ========================
DROP TABLE IF EXISTS Document;
CREATE TABLE Document (
    DocumentID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(500) NOT NULL,
    Abstract TEXT,
    PublicationYear YEAR,
    SourceID INT,
    Area VARCHAR(255),
    ISBN VARCHAR(50) UNIQUE,
    LinkPath VARCHAR(500),
    ImportDate DATE DEFAULT CURDATE(),
    ReviewStatus ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (SourceID) REFERENCES Source(SourceID)
);

-- ========================
-- 7. Collection
-- ========================
DROP TABLE IF EXISTS Collection;
CREATE TABLE Collection (
    CollectionID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL, -- FK to User
    CollectionName VARCHAR(255) NOT NULL,
    CollectionDescription TEXT,
    CreatedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Visibility ENUM('Private', 'Public') NOT NULL,
    FOREIGN KEY (UserID) REFERENCES User(UserID)
);

-- ========================
-- 8. Notes
-- ========================
DROP TABLE IF EXISTS Notes;
CREATE TABLE Notes (
    NoteID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL, -- FK to User
    DocumentID INT NOT NULL, -- FK to Document
    Content TEXT NOT NULL,
    PageNum VARCHAR(50),
    CreatedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Visibility ENUM('Private', 'Public') NOT NULL,
    FOREIGN KEY (UserID) REFERENCES User(UserID),
    FOREIGN KEY (DocumentID) REFERENCES Document(DocumentID)
);

-- ========================
-- 9. Comment
-- ========================
DROP TABLE IF EXISTS Comment;
CREATE TABLE Comment (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    DocumentID INT NOT NULL,
    Context TEXT NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES User(UserID),
    FOREIGN KEY (DocumentID) REFERENCES Document(DocumentID)
);

-- ========================
-- 10. Citation
-- ========================
DROP TABLE IF EXISTS Citation;
CREATE TABLE Citation (
    CitationID INT AUTO_INCREMENT PRIMARY KEY,
    CitingDocumentID INT NOT NULL, -- The document that cites
    CitedDocumentID INT NOT NULL,  -- The document that is cited
    ContextPage TEXT,
    DetectedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CitingDocumentID) REFERENCES Document(DocumentID),
    FOREIGN KEY (CitedDocumentID) REFERENCES Document(DocumentID),
    UNIQUE KEY unique_citation_pair (CitingDocumentID, CitedDocumentID)
);

-- ========================
-- 11. CollectionDocument (M:M)
-- ========================
DROP TABLE IF EXISTS CollectionDocument;
CREATE TABLE CollectionDocument (
    CollectionID INT NOT NULL,
    DocumentID INT NOT NULL,
    PRIMARY KEY (CollectionID, DocumentID),
    FOREIGN KEY (CollectionID) REFERENCES Collection(CollectionID),
    FOREIGN KEY (DocumentID) REFERENCES Document(DocumentID)
);

-- ========================
-- 12. DocumentAuthor (M:M)
-- ========================
DROP TABLE IF EXISTS DocumentAuthor;
CREATE TABLE DocumentAuthor (
    DocumentID INT NOT NULL,
    AuthorID INT NOT NULL,
    PRIMARY KEY (DocumentID, AuthorID),
    FOREIGN KEY (DocumentID) REFERENCES Document(DocumentID),
    FOREIGN KEY (AuthorID) REFERENCES Author(AuthorID)
);

-- ========================
-- 13. DocumentTag (M:M)
-- ========================
DROP TABLE IF EXISTS DocumentTag;
CREATE TABLE DocumentTag (
    DocumentID INT NOT NULL,
    TagID INT NOT NULL,
    PRIMARY KEY (DocumentID, TagID),
    FOREIGN KEY (DocumentID) REFERENCES Document(DocumentID),
    FOREIGN KEY (TagID) REFERENCES Tag(TagID)
);

-- Re-enable foreign key checks after table creation
SET FOREIGN_KEY_CHECKS = 1;