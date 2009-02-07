-- $Horde: agora/scripts/sql/agora.mysql.sql,v 1.11 2007/02/24 22:21:05 chuck Exp $

CREATE TABLE agora_files (
    file_id INT(11) UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT(11) UNSIGNED NOT NULL default 0,
    file_type VARCHAR(32) NOT NULL,
    message_id MEDIUMINT(9) UNSIGNED DEFAULT 0,
--
    PRIMARY KEY (file_id)
);
CREATE INDEX agora_file_message_idx ON agora_files (message_id);

CREATE TABLE agora_forums (
    forum_id SMALLINT(6) UNSIGNED NOT NULL,
    scope VARCHAR(10) NOT NULL,
    forum_name VARCHAR(255) NOT NULL,
    active SMALLINT(6) UNSIGNED NOT NULL,
    forum_description TEXT,
    forum_parent_id SMALLINT(11) UNSIGNED,
    author VARCHAR(32) NOT NULL,
    forum_moderated SMALLINT(6) UNSIGNED,
    forum_attachments VARCHAR(50),
    message_count SMALLINT(6) UNSIGNED DEFAULT 0,
    thread_count SMALLINT(6) UNSIGNED DEFAULT 0,
    count_views SMALLINT(6) UNSIGNED,
--
    PRIMARY KEY (forum_id)
);
CREATE INDEX agora_forum_scope_idx ON agora_forums (scope, active);

CREATE TABLE agora_messages (
    message_id MEDIUMINT(9) UNSIGNED NOT NULL,
    forum_id SMALLINT(6) UNSIGNED NOT NULL DEFAULT 0,
    message_thread MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 0,
    parents VARCHAR(255) DEFAULT NULL,
    message_author VARCHAR(32) NOT NULL,
    message_subject VARCHAR(85) NOT NULL,
    body TEXT NOT NULL,
    attachments TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    ip VARCHAR(30) NOT NULL,
    status TINYINT(1) UNSIGNED NOT NULL DEFAULT 2,
    message_seq INT(11) NOT NULL DEFAULT 0,
    approved TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    message_timestamp INT(11) UNSIGNED NOT NULL DEFAULT 0,
    message_modifystamp INT(11) UNSIGNED NOT NULL DEFAULT 0,
    view_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
    locked TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
--
    PRIMARY KEY (message_id)
);
CREATE INDEX agora_messages_forum_id ON agora_messages (forum_id);
CREATE INDEX agora_messages_message_thread ON agora_messages (message_thread);
CREATE INDEX agora_messages_parents ON agora_messages (parents);

CREATE TABLE agora_moderators (
    forum_id SMALLINT(6) UNSIGNED NOT NULL,
    horde_uid VARCHAR(32) NOT NULL,
--
    PRIMARY KEY (forum_id, horde_uid)
);
