-- migrations for EOL edetabel
CREATE TABLE IF NOT EXISTS iofevents (
  eventorId BIGINT PRIMARY KEY,
  kuupaev DATE,
  nimetus VARCHAR(255),
  distants VARCHAR(255),
  riik CHAR(3),
  alatunnus CHAR(3)
);

CREATE TABLE IF NOT EXISTS iofrunners (
  iofId BIGINT PRIMARY KEY,
  firstname VARCHAR(255),
  lastname VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS iofresults (
  id INT AUTO_INCREMENT PRIMARY KEY,
  eventorId BIGINT,
  iofId BIGINT,
  tulemus INT,
  koht INT,
  RankPoints FLOAT,
  INDEX(eventorId),
  INDEX(iofId)
);
