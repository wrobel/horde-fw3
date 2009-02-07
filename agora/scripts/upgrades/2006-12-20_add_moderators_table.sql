CREATE TABLE agora_moderators (
    forum_id INT NOT NULL DEFAULT 0,
    horde_uid VARCHAR(32) NOT NULL,
--
    PRIMARY KEY (forum_id, horde_uid)
);
