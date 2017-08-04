OPTIONS (SKIP=1,DIRECT=TRUE,rows=100000)
load data
CHARACTERSET UTF8
infile 'gname-distance.csv' "str '\n'"
replace
into table PX.GNAME_SIMILARITY
fields terminated by ','
trailing nullcols
           ( FROM_GN_ID,
             TO_GN_ID,
             SIMILARITY
             )
