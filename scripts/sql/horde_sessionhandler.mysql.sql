-- $Horde: horde/scripts/sql/horde_sessionhandler.mysql.sql,v 1.1.2.2 2007/12/20 15:03:03 jan Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           LONGBLOB,

    PRIMARY KEY (session_id)
) ENGINE = InnoDB;
