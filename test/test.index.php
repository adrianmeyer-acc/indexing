<?php

  $time_start = microtime(true); 
  ini_set('memory_limit','2000M');

  require_once( '../php/service.database.php' );
  require_once( '../php/service.index.php' );
  require_once( '../php/index.processor.phone.php' );
  require_once( '../php/index.processor.firstname.php' );
  require_once( '../php/address.formatter.php' );

	print_r( \index\IndexProcessorPhone::process( '+41793251753' ));
//	print_r( \index\IndexProcessorFirstname::process( 'adrian' ));
	die;


/*
	$abstract = IndexService::buildAbstract( acCONTACT, '10001' );
	$abstract->addSegment( '<span class="fn1">Robin</span> <span match="lastname" class="ln1">Müller</span>' );
	$abstract->addSegment( '<span>Bergweg 23</span>' );
	$abstract->addSegment( 'CH-<span match="full">3064</span> <span>Ittigen</span>' );
	$abstract->addSegment( '&#x260E; <span match="phone">+41 79 123-4567</span>' );

  IndexService::rebuildPending();

  echo "\nTotal: ".(microtime(true) - $time_start)."s\n";
	die;



  require_once( 'index.processor.phone.php' );

  //Switzerland
	print_r( \index\IndexProcessorPhone::process( '+41 (0)31 123-4567' ));
	// germany
	print_r( \index\IndexProcessorPhone::process( '+49 6151 8525' ));	
	// malta
	print_r( \index\IndexProcessorPhone::process( '+356 2100 9988' ));
	// USA
	print_r( \index\IndexProcessorPhone::process( '+1 (919) 260-3965' ));
	die;
*/

	define( 'testOnly100', false );
	

	echo "\nLoading Files ...";	
//	$countryCodes = json_decode( file_get_contents( 'data.country.codes.json' ));
	$contacts = json_decode( file_get_contents( 'test.contacts.acc.json' ));

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 
	echo "\nPreparing Contacts...";	

  $i = 0;
	foreach( $contacts as $contact ) {
		$i++;
  	$abstract = IndexService::buildAbstract( acCONTACT, $contact->contact_id );
		$abstract->sort_value = $contact->firstname.$contact->lastname;

    // add the name and email
    if ($contact->email=='') {
			$abstract->addSegment( 
				'<span class="fn1">'.htmlentities($contact->firstname).'</span> '.
				'<span class="ln1">'.htmlentities($contact->lastname).'</span>' );
		} else {
			$abstract->addSegment( 
				'<span class="fn1">'.htmlentities($contact->firstname).'</span> '.
				'<span class="ln1">'.htmlentities($contact->lastname).'</span> '.
				'(<span class="em1" match="email">'.htmlentities($contact->email).'</span>)' );
		}

		$abstract->addSegment( '<span class="co1">'.htmlentities($contact->company).'</span>' );
		$abstract->addSegment( 
		  '<span class="st1">'.htmlentities($contact->street).
		  '</span> <span match="full">'.$contact->building_nr.'</span>' 
		);
		$abstract->addSegment( 
		  '<span class="zi1">'.htmlentities($contact->zip).'</span> '.
			'<span class="ci1">'.htmlentities($contact->city).'</span>' );

		if ($i>100 & testOnly100) {
			break;
		}
	}

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 

	echo "\nLoading Tickets ...";	
	$tickets = json_decode( file_get_contents( 'test.tickets.acc.json' ));

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 
	echo "\nPreparing Tickets...";	

  $i = 0;
	foreach( $tickets as $ticket ) {
		$i++;
  	$abstract = IndexService::buildAbstract( acTICKET, $ticket->ticket_id );
		$abstract->sort_value = (100000000 - $ticket->ticket_id);

		$abstract->addSegment( 
			'#<span class="id2">'.$ticket->ticket_id.'</span> '.
			' <span class="pj2">'.htmlentities($ticket->project).'</span> '.
			' (<span class="ct2">'.htmlentities($ticket->category).'</span>)'.
			' [<span class="st2">'.htmlentities($ticket->status).'</span>]'.
			' by <span class="rp2">'.htmlentities($ticket->reporter).'</span>'.
			' at <span class="dt2">'.htmlentities($ticket->created).'</span>' );
		$abstract->addSegment( '<span class="su2">'.htmlentities($ticket->summary).'</span>' );
		if ($i>100 && testOnly100) {
			break;
		}
	}



/*
  // Some manual test data
	$abstract = IndexService::buildAbstract( acCONTACT, '10000' );
	$abstract->addSegment( '<span>Adrian</span> <span>Meyer</span>' );
	$abstract->addSegment( '<span>Eggweg 125b</span>' );
	$abstract->addSegment( 'CH-<span>3065</span> <span>Bolligen</span>' );
	$abstract->addFilter( 'COUNTRY', 'Switzerland', 'SWI' );
	$abstract->addFilter( 'AGE_GROUP', '30-50', '03' );

	$abstract = IndexService::buildAbstract( acCONTACT, '10001' );
	$abstract->addSegment( '<span>Robin</span> <span>Bieri</span>' );
	$abstract->addSegment( '<span>Bergweg 23</span>' );
	$abstract->addSegment( 'CH-<span match="full">3064</span> <span>Ittigen</span>' );
	$abstract->addFilter( 'COUNTRY', 'Switzerland', 'SWI' );
	$abstract->addFilter( 'AGE_GROUP', '18-30', '02' );

	$abstract = IndexService::buildAbstract( acCONTACT, '10002' );
	$abstract->addSegment( '<span>Roger</span> <span>Müller</span>' );
	$abstract->addSegment( '<span>Kistlerstrasse 22</span>' );
	$abstract->addSegment( 'CH-<span>3065</span> <span>Bolligen</span>' );
	$abstract->addFilter( 'COUNTRY', 'Switzerland', 'SWI' );
	$abstract->addFilter( 'AGE_GROUP', '30-50', '03' );

*/

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 

	echo "\nBuilding indexes...";	

  IndexService::rebuildPending();

  echo "\nTotal: ".(microtime(true) - $time_start)."s\n";

//	print_r( indx\AbstractModel::getAllAbstracts());

?>