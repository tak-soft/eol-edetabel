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
