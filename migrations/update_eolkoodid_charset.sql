-- Muuda eolkoodid tabel utf8mb4 peale
ALTER TABLE eolkoodid CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Veendu et kõik väljad on utf8mb4
ALTER TABLE eolkoodid MODIFY EESNIMI VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE eolkoodid MODIFY PERENIMI VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE eolkoodid MODIFY KLUBI VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;