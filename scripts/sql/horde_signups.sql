-- $Horde: horde/scripts/sql/horde_signups.sql,v 1.1.2.1 2008/08/13 20:18:03 chuck Exp $
CREATE TABLE horde_signups (
    user_name VARCHAR(255) NOT NULL,
    signup_date INTEGER NOT NULL,
    signup_host VARCHAR(255) NOT NULL,
    signup_email VARCHAR(255) NOT NULL,
    signup_data TEXT NOT NULL,
    PRIMARY KEY (user_name)
);
