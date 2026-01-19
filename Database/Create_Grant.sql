CREATE USER IF NOT EXISTS 'app_user'@'localhost' IDENTIFIED BY 'app_user_password';
CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'admin_password';

GRANT SELECT, INSERT, UPDATE, DELETE ON projectDB3.* TO 'admin'@'localhost';

GRANT SELECT ON projectDB3.Affiliation      TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.Source           TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.Author           TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.Document         TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.DocumentAuthor   TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.Tag              TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.DocumentTag      TO 'app_user'@'localhost';
GRANT SELECT ON projectDB3.Citation         TO 'app_user'@'localhost';

GRANT SELECT, INSERT, UPDATE, DELETE ON projectDB3.Notes             TO 'app_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON projectDB3.Comment           TO 'app_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON projectDB3.Collection        TO 'app_user'@'localhost';
GRANT SELECT, INSERT, DELETE        ON projectDB3.CollectionDocument TO 'app_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON projectDB3.User              TO 'app_user'@'localhost';

GRANT EXECUTE ON projectDB3.* TO 'admin'@'localhost';

FLUSH PRIVILEGES;