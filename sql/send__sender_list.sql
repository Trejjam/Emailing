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
-- Struktura tabulky `send__sender_list`
--

CREATE TABLE IF NOT EXISTS `send__sender_list` (
	`id`          INT(11)               NOT NULL,
	`email`       VARCHAR(40)
				  COLLATE utf8_czech_ci NOT NULL,
	`config_name` VARCHAR(20)
				  COLLATE utf8_czech_ci NOT NULL DEFAULT 'default'
)
	ENGINE =InnoDB
	AUTO_INCREMENT =1
	DEFAULT CHARSET =utf8
	COLLATE =utf8_czech_ci;

--
-- Klíče pro tabulku `send__sender_list`
--
ALTER TABLE `send__sender_list`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pro tabulku `send__sender_list`
--
ALTER TABLE `send__sender_list`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT =1;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
