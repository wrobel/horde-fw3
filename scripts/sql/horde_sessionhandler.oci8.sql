-- $Horde: horde/scripts/sql/horde_sessionhandler.oci8.sql,v 1.2.10.2 2007/12/20 15:03:03 jan Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR2(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           BLOB,
--
    PRIMARY KEY (session_id)
);
