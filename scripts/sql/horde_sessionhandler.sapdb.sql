-- $Horde: horde/scripts/sql/horde_sessionhandler.sapdb.sql,v 1.1 2004/09/18 17:20:59 chuck Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           LONG BYTE,
    PRIMARY KEY (session_id)
)
