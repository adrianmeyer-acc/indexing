<?php
	// enable output compression in gz format
	ob_start("ob_gzhandler");
	header('Content-Type: application/json; charset=utf-8');
	
  require_once( 'php/service.database.php' );
  require_once( 'php/service.index.php' );

  // -------------------------------------------
  // set the language dependent options
  // -------------------------------------------
	IndexService::setConceptLabel( acCONTACT, 'Contacts' );
	IndexService::setConceptLabel( acTICKET, 'Tickets' );
	IndexService::setConceptLabel( acMESSAGE, 'Messages' );
	IndexService::setConceptLabel( acOBJECT, 'Objects' );
	IndexService::setConceptLabel( acWIKI, 'Wiki Pages' );

	IndexService::setLabel( lblSearching, 'Searching...' );
	IndexService::setLabel( lblSearchingFor, 'Searching for:' );
	IndexService::setLabel( lblMatchingAll, 'Showing <strong>ALL</strong> matching %s' );
	IndexService::setLabel( lblMatchingPartial, 'Showing first <strong>%d</strong> of <strong>%d</strong> %s' );
	IndexService::setLabel( lblInConcepts, 'in' );

  try {

	  // check for tem parameter
	  if (!isset( $_GET['t'] )) {
			throw new Exception( 'Term parameter missing' );
		}

	  // check for concept parameter
	  if (!isset( $_GET['c'] )) {
			$concept_id = NULL;
		} else {
			$concept_id = $_GET['c'];
		}

		echo json_encode( IndexService::search( urldecode( $_GET['t']), $concept_id ), JSON_PRETTY_PRINT );

	} catch ( Exception $e ) {
		$data['error'] = $e->getMessage();
		$data['line'] = $e->getLine();
		$data['file'] = $e->getFile();
    echo json_encode($data);
	}
	
?>