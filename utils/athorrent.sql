-- phpMyAdmin SQL Dump
-- version 4.4.13.1
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Mer 14 Octobre 2015 à 19:26
-- Version du serveur :  5.6.17
-- Version de PHP :  5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `athorrent`
--
CREATE DATABASE IF NOT EXISTS `athorrent` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `athorrent`;

-- --------------------------------------------------------

--
-- Structure de la table `sharing`
--

CREATE TABLE IF NOT EXISTS `sharing` (
  `token` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  `path` text COLLATE utf8_unicode_ci NOT NULL,
  `creationTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `userId` int(10) unsigned NOT NULL,
  `username` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(88) COLLATE utf8_unicode_ci NOT NULL,
  `salt` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `creationTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `connectionTimestamp` timestamp NULL DEFAULT NULL,
  `usedDiskSpace` bigint(20) UNSIGNED NOT NULL,
  `totalDiskSpace` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_role`
--

CREATE TABLE IF NOT EXISTS `user_role` (
  `userId` int(10) unsigned NOT NULL,
  `role` enum('ROLE_USER','ROLE_ADMIN') COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `sharing`
--
ALTER TABLE `sharing`
  ADD PRIMARY KEY (`token`) USING BTREE,
  ADD KEY `userId` (`userId`),
  ADD KEY `creationTimestamp` (`creationTimestamp`),
  ADD FULLTEXT KEY `path` (`path`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`userId`,`role`),
  ADD KEY `userId` (`userId`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `userId` int(10) unsigned NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
