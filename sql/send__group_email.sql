-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Počítač: localhost
-- Vytvořeno: Sob 27. pro 2014, 17:00
-- Verze serveru: 5.5.40-MariaDB-0ubuntu0.14.10.1
-- Verze PHP: 5.5.12-2ubuntu4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Struktura tabulky `send__group_email`
--

CREATE TABLE IF NOT EXISTS `send__group_email` (
	`id`       INT(11) NOT NULL,
	`group_id` INT(11) NOT NULL,
	`email_id` INT(11) NOT NULL
)
	ENGINE =InnoDB
	AUTO_INCREMENT =1
	DEFAULT CHARSET =utf8
	COLLATE =utf8_czech_ci;

--
-- Klíče pro tabulku `send__group_mail`
--
ALTER TABLE `send__group_email`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `group_id_2` (`group_id`, `email_id`), ADD KEY `group_id` (`group_id`), ADD KEY `email_id` (`email_id`);

--
-- AUTO_INCREMENT pro tabulku `send__group_mail`
--
ALTER TABLE `send__group_email`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT =1;

--
-- Omezení pro tabulku `send__group_mail`
--
ALTER TABLE `send__group_email`
ADD CONSTRAINT `send__group_mail_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `send__groups` (`id`)
	ON UPDATE CASCADE,
ADD CONSTRAINT `send__group_mail_ibfk_2` FOREIGN KEY (`email_id`) REFERENCES `send__emails` (`id`)
	ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
