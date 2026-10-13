-- Execute uma vez no banco admins antes de usar as páginas PHP.
-- Se houver e-mails duplicados, resolva-os antes de criar o índice único.
ALTER TABLE users
    ADD COLUMN ROLE varchar(20) NOT NULL DEFAULT 'admin';

ALTER TABLE users
    ADD UNIQUE KEY users_email_unique (EMAIL);
