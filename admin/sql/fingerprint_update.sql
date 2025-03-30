-- Add FingerprintID column to tblstudents
ALTER TABLE tblstudents
ADD COLUMN FingerprintID INT UNIQUE;
