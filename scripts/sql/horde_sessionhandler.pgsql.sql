-- $Horde: horde/scripts/sql/horde_sessionhandler.pgsql.sql,v 1.1.10.1 2007/12/20 15:03:03 jan Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           TEXT,
    PRIMARY KEY (session_id)
);
