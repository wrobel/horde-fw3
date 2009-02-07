-- $Horde: jonah/scripts/sql/jonah.mssql.sql,v 1.7 2007/11/25 13:28:40 jan Exp $

CREATE TABLE jonah_channels (
    channel_id        INT NOT NULL,
    channel_name      VARCHAR(255) NOT NULL,
    channel_type      SMALLINT NOT NULL,
    channel_desc      VARCHAR(255),
    channel_interval  INT,
    channel_url       VARCHAR(255),
    channel_link      VARCHAR(255),
    channel_page_link VARCHAR(255),
    channel_story_url VARCHAR(255),
    channel_img       VARCHAR(255),
    channel_updated   INT,
--
    PRIMARY KEY (channel_id)
);

CREATE TABLE jonah_stories (
    story_id        INT NOT NULL,
    channel_id      INT NOT NULL,
    story_title     VARCHAR(255) NOT NULL,
    story_desc      VARCHAR(MAX),
    story_body_type VARCHAR(255) NOT NULL,
    story_body      VARCHAR(MAX),
    story_url       VARCHAR(255),
    story_permalink VARCHAR(255),
    story_published INT,
    story_updated   INT NOT NULL,
    story_read      INT NOT NULL,
--
    PRIMARY KEY (story_id)
);

CREATE INDEX jonah_stories_channel_idx ON jonah_stories (channel_id);
CREATE INDEX jonah_stories_published_idx ON jonah_stories (story_published);
CREATE INDEX jonah_stories_url_idx ON jonah_stories (story_url);
CREATE INDEX jonah_channels_type_idx ON jonah_channels (channel_type);
