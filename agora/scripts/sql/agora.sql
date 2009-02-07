-- $Horde: agora/scripts/sql/agora.sql,v 1.11 2007/06/27 15:18:59 chuck Exp $

CREATE TABLE agora_files (
    file_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    file_type VARCHAR(32) NOT NULL,
    message_id INT NOT NULL DEFAULT 0,
--
    PRIMARY KEY (file_id)
);
CREATE INDEX agora_file_message_idx ON agora_files (message_id);

CREATE TABLE agora_forums (
    forum_id INT NOT NULL,
    scope VARCHAR(10) NOT NULL,
    forum_name VARCHAR(255) NOT NULL,
    active SMALLINT NOT NULL,
    forum_description VARCHAR(255),
    forum_parent_id INT,
    author VARCHAR(32) NOT NULL,
    forum_moderated SMALLINT,
    forum_attachments VARCHAR(50),
    message_count INT DEFAULT 0,
    thread_count INT DEFAULT 0,
    count_views SMALLINT,
--
    PRIMARY KEY (forum_id)
);
CREATE INDEX agora_forum_scope_idx ON agora_forums (scope, active);

CREATE TABLE agora_messages (
    message_id INT NOT NULL,
    forum_id INT NOT NULL DEFAULT 0,
    message_thread INT NOT NULL DEFAULT 0,
    parents VARCHAR(255),
    message_author VARCHAR(32) NOT NULL,
    message_subject VARCHAR(85) NOT NULL,
    body text NOT NULL,
    attachments SMALLINT NOT NULL DEFAULT 0,
    ip VARCHAR(30) NOT NULL,
    status SMALLINT NOT NULL DEFAULT 2,
    message_seq INT NOT NULL DEFAULT 0,
    approved SMALLINT NOT NULL DEFAULT 0,
    message_timestamp INT NOT NULL DEFAULT 0,
    message_modifystamp INT NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    locked SMALLINT NOT NULL DEFAULT 0,
--
    PRIMARY KEY  (message_id)
);
CREATE INDEX agora_messages_forum_id ON agora_messages (forum_id);
CREATE INDEX agora_messages_message_thread ON agora_messages (message_thread);
CREATE INDEX agora_messages_parents ON agora_messages (parents);

CREATE TABLE agora_moderators (
    forum_id INT NOT NULL DEFAULT 0,
    horde_uid VARCHAR(32) NOT NULL,
--
    PRIMARY KEY (forum_id, horde_uid)
);
