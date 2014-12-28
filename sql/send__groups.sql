-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Počítač: localhost
-- Vytvořeno: Sob 27. pro 2014, 17:01
-- Verze serveru: 5.5.40-MariaDB-0ubuntu0.14.10.1
-- Verze PHP: 5.5.12-2ubuntu4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Struktura tabulky `send__groups`
--

CREATE TABLE IF NOT EXISTS `send__groups` (
	`id`        INT(11)               NOT NULL,
	`parent_id` INT(11)                        DEFAULT NULL,
	`name`      VARCHAR(60)
				COLLATE utf8_czech_ci NOT NULL,
	`type`      ENUM('public', 'private')
				COLLATE utf8_czech_ci NOT NULL DEFAULT 'public'
)
	ENGINE =InnoDB
	AUTO_INCREMENT =1
	DEFAULT CHARSET =utf8
	COLLATE =utf8_czech_ci;

--
-- Klíče pro tabulku `send__groups`
--
ALTER TABLE `send__groups`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`), ADD KEY `parent_id` (`parent_id`);

--
-- AUTO_INCREMENT pro tabulku `send__groups`
--
ALTER TABLE `send__groups`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT =1;

--
-- Omezení pro tabulku `send__groups`
--
ALTER TABLE `send__groups`
ADD CONSTRAINT `send__group_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `send__groups` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
