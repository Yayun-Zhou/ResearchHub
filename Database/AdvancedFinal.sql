-- Advanced SQL: Triggers & Procedures

-- We designed a role-based document management system where only Admins can modify the core document database, while all users can interact with documents through collections, notes, comments
-- Permissions, integrity, and review flow are enforced entirely at the database level via stored procedures and triggers, with full audit logging.

-- 1) User Interaction
-- Triggers: User can only update/delete Comments/Notes/Collections/CollectionDocuments created by themselves;
-- Before Triggers prevent non-owners from modifying 
-- After Triggers write into action_log 

-- action_log
CREATE TABLE IF NOT EXISTS action_log (
  LogID       INT AUTO_INCREMENT PRIMARY KEY, -- for every log --
  UserID      INT NOT NULL, -- recording the user --
  Role    VARCHAR(20) NOT NULL, -- recording the user's role --
  TableName   VARCHAR(50) NOT NULL, -- recording the table that the action acts on --
  ActionType  VARCHAR(10) NOT NULL, -- INSERT/UPDATE/DELETE --
  TargetID    INT NOT NULL, -- target's PK in table --
  ActionTime  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ExtraInfo   TEXT NULL
);

-- user_role inquiry by user_id
DROP FUNCTION IF EXISTS fn_role_by_user_id;
DELIMITER $$
CREATE FUNCTION fn_role_by_user_id(p_user_id INT)
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_role VARCHAR(20);
  SELECT Role INTO v_role FROM User WHERE UserID = p_user_id;
  RETURN v_role;
END$$
DELIMITER ;

-- Comments
-- BEFORE UPDATE
DROP TRIGGER IF EXISTS trg_comment_bu_guard;
DELIMITER $$
CREATE TRIGGER trg_comment_bu_guard
BEFORE UPDATE ON Comment
FOR EACH ROW
BEGIN
  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;
  IF OLD.UserID <> @current_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot update others comments';
  END IF;
END$$
DELIMITER ;

-- BEFORE DELETE
DROP TRIGGER IF EXISTS trg_collection_bd_guard;
DELIMITER $$

CREATE TRIGGER trg_collection_bd_guard
BEFORE DELETE ON `Collection`
FOR EACH ROW
BEGIN
    -- 1. Must have current user set
    IF @current_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'current user not set';
    END IF;

    -- 2. Only owner or Admin can delete
    IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci
       AND OLD.UserID <> @current_user_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only owner or Admin can delete collection';
    END IF;
END$$

DELIMITER ;

-- AFTER INSERT/UPDATE/DELETE：action_log
DROP TRIGGER IF EXISTS trg_comment_ai;
DELIMITER $$
CREATE TRIGGER trg_comment_ai
AFTER INSERT ON Comment
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID, ExtraInfo)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Comment', 'INSERT', NEW.CommentID, CONCAT('DocumentID=', NEW.DocumentID)
  );
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_comment_au;
DELIMITER $$
CREATE TRIGGER trg_comment_au
AFTER UPDATE ON Comment
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Comment', 'UPDATE', NEW.CommentID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_comment_ad;
DELIMITER $$
CREATE TRIGGER trg_comment_ad
AFTER DELETE ON Comment
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Comment', 'DELETE', OLD.CommentID);
END$$
DELIMITER ;

-- Notes
-- BEFORE UPDATE
DROP TRIGGER IF EXISTS trg_notes_bu_guard;
DELIMITER $$
CREATE TRIGGER trg_notes_bu_guard
BEFORE UPDATE ON Notes
FOR EACH ROW
BEGIN
  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;
  IF OLD.UserID <> @current_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot update others notes';
  END IF;
END$$
DELIMITER ;


-- BEFORE DELETE Notes
DROP TRIGGER IF EXISTS trg_notes_bd_guard;
DELIMITER $$
CREATE TRIGGER trg_notes_bd_guard
BEFORE DELETE ON Notes
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(20);

    -- 1. Must have current user set
    IF @current_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'current user not set';
    END IF;

    -- 2. Get role of current user
    SELECT Role INTO v_role
    FROM User
    WHERE UserID = @current_user_id;

    -- 3. Admin can delete anyone's notes
    IF v_role <> 'Admin' COLLATE utf8mb4_general_ci AND OLD.UserID <> @current_user_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete others notes';
    END IF;

END$$
DELIMITER ;

DELIMITER ;

-- AFTER INSERT/UPDATE/DELETE：action_log
DROP TRIGGER IF EXISTS trg_notes_ai;
DELIMITER $$
CREATE TRIGGER trg_notes_ai
AFTER INSERT ON Notes
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID, ExtraInfo)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Notes', 'INSERT', NEW.NoteID,
    CONCAT('DocumentID=', NEW.DocumentID, '; Visibility=', QUOTE(NEW.Visibility))
  );
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_notes_au;
DELIMITER $$
CREATE TRIGGER trg_notes_au
AFTER UPDATE ON Notes
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Notes','UPDATE', NEW.NoteID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_notes_ad;
DELIMITER $$
CREATE TRIGGER trg_notes_ad
AFTER DELETE ON Notes
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Notes','DELETE', OLD.NoteID);
END$$
DELIMITER ;

-- Collection
-- BEFORE UPDATE
DROP TRIGGER IF EXISTS trg_collection_bu_guard;
DELIMITER $$
CREATE TRIGGER trg_collection_bu_guard
BEFORE UPDATE ON `Collection`
FOR EACH ROW
BEGIN
  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;
  IF OLD.UserID <> @current_user_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only owner can update collection';
  END IF;
END$$
DELIMITER ;

-- BEFORE DELETE
DROP TRIGGER IF EXISTS trg_collection_bd_guard;
DELIMITER $$
CREATE TRIGGER trg_collection_bd_guard
BEFORE DELETE ON `Collection`
FOR EACH ROW
BEGIN
  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;
  IF OLD.UserID <> @current_user_id AND fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only owner or admin can delete collection';
  END IF; 
END$$
DELIMITER ;

-- AFTER INSERT/UPDATE/DELETE：action_log
DROP TRIGGER IF EXISTS trg_collection_ai;
DELIMITER $$
CREATE TRIGGER trg_collection_ai
AFTER INSERT ON `Collection`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID, ExtraInfo)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Collection','INSERT', NEW.CollectionID,
    CONCAT('Visibility=', QUOTE(NEW.Visibility))
  );
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_collection_au;
DELIMITER $$
CREATE TRIGGER trg_collection_au
AFTER UPDATE ON `Collection`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Collection', 'UPDATE', NEW.CollectionID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_collection_ad;
DELIMITER $$
CREATE TRIGGER trg_collection_ad
AFTER DELETE ON `Collection`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Collection', 'DELETE', OLD.CollectionID);
END$$
DELIMITER ;

-- CollectionDocument: Only owners/admin can add/delete documents in collections
-- BEFORE
DROP TRIGGER IF EXISTS trg_collectiondoc_bi_guard;
DELIMITER $$
CREATE TRIGGER trg_collectiondoc_bi_guard
BEFORE INSERT ON `CollectionDocument`
FOR EACH ROW
BEGIN
  DECLARE v_owner INT;

  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;

  SELECT UserID INTO v_owner
  FROM `Collection`
  WHERE CollectionID = NEW.CollectionID;

  IF v_owner IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Collection not found';
  END IF;
  -- owner/admin check
  IF v_owner <> @current_user_id AND fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only owner or admin can add documents to collection';
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_collectiondoc_bd_guard;
DELIMITER $$
CREATE TRIGGER trg_collectiondoc_bd_guard
BEFORE DELETE ON `CollectionDocument`
FOR EACH ROW
BEGIN
  DECLARE v_owner INT;

  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;

  SELECT UserID INTO v_owner
  FROM `Collection`
  WHERE CollectionID = OLD.CollectionID;

  IF v_owner IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Collection not found';
  END IF;

  IF v_owner <> @current_user_id AND fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only owner or admin can remove documents from collection';
  END IF;
END$$
DELIMITER ;

-- AFTER：action_log
DROP TRIGGER IF EXISTS trg_collectiondoc_ai;
DELIMITER $$
CREATE TRIGGER trg_collectiondoc_ai
AFTER INSERT ON `CollectionDocument`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (
    @current_user_id,
    fn_role_by_user_id(@current_user_id),
    'CollectionDocument',
    'INSERT',
    NEW.CollectionID
  );
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_collectiondoc_ad;
DELIMITER $$
CREATE TRIGGER trg_collectiondoc_ad
AFTER DELETE ON `CollectionDocument`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (
    @current_user_id,
    fn_role_by_user_id(@current_user_id),
    'CollectionDocument',
    'DELETE',
    OLD.CollectionID
  );
END$$
DELIMITER ;

-- Source:

-- 2) Documents
-- Only Admins can insert/update/delete documents

-- BEFORE INSERT: 
-- Non-advanced part automatically sets Document - ReviewStatus as 'pending'
DROP TRIGGER IF EXISTS trg_document_bi_guard;
DELIMITER $$
CREATE TRIGGER trg_document_bi_guard
BEFORE INSERT ON `Document`
FOR EACH ROW
BEGIN
  DECLARE v_role VARCHAR(20);

  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;

  SELECT Role INTO v_role
  FROM `User`
  WHERE UserID = @current_user_id;

  IF v_role IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'user not found';
  END IF;

  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can insert Document';
  END IF;
END$$
DELIMITER ;

-- BEFORE UPDATE
DROP TRIGGER IF EXISTS trg_document_bu_guard;
DELIMITER $$
CREATE TRIGGER trg_document_bu_guard
BEFORE UPDATE ON `Document`
FOR EACH ROW
BEGIN
  DECLARE v_role VARCHAR(20);

  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;

  SELECT Role INTO v_role FROM `User` WHERE UserID = @current_user_id;
  IF v_role IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'user not found';
  END IF;

  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can update Document';
  END IF;
END$$
DELIMITER ;

-- BEFORE DELETE
DROP TRIGGER IF EXISTS trg_document_bd_guard;
DELIMITER $$
CREATE TRIGGER trg_document_bd_guard
BEFORE DELETE ON `Document`
FOR EACH ROW
BEGIN
  DECLARE v_role VARCHAR(20);

  IF @current_user_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current user not set';
  END IF;

  SELECT Role INTO v_role FROM `User` WHERE UserID = @current_user_id;
  IF v_role IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'user not found';
  END IF;

  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can delete Document';
  END IF;
END$$
DELIMITER ;

-- AFTER INSERT/UPDATE/DELETE: action_log
DROP TRIGGER IF EXISTS trg_document_ai_log;
DELIMITER $$
CREATE TRIGGER trg_document_ai_log
AFTER INSERT ON `Document`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (
    @current_user_id,
    (SELECT Role FROM `User` WHERE UserID = @current_user_id),
    'Document', 'INSERT', NEW.DocumentID
  );
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_document_au_log;
DELIMITER $$
CREATE TRIGGER trg_document_au_log
AFTER UPDATE ON `Document`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID, ExtraInfo)
  VALUES (
    @current_user_id,
    (SELECT Role FROM `User` WHERE UserID = @current_user_id),
    'Document', 'UPDATE', NEW.DocumentID,
    IFNULL(
      IF(OLD.ReviewStatus <> NEW.ReviewStatus,
         CONCAT('ReviewStatus:', OLD.ReviewStatus, '->', NEW.ReviewStatus),
         NULL),
      NULL
    )
  );
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_document_ad_log;
DELIMITER $$
CREATE TRIGGER trg_document_ad_log
AFTER DELETE ON `Document`
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID, ExtraInfo)
  VALUES (
    @current_user_id,
    (SELECT Role FROM `User` WHERE UserID = @current_user_id),
    'Document', 'DELETE', OLD.DocumentID,
    CONCAT('Title=', QUOTE(OLD.Title))
  );
END$$
DELIMITER ;

-- 3) Add New Document: Only Admins can add new documents
-- Admins should fill up the attributes of Document, as well as associative information
-- Stored Procedure would automatically insert into Document / other associative entities
-- Associative information is stored uniquely; if anything new, Stored Procedure would insert (get-or-create)
-- Citation is only allowed between existing documents
-- action_log records all Document I/U/D and REVIEW via procedure

DROP PROCEDURE IF EXISTS sp_Admin_upload_document_with_relations_csv;
DELIMITER $$

CREATE PROCEDURE sp_Admin_upload_document_with_relations_csv(
  IN p_Admin_user_id      INT,

  -- Document attributes
  IN p_title                VARCHAR(255),
  IN p_abstract             TEXT,
  IN p_publication_year     INT,
  IN p_area                 VARCHAR(255),
  IN p_isbn                 VARCHAR(64),
  IN p_linkpath             VARCHAR(512),

  -- Source
  IN p_source_name          VARCHAR(255),

  -- Authors CSV: 'First|Last|Aff,First|Last|Aff,...'
  IN p_authors_csv          TEXT,

  -- Tags CSV: 'AI,NLP,Computing'
  IN p_tags_csv             TEXT,

  -- Citations CSV: '101,205,318', existing document ids
  IN p_citations_id_csv     TEXT
)

SQL SECURITY DEFINER
BEGIN
  DECLARE v_role VARCHAR(20);
  DECLARE v_document_id INT;
  DECLARE v_source_id   INT;

  -- Author parsing
  DECLARE v_token    TEXT;
  DECLARE v_first    VARCHAR(128);
  DECLARE v_last     VARCHAR(128);
  DECLARE v_affname  VARCHAR(255);
  DECLARE v_affid    INT;
  DECLARE v_author_id INT;

  -- Tags parsing
  DECLARE v_tag_token     TEXT;
  DECLARE v_tag_id        INT;

  -- Citation parsing
  DECLARE v_cit_token TEXT;
  DECLARE v_cited_id  INT;

  -- Error Handling
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  -- 1 Access: Must be Admin
  SELECT Role INTO v_role FROM `User` WHERE UserID = p_Admin_user_id;
  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can upload Document';
  END IF;

  START TRANSACTION;

  SET @current_user_id = p_Admin_user_id;

  -- 2 Source get-or-create: if not existing, insert
  INSERT INTO Source(SourceName)
  VALUES (TRIM(p_source_name))
  ON DUPLICATE KEY UPDATE SourceID = LAST_INSERT_ID(SourceID);
  SET v_source_id = LAST_INSERT_ID();

  -- 3 Insert Document
  -- ReviewStatus default = 'pending' (Non-advanced)
  INSERT INTO Document(
      Title, Abstract, PublicationYear, SourceID,
      Area, ISBN, LinkPath
  )
  VALUES (
      TRIM(p_title), p_abstract, p_publication_year, v_source_id,
      TRIM(p_area), NULLIF(TRIM(p_isbn),''), TRIM(p_linkpath)
  );
  SET v_document_id = LAST_INSERT_ID();

  -- 4 Author Parsing
  SET p_authors_csv = TRIM(BOTH ' ' FROM IFNULL(p_authors_csv, ''));

  -- Looping through each author
  WHILE p_authors_csv <> '' DO
    -- Take the next token (First|Last|Aff)
    SET v_token = TRIM(SUBSTRING_INDEX(p_authors_csv, ',', 1));
    -- Remove current token
    IF p_authors_csv = v_token THEN
      SET p_authors_csv = '';
    -- Reaching the last token
    ELSE
      SET p_authors_csv = SUBSTRING(p_authors_csv, CHAR_LENGTH(v_token) + 2);
    END IF;

    -- Split First|Last|Aff
    SET v_first   = TRIM(SUBSTRING_INDEX(v_token, '|', 1));
    SET v_last    = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(v_token, '|', 2), '|', -1));
    SET v_affname = TRIM(SUBSTRING_INDEX(v_token, '|', -1));

    IF v_first = '' OR v_last = '' OR v_affname = '' THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Author token must be First|Last|Affiliation';
    END IF;

    -- Affiliation get-or-create
    INSERT INTO Affiliation(AffiliationName)
    VALUES (v_affname)
    ON DUPLICATE KEY UPDATE AffiliationID = LAST_INSERT_ID(AffiliationID);
    SET v_affid = LAST_INSERT_ID();

    -- Author get-or-create
    INSERT INTO Author(FirstName, LastName, AffiliationID)
    VALUES (v_first, v_last, v_affid)
    ON DUPLICATE KEY UPDATE AuthorID = LAST_INSERT_ID(AuthorID);
    SET v_author_id = LAST_INSERT_ID();

    -- Link DocumentAuthor (ignore duplicates)
    INSERT IGNORE INTO DocumentAuthor(DocumentID, AuthorID)
    VALUES (v_document_id, v_author_id);
  END WHILE;

  -- 5 Tag Parsing
  SET p_tags_csv = TRIM(BOTH ' ' FROM IFNULL(p_tags_csv, ''));

  -- Looping through each tag
  WHILE p_tags_csv <> '' DO
    -- Take the next tag
    SET v_token = TRIM(SUBSTRING_INDEX(p_tags_csv, ',', 1));
    -- Remove current tag
    IF p_tags_csv = v_token THEN
      SET p_tags_csv = '';
    -- Reaching the last tag
    ELSE
      SET p_tags_csv = SUBSTRING(p_tags_csv, CHAR_LENGTH(v_token) + 2);
    END IF;

    IF v_token <> '' THEN
      -- Tag get-or-create
      INSERT INTO Tag(TagName)
      VALUES (v_token)
      ON DUPLICATE KEY UPDATE TagID = LAST_INSERT_ID(TagID);

      -- Link DocumentTag
      INSERT IGNORE INTO DocumentTag(DocumentID, TagID)
      SELECT v_document_id, TagID FROM Tag WHERE TagName = v_token;
    END IF;
  END WHILE;

  -- 6 Citation Parsing
  SET p_citations_id_csv = TRIM(BOTH ' ' FROM IFNULL(p_citations_id_csv, ''));

  -- Looping through each citation
  WHILE p_citations_id_csv <> '' DO
    -- Take the next citation
    SET v_cit_token = TRIM(SUBSTRING_INDEX(p_citations_id_csv, ',', 1));
    -- Remove current citation
    IF p_citations_id_csv = v_cit_token THEN
      SET p_citations_id_csv = '';
    -- Reaching the last citation
    ELSE
      SET p_citations_id_csv = SUBSTRING(p_citations_id_csv, CHAR_LENGTH(v_cit_token) + 2);
    END IF;

    IF v_cit_token <> '' THEN
      SET v_cited_id = CAST(v_cit_token AS UNSIGNED);

      IF NOT EXISTS (SELECT 1 FROM Document WHERE DocumentID = v_cited_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cited DocumentID not found';
      END IF;

      IF v_cited_id = v_document_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document cannot cite itself';
      END IF;

      INSERT IGNORE INTO Citation(DocumentID, CitedDocumentID)
      VALUES (v_document_id, v_cited_id);
    END IF;
  END WHILE;

  COMMIT;
END$$
DELIMITER ;

-- 4) Document - ReviewStatus
-- Admin approves documents based on non-duplicates: 
-- No same title in approved documents

DROP PROCEDURE IF EXISTS sp_review_document;
DELIMITER $$

CREATE PROCEDURE sp_review_document(
  IN p_Admin_user_id INT,
  IN p_document_id     INT,
  IN p_decision        VARCHAR(16)   -- 'approved'/'rejected' (default: 'pending')        
)
SQL SECURITY DEFINER
BEGIN
  DECLARE v_role  VARCHAR(20);
  DECLARE v_title VARCHAR(255);

  -- Error Handling: rollback and rethrow 
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  -- Access: Admin
  SELECT Role INTO v_role FROM `User` WHERE UserID = p_Admin_user_id;
  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can review Document';
  END IF;

  -- Normalizing desicion
  SET p_decision = LOWER(TRIM(p_decision));
  IF p_decision NOT IN ('approved','rejected') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Decision must be approved or rejected';
  END IF;

  START TRANSACTION;

  SET @current_user_id = p_Admin_user_id;

  -- Load & lock the target document
  SELECT Title
    INTO v_title
    FROM Document
   WHERE DocumentID = p_document_id
   FOR UPDATE; -- row lock
  IF v_title IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document not found';
  END IF;

  -- Approve Role: no duplicate title in approved documents
  IF p_decision = 'approved' THEN
    IF EXISTS (
      SELECT 1
        FROM Document
       WHERE Title = v_title
         AND ReviewStatus = 'approved'
         AND DocumentID <> p_document_id
    ) THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Duplicate title exists among approved documents';
    END IF;
  END IF;

  -- Update ReviewStatus
  UPDATE Document
     SET ReviewStatus = p_decision
   WHERE DocumentID   = p_document_id;

  COMMIT;
END$$
DELIMITER ;

-- 5) Source & Tag 
-- After inserting SourceName/TagName in the process of inserting document, 
-- Admin needs to upload the following information

-- Triggers: Source/Tag can only be modified by Admins

-- Source
-- BEFORE INSERT
DROP TRIGGER IF EXISTS trg_source_bi_guard;
DELIMITER $$
CREATE TRIGGER trg_source_bi_guard
BEFORE INSERT ON Source
FOR EACH ROW
BEGIN
  IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can insert Source';
  END IF;
END$$
DELIMITER ;

-- BEFORE UPDATE
DROP TRIGGER IF EXISTS trg_source_bu_guard;
DELIMITER $$
CREATE TRIGGER trg_source_bu_guard
BEFORE UPDATE ON Source
FOR EACH ROW
BEGIN
  IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can update Source';
  END IF;
END$$
DELIMITER ;

-- BEFORE DELETE
DROP TRIGGER IF EXISTS trg_source_bd_guard;
DELIMITER $$
CREATE TRIGGER trg_source_bd_guard
BEFORE DELETE ON Source
FOR EACH ROW
BEGIN
  IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can delete Source';
  END IF;
END$$
DELIMITER ;

-- AFTER INSERT/UPDATE/DELETE: action_log
DROP TRIGGER IF EXISTS trg_source_ai_log;
DELIMITER $$
CREATE TRIGGER trg_source_ai_log
AFTER INSERT ON Source
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Source', 'INSERT', NEW.SourceID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_source_au_log;
DELIMITER $$
CREATE TRIGGER trg_source_au_log
AFTER UPDATE ON Source
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Source', 'UPDATE', NEW.SourceID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_source_ad_log;
DELIMITER $$
CREATE TRIGGER trg_source_ad_log
AFTER DELETE ON Source
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Source', 'DELETE', OLD.SourceID);
END$$
DELIMITER ;

-- Tag
-- BEFORE INSERT
DROP TRIGGER IF EXISTS trg_tag_bi_guard;
DELIMITER $$
CREATE TRIGGER trg_tag_bi_guard
BEFORE INSERT ON Tag
FOR EACH ROW
BEGIN
  IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can insert Tag';
  END IF;
END$$
DELIMITER ;

-- BEFORE UPDATE
DROP TRIGGER IF EXISTS trg_tag_bu_guard;
DELIMITER $$
CREATE TRIGGER trg_tag_bu_guard
BEFORE UPDATE ON Tag
FOR EACH ROW
BEGIN
  IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can update Tag';
  END IF;
END$$
DELIMITER ;

-- BEFORE DELETE
DROP TRIGGER IF EXISTS trg_tag_bd_guard;
DELIMITER $$
CREATE TRIGGER trg_tag_bd_guard
BEFORE DELETE ON Tag
FOR EACH ROW
BEGIN
  IF fn_role_by_user_id(@current_user_id) <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can delete Tag';
  END IF;
END$$
DELIMITER ;

-- AFTER INSERT/UPDATE/DELETE: action_log
DROP TRIGGER IF EXISTS trg_tag_ai_log;
DELIMITER $$
CREATE TRIGGER trg_tag_ai_log
AFTER INSERT ON Tag
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Tag', 'INSERT', NEW.TagID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_tag_au_log;
DELIMITER $$
CREATE TRIGGER trg_tag_au_log
AFTER UPDATE ON Tag
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Tag', 'UPDATE', NEW.TagID);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_tag_ad_log;
DELIMITER $$
CREATE TRIGGER trg_tag_ad_log
AFTER DELETE ON Tag
FOR EACH ROW
BEGIN
  INSERT INTO action_log(UserID, Role, TableName, ActionType, TargetID)
  VALUES (@current_user_id, fn_role_by_user_id(@current_user_id), 'Tag', 'DELETE', OLD.TagID);
END$$
DELIMITER ;

-- Procedures
-- Source
DROP PROCEDURE IF EXISTS sp_Admin_update_source;
DELIMITER $$

CREATE PROCEDURE sp_Admin_update_source(
  IN p_Admin_user_id INT,
  IN p_source_name     VARCHAR(255),
  IN p_source_type     VARCHAR(64), -- could be NULL (Non-advanced)
  IN p_language        VARCHAR(64) -- could be NULL (Non-advanced)
)
SQL SECURITY DEFINER
BEGIN
  DECLARE v_role VARCHAR(20);
  DECLARE v_source_id INT;

  -- Access: Admin
  SELECT Role INTO v_role FROM User WHERE UserID = p_Admin_user_id;
  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can update source';
  END IF;

  -- No duplicates
  INSERT INTO Source(SourceName)
  VALUES (TRIM(p_source_name))
  ON DUPLICATE KEY UPDATE SourceID = LAST_INSERT_ID(SourceID);
  SET v_source_id = LAST_INSERT_ID();

  -- Overwrite
  UPDATE Source
     SET SourceType = NULLIF(TRIM(p_source_type), ''),
         Language   = NULLIF(TRIM(p_language), '')
   WHERE SourceID = v_source_id;

END$$
DELIMITER ;

-- Tag
DROP PROCEDURE IF EXISTS sp_Admin_update_tag;
DELIMITER $$

CREATE PROCEDURE sp_Admin_update_tag(
  IN p_Admin_user_id INT,
  IN p_tag_name        VARCHAR(255),
  IN p_description     TEXT -- could be NULL (Non-advanced)
)
SQL SECURITY DEFINER
BEGIN
  DECLARE v_role VARCHAR(20);

  -- Access: Admin
  SELECT Role INTO v_role FROM User WHERE UserID = p_Admin_user_id;
  IF v_role <> 'Admin' COLLATE utf8mb4_general_ci THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Admin can update tag';
  END IF;

  -- No duplicates
  INSERT INTO Tag(TagName)
  VALUES (TRIM(p_tag_name))
  ON DUPLICATE KEY UPDATE TagID = LAST_INSERT_ID(TagID);

  -- Overwrite
  UPDATE Tag
     SET Description = NULLIF(TRIM(p_description), '')
   WHERE TagName = TRIM(p_tag_name);

END$$
DELIMITER ;
