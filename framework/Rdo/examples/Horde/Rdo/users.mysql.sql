-- $Horde: framework/Rdo/examples/Horde/Rdo/users.mysql.sql,v 1.1.2.1 2008-05-15 23:23:14 chuck Exp $
CREATE TABLE users (
    id INT(11) auto_increment  NOT NULL,
    name varchar(255),
    favorite_id int(11),
    phone varchar(20),
    created varchar(10),
    updated varchar(10),

    PRIMARY KEY  (id)
);
