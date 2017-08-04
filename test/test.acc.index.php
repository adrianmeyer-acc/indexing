<?php

  $time_start = microtime(true); 
  ini_set('memory_limit','2000M');

  require_once( '../php/service.database.php' );
  require_once( '../php/service.index.php' );
  require_once( '../php/index.processor.phone.php' );
  require_once( '../php/address.formatter.php' );
	
	define( 'testOnly100', false );
	
	// ================================================================
	// Process Contacts
	// ================================================================
	echo "\n\nLoading Contacts...";	
	
	$contacts = json_decode( file_get_contents( 'test.acc.contacts.json' ));

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 
	echo "\nPreparing Contacts (".count($contacts).")...";	

  $i = 0;
	foreach( $contacts as $contact ) {
		$i++;
    // -------------------------------------
    // Basic setup including sorting and url
    // -------------------------------------
  	$abstract = IndexService::buildAbstract( acCONTACT, $contact->contact_id );
		$abstract->sort_value = $contact->firstname.$contact->lastname;
		$abstract->url = "https://acc.servicechampion.com/app/de/customer/detail/customerId/{$contact->customer_id}/contactId/{$contact->contact_id}";

    // -------------------------------------
		// ICON for concept, dependent on gender
    // -------------------------------------
    if ($contact->gender=='m') {
			$abstract->icon = 'search-icon-male.svg';
		} else {
			$abstract->icon = 'search-icon-female.svg';
		}

    // -------------------------------------
    // Name and email of contact
    // -------------------------------------
    if ($contact->email=='') {
			$abstract->addSegment( 
				'<span class="bold" match="firstname">'.htmlentities($contact->firstname).'</span> '.
				'<span class="bold" match="lastname">'.htmlentities($contact->lastname).'</span>' );
		} else {
			$abstract->addSegment( 
				'<span class="bold" match="firstname">'.htmlentities($contact->firstname).'</span> '.
				'<span class="bold" match="lastname">'.htmlentities($contact->lastname).'</span> '.
				'&nbsp;(<span class="em1" match="email">'.htmlentities($contact->email).'</span>)' );
		}

    // -------------------------------------
		// Add phone numbers
    // -------------------------------------
    $phoneNumbers = '';
    // add office phone
    if ($contact->office!='') {
    	$phoneNumbers .= '<img src="/images/search-icon-office.svg"><span match="phone">'.
			  \index\IndexProcessorPhone::formatInternational( $contact->office ).'</span>';
		}

    // add mobile phone
    if ($contact->mobile!='') {
    	$phoneNumbers .= ' <img src="/images/search-icon-cell.svg"><span match="phone">'.
			  \index\IndexProcessorPhone::formatInternational( $contact->mobile ).'</span>';
		}
		
    // add numbers if we have any
    if ($phoneNumbers!='') {
			$abstract->addSegment( $phoneNumbers );
		}

    // -------------------------------------
		// Address, uses the address formatter to 
		// get correct international display of address based on country code
    // -------------------------------------
		$data = new \address\AddressFormatterData();
		$data->CountryISO2 = $contact->country_code;

		if ($contact->company != '' ) {
  		$data->Business = '<span>'.htmlentities($contact->company).'</span>';
		}
		
		if ($contact->street != '' ) {
  		$data->Street = '<span class="st1">'.htmlentities($contact->street).'</span>';
		}
		
		if ($contact->building_nr != '' ) {
  		$data->StreetNumber = '<span match="full">'.htmlentities($contact->building_nr).'</span>';
		}

		if ($contact->city != '' ) {
  		$data->City = '<span class="ci1">'.htmlentities($contact->city).'</span>';
		}

		if ($contact->zip != '' ) {
  		$data->Zip = '<span match="full" class="zi1">'.htmlentities($contact->zip).'</span>';
		}
		
		$abstract->addSegment( implode( ', ', \address\AddressFormatter::format( $data )) );

		if ($i>100 & testOnly100) {
			break;
		}
	}
	unset( $contacts );
  
  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 

	// ================================================================
	// Process Wiki Pages
	// ================================================================
	echo "\n\nLoading Wiki Pages...";	
	
	$pages = json_decode( file_get_contents( 'test.acc.wiki.json' ));

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 
	echo "\nPreparing Pages (".count($pages).")...";	

  $i = 0;
	foreach( $pages as $page ) {
		$i++;
    // -------------------------------------
    // Basic setup including sorting and url
    // -------------------------------------
  	$abstract = IndexService::buildAbstract( acWIKI, $page->page_id );
		$abstract->sort_value = 1000000000 - $page->page_counter;

    // vary the icon
    if ($page->wiki_id == 0) {
  		$abstract->icon = 'search-icon-wikipublic.svg';
  		$abstract->url = "https://acc.servicechampion.com/app/wiki/0/de/index.php?title=".urlencode($page->page_title);
		} else {
  		$abstract->icon = 'search-icon-wiki.svg';
  		$abstract->url = "https://acc.servicechampion.com/app/wiki/11/de/index.php?title=".urlencode($page->page_title);
		}

    $abstract->addSegment( '<span class="bold">'.htmlentities( preg_replace("/[^[:alnum:][:space:]]/u", ' ', $page->page_title )).'</span>' );
		
		if ($page->last_change!='') {
			$abstract->addSegment( 
				DateTime::createFromFormat('YmdHis', $page->last_change )->format( 'd.m.Y H:i' ).
				' <img src="/images/search-icon-edit.svg"> '.
				$page->user );
		} else {
			$abstract->addSegment( '&nbsp;' );
		}
			
		if ($i>100 & testOnly100) {
			break;
		}
	}
	unset( $pages );

	// ================================================================
	// Process Tickets
	// ================================================================
	echo "\n\nLoading Tickets...";	
	
	$tickets = json_decode( file_get_contents( 'test.acc.tickets.json' ));

  echo (microtime(true) - $time_start)."s";
  $time_start = microtime(true); 
	echo "\nPreparing Tickets (".count($tickets).")...";	

  $i = 0;
	foreach( $tickets as $ticket ) {
		$i++;
    // -------------------------------------
    // Basic setup including sorting and url
    // -------------------------------------
  	$abstract = IndexService::buildAbstract( acTICKET, $ticket->ticket_id );
		$abstract->sort_value = 1000000000 - $ticket->ticket_id;
		$abstract->url = "https://acc.servicechampion.com/app/mantis/view.php?id=".$ticket->ticket_id;

    // vary the icon
    if ($ticket->category_id == 870) {
  		$abstract->icon = 'search-icon-bug.svg';
		} else {
  		$abstract->icon = 'search-icon-ticket.svg';
		}

    $abstract->addSegment( '<span class="bold">'.htmlentities( $ticket->summary  ).'</span>' );
		
		$data = '#<span class="dark">'.$ticket->ticket_id.'</span>';
		$data.= ' ['.$ticket->status.']';
		$data.= ' <span class="dark">'.$ticket->project.'</span>';
		$data.= '; <span>'.$ticket->category.'</span>';
		if ($ticket->customer != '') {
      $data.='; <span class="italic">'.$ticket->customer.'</span>';
		} 
    $abstract->addSegment( $data );		
		
    $abstract->addSegment( $ticket->created.' <img src="/images/search-icon-edit.svg"> '.$ticket->reporter );
			
		if ($i>100 & testOnly100) {
			break;
		}
	}
	unset( $tickets );

  echo (microtime(true) - $time_start)."s";

  $time_start = microtime(true); 
	echo "\n\nBuilding indexes...";	

  IndexService::rebuildPending();

  echo "\nTotal: ".(microtime(true) - $time_start)."s\n";

?>