-- Run this once in phpMyAdmin after importing w5-spotify-db (1).sql
ALTER TABLE `user`
    ADD `password_hash` VARCHAR(255) NOT NULL,
    ADD `role` ENUM('user', 'artist') NOT NULL DEFAULT 'user',
    ADD `artist_id` VARCHAR(255) NULL,
    ADD CONSTRAINT `user_artist_fk`
        FOREIGN KEY (`artist_id`) REFERENCES `artist` (`artist_id`)
        ON DELETE SET NULL ON UPDATE CASCADE;
