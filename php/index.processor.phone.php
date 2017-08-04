<?php
//====================================================================================
/** 
 * Indexing processor for phone numbers
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  namespace index;
  
  require_once( 'index.processor.php' );
  
  //====================================================================================
  /**
   * Indexing processor for phone numbers
   * Responsibilities: Explode term for phone numbers
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class IndexProcessorPhone extends IndexTermProcessorBase{
    
    
    static $countryCodes;
    
    // *************************************
    /**
     * Process the term
     * @param string $aTerm The term to be processed
     * @return array
     */
    static function process( $aTerm ) {
      $synonyms = array();

      // detect country code on cleaned number
      $cleaned = preg_replace('/\D/', '', $aTerm);
      $countryCode = self::stripCountryCode($cleaned);
      if (isset($countryCode['code'])) {
				
				// add international notation
				$aTerm = '+'.$cleaned;
        
        // check if the international country code requires truncation: Example Switzerland: +41 (0) 31 012 3456
        if ( $items = self::getTruncatePrefix($countryCode['code']) ) {
          
          foreach( $items as $item ) {
            $truncated = ltrim( $countryCode['number'], $item );

            // add <international><local number>
            $synonyms[$countryCode['code'].$truncated] = moSYNONYM;

            // add <loacl prefix><local number> for in-country dialing
            $synonyms[$item.$truncated] = moSYNONYM;
          }
        } else {

          // add <international><local number>
          $synonyms[$countryCode['code'].$countryCode['number' ]] = moSYNONYM;

          // add number as is assuming this is in-country dialing number
          $synonyms[$countryCode['number' ]] = moSYNONYM;
        }
      } else {

				// just use stripped version
  			$aTerm = $cleaned;
			}
      
      // original phone number
      $result[$aTerm][$aTerm] = moFULL;
      
      // build individual terms for synonyms
      foreach( $synonyms as $synonym => $dummy ) {
        $result[$aTerm] = $result[$aTerm] + self::termExplode( $synonym, 3, moSYNONYM );
      }

      return $result;
    }

    // *************************************
    /**
     * Format the phone number by international standards
     * @param string $aPhoneNumber The phone number to be formatted
     * @return string The formatted number
     */
    static function formatInternational( $aPhoneNumber ) {
			return self::formatNumber( $aPhoneNumber, true );
		}

    // *************************************
    /**
     * Format the phone number by national standards
     * @param string $aPhoneNumber The phone number to be formatted
     * @return string The formatted number
     */
    static function formatNational( $aPhoneNumber ) {
			return self::formatNumber( $aPhoneNumber, false );
		}
    
    // *************************************
    /**
     * Format the phone number by international standards
     * @param string $aPhoneNumber The phone number to be formatted
     * @return string The formatted number
     */
    static function formatNumber( $aPhoneNumber, $aInternational ) {
      
      // hold the info
      $info = new \StdClass();

      // strip anything except digits
      $cleaned = preg_replace('/\D/', '', $aPhoneNumber);
      
      // check the country code
      $countryCode = self::stripCountryCode($cleaned);      
      if (isset($countryCode['code'])) {
        $info->CountryCode = $countryCode['code'];
        $info->Number = $countryCode['number'];

        // check if country code has truncated national prefix
        if ( $items = self::getTruncatePrefix($countryCode['code']) ) {

          // remember truncate code          
          $info->Truncate = reset($items);
            
          foreach( $items as $item ) {
            // check if we have the international truncation number at the beginning.
            if (substr( $countryCode['number'], 0, strlen($item) ) === $item ) {
              $info->Number = substr( $countryCode['number'], strlen($item));
            }
          }          
        } 
      } else {
        // just grab the number
        $info->Number = $cleaned;
      }
      
      // check if we have a formatting template
      if ( $info->Format = self::getFormat( $info->CountryCode )) {
        
        $results = array();
        
        foreach( $info->Format as $position=>$format ) {
          
          $hitCount = count( $info->Format) - $position;
					
					if ( $aInternational ) {
            $result = array( '+'.$info->CountryCode.' ' );
					} else {
						// add truncate code if exist
						if ( isset( $info->Truncate )) {
							$result = array( $info->Truncate );
						} else {
							$result = array();
						}
					}
                   
          $items = str_split( $format );
          $digits = array_reverse( str_split( $info->Number ));
          
          foreach( $items as $token ) {
            switch ($token) {
  
              // area code or b digits
              case 'A':
              case 'B':
                $result[] = array_pop( $digits );
              break;

              // check for specific digits at specific position
              case '0':
              case '1':
              case '2':
              case '3':
              case '4':
              case '5':
              case '6':
              case '7':
              case '8':
              case '9':
                $char = array_pop( $digits );
                // if the digit hits on the formatting increase hit count
                if ($token==$char) {
                  $result[] = $token;
                  $hitCount+=100;
                } else {
                  // if digit does not hit gotmat mask drop out and don't use format
                  continue 3;
                }
              
              break;
  
              // allowed spacing tokens
              case ' ':
              case '(':
              case ')':
              case "'":
              case '.':
              case '-':
                $result[] = $token;
              break;            
            }
          }
          
          // check if we have more digits in number than in format. If so don't use format
          if (count($digits)>0) {
						continue 1;						
          }
          
          // store result to later find the best match
          $results[implode( '', $result )] = $hitCount;
        }
        
        // check if any of the formats worked
        if ( count($results)>0) {
          // sort to get mest possible format with highest hit rate
          arsort( $results );
          return key($results);
        }
      }

      // no format hit of no format was found for international code      
      return $aPhoneNumber;
    }
    
    // *************************************
    /**
     * get the phone number format depending on the country code
     * Source: https://en.wikipedia.org/wiki/National_conventions_for_writing_telephone_numbers
     * @param string $aCountryCode The country code to get the formatting for
     * @return array with code that matched and the number
     */
    static function getFormat( $aCountryCode ) {
      if (!isset( $aCountryCode )) {
        return false;
      }
    
      switch ($aCountryCode) {

        // United States, Canada, and other NANP countries
        case '1': return array( '(AAA) BBB-BBBB' );

        // Switzerland, https://en.wikipedia.org/wiki/Telephone_numbers_in_Switzerland
        case '41': return array( 'AA BBB BB BB', '800 BB BB BB', '84A BB BB BB', '90A BB BB BB' );

        // France, https://en.wikipedia.org/wiki/Telephone_numbers_in_France
        case '33': return array( 'A BB BB BB BB' );

        // Germany, https://en.wikipedia.org/wiki/Telephone_numbers_in_Germany
        case '49': return array( 'AAAA BBB-BBBB', '30 BBBB-BBBBB', '40 BBBB-BBBBB', '69 BBBB-BBBBB', '89 BBBB-BBBBB',
					'3AAAA BB-BBBB', '15AA-BBBBBBB', '16A-BBBBBBB', '17A-BBBBBBB', '180-BBBBBBB', '800-BBBBBBB', '900-A-BBBBBB' );
						
        // Denmark
        case '45': return array( 'BB BB BB BB' );

        // Austria, https://en.wikipedia.org/wiki/Telephone_numbers_in_Austria, http://www.searchyellowdirectory.com/reverse-phone/43/
        case '43': return array( 
          'AAAA BBBBBBBB', '6AA BBBBBBBB', '1 BBBBBBBBBB', '70 BBBBBBBB',
          '316 BBBBBBBB','463 BBBBBBBB','512 BBBBBBBB','662 BBBBBBBB','732 BBBBBBBB'
        );

				// Czech Republic, https://en.wikipedia.org/wiki/Telephone_numbers_in_the_Czech_Republic
				case '420': return array( 'AAA BBB BBB'	);

        // United Kingdom (Great Britain / UK), 
				case '44': return array( '1224 BBBBBB', '1244 BBBBBB', '1382 BBBBBB', '1387 BBBBBB', '1452 BBBBBB', '1429 BBBBBB', 
				  '1482 BBBBBB', '1539 BBBBBB', '1582 BBBBBB', '1670 BBBBBB', '1697 BBBBBB', '1730 BBBBBB', '1736 BBBBBB', '1772 BBBBBB',
 				  '1793 BBBBBB', '1854 BBBBBB', '1947 BBBBBB', '1AA AA BBBBB', '1AAA BBBBBB', '1AAA BBBBB', '1A1 BBB BBBB', '11A BBB BBBB',
					'2A BBBB BBBB', '3AA BBB BBBB', '55 BBBB BBBB', '56 BBBB BBBB', '70 BBBB BBBB', '7624 BBBBBB', '76 BBBB BBBB', '7AAA BBBBBB',
					'800 BBB BBBB', '8AA BBB BBBB', '9AA BBB BBBB', '169 77 BBBB', '500 BBBBBB', '800 BBBBBB', '800 1111', '845 46 4B' );
				
				// Serbia, 
				case '381': return array( 'AA BBB BB BB' );

				// Italy, https://en.wikipedia.org/wiki/Telephone_numbers_in_Italy, https://en.wikipedia.org/wiki/List_of_dialling_codes_in_Italy, http://www.wtng.info/wtng-39-it.html
				case '39': return array( 
				  'AAAA BBBBBBB', '3AA BBBBBBBB', '02 BBBBBBBBB', '06 BBBBBBBBB', '0A0 BBBBBBBB','0A1 BBBBBBBB', '0A5 BBBBBBBB', '0A9 BBBBBBBB' );
				  
				  //Mainland China, https://en.wikipedia.org/wiki/Telephone_numbers_in_China
				  case '86': return array(
				  'AAA BBB BBBB', //Calls from another area (with another area code) within Mainland China (Calls from outside China, Calls within the same area code)
				  'AAA BBBB BBBB',
				  '10 BBBB BBBB', // Beijing
				  '2A BBBB BBBB', ); // Area 2
				  
				  //Hong Kong, https://en.wikipedia.org/wiki/Telephone_numbers_in_Hong_Kong
				  case '852': return array(
				  'BBBB BBBB', );
				  //Taiwan, https://en.wikipedia.org/wiki/Telephone_numbers_in_Taiwan
				  case '886': return array(
				  '9BBBB BBBB', //Moble phones
				  'A BBBB BBBB',
				  '37 BBBB BBB',
				  '49 BBBB BBB',
				  '82 BBBB BBB',
				  '89 BBBB BBB',
				  '826 BBBB BB',
				  '836 BBBB BBB',
				  'AAAA BBB BB', );
				  //Macau, https://en.wikipedia.org/wiki/Telephone_numbers_in_Macau
				  case'853': return array(
				  'BBBB BBBB', );
				  //Spain, https://en.wikipedia.org/wiki/Telephone_numbers_in_Spain
				  case'34': return array(
				  '9BB BBB BBB', );
				  //Portugal, https://en.wikipedia.org/wiki/Telephone_numbers_in_Portugal
				  case '351': return array(
				  'AA BBB BBBB',
				  'AAA BBB BBBB', );
				  //Denmark, https://en.wikipedia.org/wiki/Telephone_numbers_in_Denmark
				  case '45': return array (
				  'AA BB BB BB', );
				  //Russia, https://en.wikipedia.org/wiki/Telephone_numbers_in_Russia
				  case '7': return array(
				  'AAA BBB-BB-BB', );
				  //Ukraine, https://en.wikipedia.org/wiki/Telephone_numbers_in_Ukraine
				  case '380': return array (
				  'AA-BBB-BB-BB',
				  'AAA-BBB-BBB',
				  'AAAA-BBB-BB', );
				  // South Korea, https://en.wikipedia.org/wiki/Telephone_numbers_in_South_Korea
				  case '82': return array (
				  'AA BBBB BBBB', );
				  //Australia, https://en.wikipedia.org/wiki/Telephone_numbers_in_Australia
				  case '61': return array (
				  '4BBBB BBBB', //mobile phone numbers
				  'A BBBB BBBB', );
				  //India, https://en.wikipedia.org/wiki/Telephone_numbers_in_India
				  case '91': return array (
				  'AA BBB BBBBB', //mobile phone numbers
				  'AAA-BBBBBBB',
				  	'11-BBBBBBBB', //New Delhi, Delhi
					'22-BBBBBBBB', // Mumbai, Maharashtra
					'33-BBBBBBBB', // Kolkata, West Bengal
					'44-BBBBBBBB', // Chennai, Tamil Nadu
					'20-BBBBBBBB', // Pune, Maharashtra
					'40-BBBBBBBB',  // Hyderabad, Telangana
					'79-BBBBBBBB', // Ahmedabad, Gujarat
					'80-BBBBBBBB', );// Bangalore, Karnataka
					
				
		  		  //Indonesia, https://en.wikipedia.org/wiki/Telephone_numbers_in_Indonesia
					case '62': return array (
					'AAA BBBB BBBB',
					'AAA BBB BBBB',
					'AA BBBBB BBBBB',
					'21 BBB BBBB',
					'21 BBBB BBBB',
					'22 BBB BBBB',
					'22 BBBB BBBB',
					'24 BBB BBBB',
					'24 BBBB BBBB',
					'31 BBB BBBB',
					'31 BBBB BBBB',
					'61 BBB BBBB',
					'61 BBBB BBBB', );
					
					//Brazil, https://en.wikipedia.org/wiki/Telephone_numbers_in_Brazil
					case '55': return array (
					'AA BBBB BBBB',
					'AA BBBBB BBBB', );
					
					//Bangladesh, https://en.wikipedia.org/wiki/Telephone_numbers_in_Bangladesh
					case '880': return array (
					'AA-BBBB-BBBB', );
					
					//Poland, https://en.wikipedia.org/wiki/Telephone_numbers_in_Poland
					case '48': return array (
					'AA BBB BB BB',
					'AAA BBB BBB', );
					
					//Canada, https://en.wikipedia.org/wiki/Telephone_numbers_in_Canada
					case '1': return array (
					'AAA BBB-BBBB', );
					
					//Belgium, https://en.wikipedia.org/wiki/Telephone_numbers_in_Belgium
					case '32': return array (
					'2-BBB-BB-BB',
					'3-BBB-BB-BB',
					'4-BBB-BB-BB',
					'AA-BBB-BBB',
					'4AA-BB-BB-BB', );
					
					//Netherlands, https://www.howtocallabroad.com/results.php?callfrom=switzerland&callto=netherlands
					case '31': return array (
					'A BBBB BBBB', //Mobile phone numbers
					'AA BBB BBBB',
					'AAA BBB BBB', );
					
					//Luxembourg, https://en.wikipedia.org/wiki/Telephone_numbers_in_Luxembourg
					case '352': return array (
					'BBB BBB',
					'BBB BBBB',
					'BBBB BBBB',
					'BBBBB BBBB',
					'6A1 BBB BBB', ); //mobile phone numbers
					
					//Iceland, https://en.wikipedia.org/wiki/Telephone_numbers_in_Iceland
					case '354': return array (
					'BBB BBBB', );
					
					//Liechtenstein, https://en.wikipedia.org/wiki/Telephone_numbers_in_Liechtenstein
					case '423': return array (
					'BBB BB BB', );
					
					//Greece, https://en.wikipedia.org/wiki/Telephone_numbers_in_Greece, https://www.howtocallabroad.com/results.php?callfrom=switzerland&callto=greece
					case '30': return array (
					'21 BBBB BBBB',
					'2A1 BBB BBBB',
					'2AAA BB BBBB',
					'6AA BBB BBBB', ); //mobile phone numbers
					
					//Ireland, https://en.wikipedia.org/wiki/National_conventions_for_writing_telephone_numbers#Ireland
					case '353': return array (
					'1 BBB BBBB', // Dublin
					'21 BBB BBBB', // Cork
					'64 BBB BBBB', //Killarney
					'61 BBB BBB', //Limerick
					'98 BBBBB', //Wesrport
					'404 BBBBB', //Wicklow
					'08A BBB BBBB', ); //mobile phone numbers
					
					//Norwegian, https://en.wikipedia.org/wiki/Telephone_numbers_in_Norway
					case '47': return array (
					'AA BB BB BB', // land line numbers
					'9BB BB BBB', //mobile phone numbers
					'4BB BB BBB',
					'58B BBB BBB BBB',
					'59B BB BBB', );
					
					//Turkey, https://en.wikipedia.org/wiki/National_conventions_for_writing_telephone_numbers#Turkey
					case '90': return array (
					'BBB AAA AA AA', );
					
					//New Zealand, https://en.wikipedia.org/wiki/Telephone_numbers_in_New_Zealand, https://www.howtocallabroad.com/results.php?callfrom=switzerland&callto=new_zealand, https://en.wikipedia.org/wiki/National_conventions_for_writing_telephone_numbers#NewZealand
					case '64': return array (
					'A BBB BBBB',
					'AA BBB BBBB',
					'AA BBB BBB',
					'AAA BBB BBB',
					'2AAA BBBB', //mobile phone numbers
					'2AAA BBBBB',
					'2AAA BBBBBB', );
					
					//Mexico, https://en.wikipedia.org/wiki/Telephone_numbers_in_Mexico
					case '52': return array (
					'AA BBBB BBBB',
					'AAA BBB BBBB',
					'1 AA BBBB BBBB',
					'1 AAA BBB BBBB', );
					
					//Philippines, https://en.wikipedia.org/wiki/Telephone_numbers_in_the_Philippines
					case '63': return array (
					'2-BBB-BBBB', //Metro Manila, Rizal, Bulacan (Obando), Cavite (Bacoor), Laguna (San Pedro)
					'AA-BBB-BBBB', //all other landlines
					'9AA-BBB-BBBB', ); //mobile phone numbers
					
					//Thailand, https://en.wikipedia.org/wiki/Telephone_numbers_in_Thailand
					case '66': return array (
					'2-BBBBBBB', //Bangkok
					'AA-BBBBBBB',
					'8A-BBBBBBB',
					'9A-BBBBBBB', );
					
					//South Africa, https://en.wikipedia.org/wiki/Telephone_numbers_in_South_Africa, https://www.howtocallabroad.com/results.php?callfrom=switzerland&callto=south_africa
					case '27': return array (
					'A BBB BBBB',
					'AA BBB BBBB', );
					
					//Romania, https://www.howtocallabroad.com/results.php?callfrom=switzerland&callto=romania
					case '40': return array (
					'7AA BBB BBB', //mobile phone numbers
					'21 BBB BBBB', //Bucharest
					'AAA BBB BBB', );
					
					//Malaysia, https://en.wikipedia.org/wiki/Telephone_numbers_in_Malaysia
					case '60': return array (
					'A-BBB BBBB',
					'8A-BBB BBB',
					'1A-BBB BBBB',
					'1A-BBBB BBBB', );
					//Singapore, https://en.wikipedia.org/wiki/Telephone_numbers_in_Singapore
					case '65': return array (
					'BBBB BBBB', );
								
				 
				 //Japan, https://en.wikipedia.org/wiki/Telephone_numbers_in_Japan
				  case '81': return array (
				  '70 BBBB BBBB', //mobile phone numbers
				  '80 BBBB BBBB',
				  '90 BBBB BBBB',
				  'AA BBB BBBB', //landlines (larger cities)
				  '3 BBBB BBBB', // Tokyo
				  '6 BBBB BBBB', ); //Osaka, Higashiōsaka, Suita, Toyonaka, Amagasaki
      }
      return false;
    }
    
    // *************************************
    /**
     * Strip country code 
     * @param string $aNumber The phone number
     * @return array with code that matched and the number
     */
    static function stripCountryCode( $aNumber ) {
      if (!isset( self::$countryCodes )) {
        self::prepareCountryCodes();
      }
      
      $result = array();
      
      foreach( self::$countryCodes as $count=>$codes ) {
        
        // check if we have a match on 1-2 or 3 digit country code
        foreach( $codes as $code=>$dummy ) {
          if (substr( $aNumber, 0, $count) == $code ) {
            $result['code'] = $code;
            $result['number'] = substr( $aNumber, $count );
            return $result;
          }
        }
      }
      return array( 'number'=>$aNumber );
    }
    
    // *************************************
    /**
     * Prepare the country codes based on international list
     * @return void
     */
    static function prepareCountryCodes() {
      // downloaded from http://www.pier2pier.com/links/files/Countrystate/Country-codes.xls

      // ADJUST THIS LIST IF NEEDED      
      $countryCodes['+93'] = true;
      $countryCodes['+1'] = true;
      $countryCodes['+1-242'] = true;
      $countryCodes['+1-246'] = true;
      $countryCodes['+1-264'] = true;
      $countryCodes['+1-268'] = true;
      $countryCodes['+1-284'] = true;
      $countryCodes['+1-340'] = true;
      $countryCodes['+1-345'] = true;
      $countryCodes['+1-441'] = true;
      $countryCodes['+1-473'] = true;
      $countryCodes['+1-649'] = true;
      $countryCodes['+1-664'] = true;
      $countryCodes['+1-670'] = true;
      $countryCodes['+1-671'] = true;
      $countryCodes['+1-684'] = true;
      $countryCodes['+1-758'] = true;
      $countryCodes['+1-767'] = true;
      $countryCodes['+1-784'] = true;
      $countryCodes['+1-787'] = true;
      $countryCodes['+1-809'] = true;
      $countryCodes['+1-829'] = true;
      $countryCodes['+1-868'] = true;
      $countryCodes['+1-869'] = true;
      $countryCodes['+1-876'] = true;
      $countryCodes['+1-939'] = true;
      $countryCodes['+20'] = true;
      $countryCodes['+212'] = true;
      $countryCodes['+213'] = true;
      $countryCodes['+216'] = true;
      $countryCodes['+218'] = true;
      $countryCodes['+220'] = true;
      $countryCodes['+221'] = true;
      $countryCodes['+222'] = true;
      $countryCodes['+223'] = true;
      $countryCodes['+224'] = true;
      $countryCodes['+225'] = true;
      $countryCodes['+226'] = true;
      $countryCodes['+227'] = true;
      $countryCodes['+229'] = true;
      $countryCodes['+230'] = true;
      $countryCodes['+231'] = true;
      $countryCodes['+232'] = true;
      $countryCodes['+233'] = true;
      $countryCodes['+234'] = true;
      $countryCodes['+235'] = true;
      $countryCodes['+236'] = true;
      $countryCodes['+237'] = true;
      $countryCodes['+238'] = true;
      $countryCodes['+239'] = true;
      $countryCodes['+240'] = true;
      $countryCodes['+241'] = true;
      $countryCodes['+242'] = true;
      $countryCodes['+243'] = true;
      $countryCodes['+244'] = true;
      $countryCodes['+245'] = true;
      $countryCodes['+248'] = true;
      $countryCodes['+249'] = true;
      $countryCodes['+250'] = true;
      $countryCodes['+251'] = true;
      $countryCodes['+252'] = true;
      $countryCodes['+253'] = true;
      $countryCodes['+254'] = true;
      $countryCodes['+255'] = true;
      $countryCodes['+256'] = true;
      $countryCodes['+257'] = true;
      $countryCodes['+258'] = true;
      $countryCodes['+260'] = true;
      $countryCodes['+261'] = true;
      $countryCodes['+262'] = true;
      $countryCodes['+263'] = true;
      $countryCodes['+264'] = true;
      $countryCodes['+265'] = true;
      $countryCodes['+266'] = true;
      $countryCodes['+267'] = true;
      $countryCodes['+268'] = true;
      $countryCodes['+269'] = true;
      $countryCodes['+27'] = true;
      $countryCodes['+290'] = true;
      $countryCodes['+291'] = true;
      $countryCodes['+297'] = true;
      $countryCodes['+298'] = true;
      $countryCodes['+299'] = true;
      $countryCodes['+30'] = true;
      $countryCodes['+31'] = true;
      $countryCodes['+32'] = true;
      $countryCodes['+33'] = true;
      $countryCodes['+34'] = true;
      $countryCodes['+350'] = true;
      $countryCodes['+351'] = true;
      $countryCodes['+352'] = true;
      $countryCodes['+353'] = true;
      $countryCodes['+354'] = true;
      $countryCodes['+355'] = true;
      $countryCodes['+356'] = true;
      $countryCodes['+357'] = true;
      $countryCodes['+358'] = true;
      $countryCodes['+359'] = true;
      $countryCodes['+36'] = true;
      $countryCodes['+370'] = true;
      $countryCodes['+371'] = true;
      $countryCodes['+372'] = true;
      $countryCodes['+373'] = true;
      $countryCodes['+374'] = true;
      $countryCodes['+375'] = true;
      $countryCodes['+376'] = true;
      $countryCodes['+377'] = true;
      $countryCodes['+378'] = true;
      $countryCodes['+380'] = true;
      $countryCodes['+381'] = true;
      $countryCodes['+382'] = true;
      $countryCodes['+385'] = true;
      $countryCodes['+386'] = true;
      $countryCodes['+387'] = true;
      $countryCodes['+389'] = true;
      $countryCodes['+39'] = true;
      $countryCodes['+40'] = true;
      $countryCodes['+41'] = true;
      $countryCodes['+418'] = true;
      $countryCodes['+420'] = true;
      $countryCodes['+421'] = true;
      $countryCodes['+423'] = true;
      $countryCodes['+43'] = true;
      $countryCodes['+44'] = true;
      $countryCodes['+45'] = true;
      $countryCodes['+46'] = true;
      $countryCodes['+47'] = true;
      $countryCodes['+48'] = true;
      $countryCodes['+49'] = true;
      $countryCodes['+500'] = true;
      $countryCodes['+501'] = true;
      $countryCodes['+502'] = true;
      $countryCodes['+503'] = true;
      $countryCodes['+504'] = true;
      $countryCodes['+505'] = true;
      $countryCodes['+506'] = true;
      $countryCodes['+507'] = true;
      $countryCodes['+508'] = true;
      $countryCodes['+509'] = true;
      $countryCodes['+51'] = true;
      $countryCodes['+52'] = true;
      $countryCodes['+53'] = true;
      $countryCodes['+54'] = true;
      $countryCodes['+55'] = true;
      $countryCodes['+56'] = true;
      $countryCodes['+57'] = true;
      $countryCodes['+58'] = true;
      $countryCodes['+590'] = true;
      $countryCodes['+591'] = true;
      $countryCodes['+592'] = true;
      $countryCodes['+593 '] = true;
      $countryCodes['+594'] = true;
      $countryCodes['+595'] = true;
      $countryCodes['+596'] = true;
      $countryCodes['+597'] = true;
      $countryCodes['+598'] = true;
      $countryCodes['+599'] = true;
      $countryCodes['+60'] = true;
      $countryCodes['+61'] = true;
      $countryCodes['+62'] = true;
      $countryCodes['+63'] = true;
      $countryCodes['+64'] = true;
      $countryCodes['+65'] = true;
      $countryCodes['+66'] = true;
      $countryCodes['+670'] = true;
      $countryCodes['+672'] = true;
      $countryCodes['+673'] = true;
      $countryCodes['+674'] = true;
      $countryCodes['+675'] = true;
      $countryCodes['+676'] = true;
      $countryCodes['+677'] = true;
      $countryCodes['+678'] = true;
      $countryCodes['+679'] = true;
      $countryCodes['+680'] = true;
      $countryCodes['+681'] = true;
      $countryCodes['+682'] = true;
      $countryCodes['+683'] = true;
      $countryCodes['+685'] = true;
      $countryCodes['+686'] = true;
      $countryCodes['+687'] = true;
      $countryCodes['+688'] = true;
      $countryCodes['+689'] = true;
      $countryCodes['+690'] = true;
      $countryCodes['+691'] = true;
      $countryCodes['+692'] = true;
      $countryCodes['+7'] = true;
      $countryCodes['+81'] = true;
      $countryCodes['+82'] = true;
      $countryCodes['+84'] = true;
      $countryCodes['+850'] = true;
      $countryCodes['+852'] = true;
      $countryCodes['+853'] = true;
      $countryCodes['+855'] = true;
      $countryCodes['+856'] = true;
      $countryCodes['+86'] = true;
      $countryCodes['+880'] = true;
      $countryCodes['+886'] = true;
      $countryCodes['+90'] = true;
      $countryCodes['+91'] = true;
      $countryCodes['+92'] = true;
      $countryCodes['+94'] = true;
      $countryCodes['+95'] = true;
      $countryCodes['+960'] = true;
      $countryCodes['+961'] = true;
      $countryCodes['+962'] = true;
      $countryCodes['+963'] = true;
      $countryCodes['+964'] = true;
      $countryCodes['+965'] = true;
      $countryCodes['+966'] = true;
      $countryCodes['+967'] = true;
      $countryCodes['+968'] = true;
      $countryCodes['+970'] = true;
      $countryCodes['+971'] = true;
      $countryCodes['+972'] = true;
      $countryCodes['+973'] = true;
      $countryCodes['+974 '] = true;
      $countryCodes['+975'] = true;
      $countryCodes['+976'] = true;
      $countryCodes['+977'] = true;
      $countryCodes['+98'] = true;
      $countryCodes['+992'] = true;
      $countryCodes['+993'] = true;
      $countryCodes['+994'] = true;
      $countryCodes['+995'] = true;
      $countryCodes['+996'] = true;
      $countryCodes['+998'] = true;
      
      foreach ( $countryCodes as $code => $dummy ) {
        if ( $pos = strpos( $code, '-' ) ) {
          $code = substr( $code, 0, $pos );
        }
        $code = substr( trim( $code ), 1 );
        
        self::$countryCodes[ strlen( $code ) ][$code] = true;
      }
      ksort( self::$countryCodes );
    }
    
    
    // *************************************
    /**
     * Get a prefix to truncate based on country code
     * Source: https://en.wikipedia.org/wiki/Trunk_prefix
     * @return void
     */
    static function getTruncatePrefix( $aCountryCode ) {
      switch( $aCountryCode ) {
        // Egypt - 0
        case '20':
        
        // Morocco - 0
        case '212':

        // Kenya - 0
        case '254':
        
        // South Africa - 0
        case '27':
        
        // Tanzania - 0
        case '255':
        
        // Rwanda - 0
        case '250':
        
        // Nigeria - 0
        case '234':
        
        // Argentina - 0
        case '54':
        
        // Bolivia
        case '591':
        
        // Brazil - 0
        case '55':
        
        // Peru - 0
        case '51':
        
        // Venezuela - 0
        case '58':
        
        // Afghanistan - 0
        case '93':
        
        // Bangladesh - 0
        case '880':
        
        // Burma - 0
        case '95':
        
        // Cambodia - 0
        case '855':
        
        // China - 0
        case '86':
        
        // India - 0
        case '91':
        
        // Indonesia - 0
        case '62':
        
        // Iran - 0
        case '98':
        
        // Israel - 0
        case '972':
        
        // Japan - 0
        case '81':
        
        // Jordan - 0
        case '962':
        
        // Korea, South - 0
        case '82':
        
        // Laos - 0
        case '856':
        
        // Malaysia - 0
        case '60':
        
        // Pakistan - 0
        case '92':
        
        // Philippines - 0
        case '63':
        
        // Taiwan - 0
        case '886':
        
        // Thailand - 0
        case '66':
        
        // Vietnam - 0
        case '84':
        
        // UAE - 0
        case '971':
        
        // KSA - 0
        case '966':
        
        // Albania - 0
        case '355':
        
        // Austria - 0
        case '43':

        // Belgium - 0
        case '32':
        
        // Bosnia and Herzegovina - 0
        case '387':
        
        // Bulgaria - 0
        case '359':
        
        // Croatia - 0
        case '385':
        
        // Cyprus - 0
        case '357':
        
        // Finland - 0
        case '358':
        
        // France - 0
        case '33':
        
        // Georgia - 0
        case '995':
        
        // Germany - 0
        case '49':
        
        // Macedonia - 0
        case '389':
        
        // Moldova - 0
        case '373':
        
        // Montenegro - 0
        case '382':
        
        // The Netherlands - 0
        case '31':
        
        // Romania - 0
        case '40':
        
        // Ireland - 0
        case '353':
        
        // Slovakia - 0
        case '421':
        
        // Slovenia - 0
        case '386':
        
        // Sweden - 0
        case '46':
        
        // Switzerland - 0
        case '41':
        
        // Turkey - 0
        case '90':
        
        // Ukraine - 0
        case '380':
        
        // United Kingdom - 0
        case '44':
        
        // Australia - 0
        case '61':
        
        // New Zealand - 0
        case '64':
        
        // Serbia - 0
        case '381':
        
          return array('0');
          
        // Azerbaijan - 8
        case '994':
        
        // Kazakhstan - 8
        // Russia - 8
        case '7':
        
        // Korea, North
        case '850':
        
        // Turkmenistan - 8
        case '993':
        
        // Uzbekistan - 8
        case '998':
        
        // Belarus - 8
        case '375':
        
          return array('8');


        // Lithuania - 8 (planned to be 0 later)
        case '370':
        
          return array('8','0');


        // Mexico - 01
        case '52':

          return array('01');


        // Mongolia - 01 or 02
        case '976':

          return array('01','02');


        // Hungary - 06
        case '36':

          return array('06');
          

        // North American Numbering Plan - 1
/*        case '1':

          return array('1');*/
          
      }
      
      return false;
    }
  }


?>