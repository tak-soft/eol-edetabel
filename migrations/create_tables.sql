-- migrations for EOL edetabel
CREATE TABLE IF NOT EXISTS iofevents (
  eventorId INT UNSIGNED PRIMARY KEY,
  kuupaev DATE,
  nimetus VARCHAR(150),
  distants VARCHAR(50),  mysql -u <user> -p < dbname < migrations/create_tables.sql
  riik CHAR(3),
  alatunnus CHAR(3)
);

CREATE TABLE IF NOT EXISTS iofrunners (
  iofId INT UNSIGNED PRIMARY KEY,
  firstname VARCHAR(50),
  lastname VARCHAR(50),
    -- previously had sex; not required per updated spec
);

CREATE TABLE IF NOT EXISTS iofresults (
  id INT AUTO_INCREMENT PRIMARY KEY,
  eventorId INT,
  iofId INT UNSIGNED,
  tulemus INT,
  koht INT,
  RankPoints FLOAT,
    `Group` VARCHAR(16),
  INDEX(eventorId),
  INDEX(iofId),
  UNIQUE KEY uq_event_iof (eventorId, iofId)
);

-- Table for edetabel (ranking) settings per discipline/year
CREATE TABLE IF NOT EXISTS edetabli_seaded (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aasta INT NOT NULL,
  nimetus VARCHAR(255) NOT NULL,
  alakood VARCHAR(16) NOT NULL,
  periood_lopp DATE NULL,
  periood_kuud INT DEFAULT 12,
  arvesse INT DEFAULT 0
);


CREATE TABLE `eolkoodid` (
  `ID` int(11) NOT NULL,
  `EESNIMI` varchar(30) DEFAULT '',
  `PERENIMI` varchar(30) DEFAULT '',
  `SYNNIKUUP` date DEFAULT '0000-00-00',
  `SUGU` char(1) DEFAULT '',
  `MAAKOND` varchar(25) DEFAULT '',
  `KLUBI` varchar(200) DEFAULT NULL,
  `ASUTUS` varchar(50) DEFAULT '',
  `EPOST` varchar(50) DEFAULT '',
  `RESERV` tinyint(4) NOT NULL DEFAULT 0,
  `SIKAART` varchar(40) DEFAULT '',
  `MUUDATUS` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `KOOD` int(11) NOT NULL DEFAULT 0,
  `LITSENTS` date NOT NULL DEFAULT '0000-00-00',
  `litsents_eolteenus_id` int(11) DEFAULT NULL,
  `KLASS` varchar(10) NOT NULL DEFAULT '',
  `ISIKUKOOD` varchar(12) NOT NULL DEFAULT '',
  `AVALDAN` tinyint(1) NOT NULL DEFAULT 0,
  `MUUTKP` datetime DEFAULT NULL,
  `MUUTJA` varchar(30) DEFAULT NULL,
  `NA` tinyint(1) DEFAULT 1,
  `PASSWRD` varchar(40) NOT NULL DEFAULT '',
  `ALIAS` varchar(40) NOT NULL DEFAULT '',
  `PASSPORT` tinyint(1) NOT NULL DEFAULT 1,
  `IOFKOOD` int(11) DEFAULT NULL,
  `FOTO` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_estonian_ci;

