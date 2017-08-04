CREATE TABLE PX.GNAME_SIMILARITY (
  FROM_GN_ID NUMBER(9,0) NOT NULL,
  TO_GN_ID NUMBER(9,0) NOT NULL,
  similarity DECIMAL(10,6) NOT NULL,
  CONSTRAINT PK_GNAME_SIMILARITY PRIMARY KEY (FROM_GN_ID, To_GN_ID) using index tablespace PX_INDX
)
tablespace PX_DATA
/

ALTER TABLE PX.GNAME_SIMILARITY
  ADD CONSTRAINT FK_GNAME_SIMILATIRY_GNAME
  FOREIGN KEY (FROM_GN_ID) REFERENCES PX.GNAME (GN_ID)
/

ALTER TABLE PX.GNAME_SIMILARITY
  ADD CONSTRAINT FK_GNAME_SIMILATIRY_GNAME2
  FOREIGN KEY (To_GN_ID) REFERENCES PX.GNAME (GN_ID)
/

-- generate gnames.php for distance calculation PHP
select '  $gnames[' || GN_ID || '] = "'||GNAME||'";' from PX.GNAME
where length(gname)>1
order by gname;

-- now import csv file using gname-similarity.bat