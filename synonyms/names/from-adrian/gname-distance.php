<?php

  require_once( 'gnames.php' );
  
	$total = 0;
	$count = count($gnames);
	
	// calculate total combinations
  for ($i=1;$i<$count;$i++){
		$total += $i;
	}
	
  $csvFile = fopen("gname-distance.csv", "w");
  fwrite($csvFile, "FROM_GN_ID,TO_GN_ID,SIMILARITY\n");

	$gnameIds = array_keys( $gnames );
	$progress = 0;
	$writeCount = 0;
	$lastProgress = 0;
	$factor = 100/$total;
	
  for ($i=1;$i<$count;$i++){
    for ($j=$i+1;$j<=$count;$j++){

  		$progress += 1;
			if (floor( $factor*$progress )>$lastProgress) {
				$lastProgress = floor( $factor*$progress );
				echo "{$lastProgress}%\n";
			}
			
			$percent = 0.0;
			similar_text($gnames[$gnameIds[$i-1]], $gnames[$gnameIds[$j-1]], $percent );

			if ($percent >=50) {
				fwrite($csvFile, "{$gnameIds[$i-1]},{$gnameIds[$j-1]},{$percent}\n");
				$writeCount++;
			}			
  	}
	}

  fclose($csvFile);
	
	echo "GNAMES: ".count($gnames)."\n";
	echo "Combinations checked: {$total}\n";
	echo "Combinations in file: {$writeCount}\n";

?>