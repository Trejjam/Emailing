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
-- Struktura tabulky `send__emails`
--

CREATE TABLE IF NOT EXISTS `send__emails` (
	`id`    INT(11)                    NOT NULL,
	`email` TEXT
			COLLATE utf8mb4_unicode_ci NOT NULL
)
	ENGINE = InnoDB
	AUTO_INCREMENT = 1
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

--
-- Klíče pro tabulku `send__emails`
--
ALTER TABLE `send__emails`
ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pro tabulku `send__emails`
--
ALTER TABLE `send__emails`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 1;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
