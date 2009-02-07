ALTER TABLE agora_messages ADD message_modifystamp INT NOT NULL DEFAULT 0;

UPDATE agora_messages SET message_modifystamp = message_timestamp;