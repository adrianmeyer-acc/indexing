echo Creating new database...

function_abort() {
   echo
   echo "***************************"
   echo "*       CHECK ERROR       *"
   echo "***************************"
}

mysql -v -uroot -p123456 < indx-reset.sql || function_abort
mysql -v -uroot -p123456 indx < indx.ddl || function_abort

echo
echo "**********************"
echo "*       ALL OK       *"
echo "**********************"

