-- $Horde: horde/scripts/sql/horde_sessionhandler.sql,v 1.1.10.1 2007/12/20 15:03:03 jan Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           LONGBLOB,
-- Or, on some DBMS systems:
--  session_data           IMAGE,

    PRIMARY KEY (session_id)
);
