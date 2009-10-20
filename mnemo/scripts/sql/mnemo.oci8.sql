-- $Horde: mnemo/scripts/sql/mnemo.oci8.sql,v 1.1.2.10 2009/10/19 10:54:36 jan Exp $

CREATE TABLE mnemo_memos (
    memo_owner      VARCHAR2(255) NOT NULL,
    memo_id         VARCHAR2(32) NOT NULL,
    memo_uid        VARCHAR2(255) NOT NULL,
    memo_desc       VARCHAR2(64) NOT NULL,
    memo_body       VARCHAR2(4000),
    memo_category   VARCHAR2(80),
    memo_private    NUMBER(1) DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (memo_owner, memo_id)
);

CREATE INDEX mnemo_notepad_idx ON mnemo_memos (memo_owner);
CREATE INDEX mnemo_uid_idx ON mnemo_memos (memo_uid);

CREATE TABLE mnemo_shares (
    share_id NUMBER(16) NOT NULL,
    share_name VARCHAR2(255) NOT NULL,
    share_owner VARCHAR2(255) NOT NULL,
    share_flags NUMBER(8) NOT NULL DEFAULT 0,
    perm_creator NUMBER(8) NOT NULL DEFAULT 0,
    perm_default NUMBER(8) NOT NULL DEFAULT 0,
    perm_guest NUMBER(8) NOT NULL DEFAULT 0,
    attribute_name VARCHAR2(255) NOT NULL,
    attribute_desc VARCHAR2(255),
    PRIMARY KEY (share_id)
);

CREATE INDEX mnemo_shares_share_name_idx ON mnemo_shares (share_name);
CREATE INDEX mnemo_shares_share_owner_idx ON mnemo_shares (share_owner);
CREATE INDEX mnemo_shares_perm_creator_idx ON mnemo_shares (perm_creator);
CREATE INDEX mnemo_shares_perm_default_idx ON mnemo_shares (perm_default);
CREATE INDEX mnemo_shares_perm_guest_idx ON mnemo_shares (perm_guest);

CREATE TABLE mnemo_shares_groups (
    share_id NUMBER(16) NOT NULL,
    group_uid VARCHAR2(255) NOT NULL,
    perm NUMBER(8) NOT NULL
);

CREATE INDEX mnemo_shares_groups_share_id_idx ON mnemo_shares_groups (share_id);
CREATE INDEX mnemo_shares_groups_group_uid_idx ON mnemo_shares_groups (group_uid);
CREATE INDEX mnemo_shares_groups_perm_idx ON mnemo_shares_groups (perm);

CREATE TABLE mnemo_shares_users (
    share_id NUMBER(16) NOT NULL,
    user_uid VARCHAR2(255) NOT NULL,
    perm NUMBER(8) NOT NULL
);

CREATE INDEX mnemo_shares_users_share_id_idx ON mnemo_shares_users (share_id);
CREATE INDEX mnemo_shares_users_user_uid_idx ON mnemo_shares_users (user_uid);
CREATE INDEX mnemo_shares_users_perm_idx ON mnemo_shares_users (perm);
