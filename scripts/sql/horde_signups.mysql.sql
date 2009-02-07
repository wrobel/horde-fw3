-- $Horde: horde/scripts/sql/horde_signups.mysql.sql,v 1.1.2.1 2008/08/13 20:18:03 chuck Exp $
CREATE TABLE horde_signups (
    user_name VARCHAR(255) NOT NULL,
    signup_date VARCHAR(255) NOT NULL,
    signup_host VARCHAR(255) NOT NULL,
    signup_email VARCHAR(255) NOT NULL,
    signup_data TEXT NOT NULL,
    UNIQUE KEY user_name (user_name),
    UNIQUE KEY signup_email (signup_email)
);
