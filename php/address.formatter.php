<?php
//====================================================================================
/** 
 * Address formatter for international addresses
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================
  
  namespace address;
	
	define( 'afdDefaultBoxLabel', 'PO Box' );

  //====================================================================================
  /**
   * Data for address formatter
   * Responsibilities: Hold the data for on address
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */	 
  class AddressFormatterData{
    /** Name of businedd */
    var $Business = '';

    /** Title or salutation of contact */
    var $Title = '';

    /** First name of contact */
    var $Firstname = '';

    /** Last name of contact */
    var $Lastname = '';

    /** PO box label */
    var $BoxLabel = afdDefaultBoxLabel;

    /** PO box number */
    var $BoxNumber = '';

    /** Street name */
    var $Street = '';

    /** Street number */
    var $StreetNumber = '';

    /** Postal code */
    var $Zip = '';

    /** City name */
    var $City = '';

    /** State information as printed on address, mostly used is abbreviation */
    var $State = '';

    /** Country code to detect country */
    var $CountryISO2 = '';

    /** Country name in upper case */
    var $EnglishCountryName = '';
		
    /** Static list of labels for PO box*/
		static $Labels = NULL;

    // *************************************
    /**
     * Set the country name based on ISO2 country code
     * @return void
     */
    function setEnlishCountryName() {
			$this->EnglishCountryName = mb_strtoupper( 
			  AddressFormatter::getEnglishCountryName( $this->CountryISO2 ));							
		}

    // *************************************
    /**
     * Set the box lael based on the detailed location data
     * @return void
     */
    function setPoBoxLabel() {

      // assemble data. 			
			$data = strtoupper( 
				substr( $this->CountryISO2.'  ', 0, 2 ).'.'.
				substr( $this->State.'  ', 0, 2 ).'.'.
				$this->Zip );
				
			if (strlen($data)<=0) return;
			
			// load the labels
			self::loadLabels();
			
			$hitList = array();
			
      foreach( self::$Labels as $mask => $label ) {
				
				$hits = 0;

				// move forward if mask too short
				if (strlen($data)<>strlen($mask)) {
					continue;
				}
				
				// check mask
				for( $i=0; $i<strlen($mask); $i++ ) {
					// check # place holder
					if( $mask[$i]== '#' ) {
						if ($data[$i]=='.') {
							continue 2;
						} 
					// check . separator
					} else 
					if( $mask[$i]== '.' ) {
						if ($data[$i]!='.') {
							continue 2;
						} 
					// check data
					} else {
  				  if ($mask[$i] == $data[$i]) {
							// Still a hit on the format. Keep processing
							$hits++;
						} else {
							// no hit on the format. Stop processing the format
							continue 2;							
						}
					}
					
				}
  			// check if end is reached.
				$hitList[$mask] = $hits;
			}
			
			// check if we have hits, otherwise use default
			if (count($hitList)<=0) {
				$this->BoxLabel = afdDefaultBoxLabel;
			} else {
  			arsort( $hitList );
				$this->BoxLabel = self::$Labels[ key( $hitList ) ];
			}
		}
		
    // *************************************
    /**
     * Load the lables for the PO boxes depending on the address information
     * Filter CC.SS.ZZZZZ. CC = Country ISO2, SS state abbreviation, ZZ=Sipy code digits
     * @return void
     */		
		static function loadLabels() {
			// check if already loaded
			if (self::$Labels != NULL) return;

      // german
			self::$Labels[ 'CH.##.####' ] = 'Postfach';
			self::$Labels[ 'DE.##.#####' ] = 'Postfach';
			
			// french
			self::$Labels[ 'CH.##.1###' ] = 'Case postale';
			self::$Labels[ 'CH.##.2###' ] = 'Case postale';

      // italien
			self::$Labels[ 'CH.##.65##' ] = 'Casella postale';
			self::$Labels[ 'CH.##.66##' ] = 'Casella postale';
			self::$Labels[ 'CH.##.67##' ] = 'Casella postale';
			self::$Labels[ 'CH.##.68##' ] = 'Casella postale';
			self::$Labels[ 'CH.##.69##' ] = 'Casella postale';
		}
	}


  //====================================================================================
  /**
   * International address formatter
   * Responsibilities: Format an address by the respective national standard
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */	 
  class AddressFormatter{
		
    // *************************************
    /**
     * Format the given address data
     * @param AddressFormatterData $aTermaAddressDataThe The address to be formatted
     * @return array returns the lines of the address
     */
    static function format( $aAddressData ) {
			$format = self::getCountryFormat( $aAddressData->CountryISO2 );
			
			// setup dynamic data based on address
			$aAddressData->setPoBoxLabel();
			$aAddressData->setEnlishCountryName();
			
      // blank data. As the box label is statis make sure the value is the same.
			// when compared it will be eliminated
			$blankData = new AddressFormatterData();
			$blankData->BoxLabel = $aAddressData->BoxLabel;

      // create 2 formats with blank and real values
			$formatLines = self::replaceTokens( $format, $aAddressData );
			$formatBlankLines = self::replaceTokens( $format, $blankData );

      // compare blank replacement with actual lines. If the same no data was included.
      for ($i=count($formatLines)-1;$i>=0;$i--) {
				if ( isset($formatBlankLines[$i]) && $formatLines[$i] == $formatBlankLines[$i] ) {
					unset( $formatLines[$i] );
				} else {
					$formatLines[$i] = trim( $formatLines[$i] );
				}
			}

      return $formatLines;
		}
		
		static function replaceTokens( $aFormat, $aAddressData ) {
			// format string an break into data
			return explode( "\n", preg_replace_callback_array(
				[
					'#\[(business)\]#' => function (&$match) use ($aAddressData) { return $aAddressData->Business; },
					'#\[(title)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->Title; },
					'#\[(firstname)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->Firstname; },
					'#\[(lastname)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->Lastname; },
					'#\[(box-label)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->BoxLabel; },
					'#\[(box-number)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->BoxNumber; },
					'#\[(street)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->Street; },
					'#\[(street-number)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->StreetNumber; },
					'#\[(zip)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->Zip; },
					'#\[(city)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->City; },
					'#\[(state)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->State; },
					'#\[(country)\]#' => function (&$match) use ($aAddressData) {	return $aAddressData->EnglishCountryName; }
				],
				$aFormat
			));
		}

    // *************************************
    /**
     * Get the country specific address format
     * @param string $aCountryISO2 The country code in ISO2 format
     * @return string
     */
    static function getCountryFormat( $aCountryISO2 ) {
			switch( $aCountryISO2 ) {
				case 'CH':
				  return
						"[business]\n".
						"[title] [firstname] [lastname]\n".
						"[box-label] [box-number]\n".
						"[street] [street-number]\n".
						"[zip] [city]\n".
						"[country]";

				case 'DE':
				  return
						"[business]\n".
						"[title] [firstname] [lastname]\n".
						"[box-label] [box-number]\n".
						"[street] [street-number]\n".
						"[zip] [city]\n".
						"[country]";

				case 'UK':
				  return
						"[business]\n".
						"[title] [firstname] [lastname]\n".
						"[box-number] [box-label]\n".
						"[city]\n".
						"[zip]\n".
						"[country]";

				case 'FR':
				  return
						"[business]\n".
						"[title] [firstname] [lastname]\n".
						"[box-label] [box-number]\n".
						"[street-number] [street]\n".
						"[zip] [city]\n".
						"[country]";

				case 'US':
				  return
						"[business]\n".
						"[title] [firstname] [lastname]\n".
						"[box-label] [box-number]\n".
						"[street-number] [street]\n".
						"[city], [state] [zip]\n".
						"[country]";

			}			

			return
				"[business]\n".
				"[title] [firstname] [lastname]\n".
				"[box-label] [box-number]\n".
				"[street-number] [street]\n".
				"[zip] [city]\n".
				"[country]";
		}


    // *************************************
    /**
     * Get the country name in english
     * @param string $aCountryISO2 The country code in ISO2 format
     * @return string
     */
    static function getEnglishCountryName( $aCountryISO2 ) {
			switch( $aCountryISO2 ) {
				case "AD": return "Andorra";
				case "AE": return "United Arab Emirates";
				case "AF": return "Afghanistan";
				case "AG": return "Antigua and Barbuda";
				case "AI": return "Anguilla";
				case "AL": return "Albania";
				case "AM": return "Armenia";
				case "AO": return "Angola";
				case "AQ": return "Antarctica";
				case "AR": return "Argentina";
				case "AS": return "American Samoa";
				case "AT": return "Austria";
				case "AU": return "Australia";
				case "AW": return "Aruba";
				case "AX": return "Åland Islands";
				case "AZ": return "Azerbaijan";
				case "BA": return "Bosnia and Herzegovina";
				case "BB": return "Barbados";
				case "BD": return "Bangladesh";
				case "BE": return "Belgium";
				case "BF": return "Burkina Faso";
				case "BG": return "Bulgaria";
				case "BH": return "Bahrain";
				case "BI": return "Burundi";
				case "BJ": return "Benin";
				case "BL": return "Saint Barthélemy";
				case "BM": return "Bermuda";
				case "BN": return "Brunei Darussalam";
				case "BO": return "Bolivia";
				case "BQ": return "Bonaire, Sint Eustatius and Saba";
				case "BR": return "Brazil";
				case "BS": return "Bahamas";
				case "BT": return "Bhutan";
				case "BV": return "Bouvet Island";
				case "BW": return "Botswana";
				case "BY": return "Belarus";
				case "BZ": return "Belize";
				case "CA": return "Canada";
				case "CC": return "Cocos (Keeling) Islands";
				case "CD": return "Congo";
				case "CF": return "Central African Republic";
				case "CG": return "Congo";
				case "CH": return "Switzerland";
				case "CI": return "Côte d'Ivoire";
				case "CK": return "Cook Islands";
				case "CL": return "Chile";
				case "CM": return "Cameroon";
				case "CN": return "China";
				case "CO": return "Colombia";
				case "CR": return "Costa Rica";
				case "CU": return "Cuba";
				case "CV": return "Cabo Verde";
				case "CW": return "Curaçao";
				case "CX": return "Christmas Island";
				case "CY": return "Cyprus";
				case "CZ": return "Czechia";
				case "DE": return "Germany";
				case "DJ": return "Djibouti";
				case "DK": return "Denmark";
				case "DM": return "Dominica";
				case "DO": return "Dominican Republic";
				case "DZ": return "Algeria";
				case "EC": return "Ecuador";
				case "EE": return "Estonia";
				case "EG": return "Egypt";
				case "EH": return "Western Sahara";
				case "ER": return "Eritrea";
				case "ES": return "Spain";
				case "ET": return "Ethiopia";
				case "FI": return "Finland";
				case "FJ": return "Fiji";
				case "FK": return "Falkland Islands (Malvinas)";
				case "FM": return "Federated States of Micronesia";
				case "FO": return "Faroe Islands";
				case "FR": return "France";
				case "GA": return "Gabon";
				case "GB": return "United Kingdom of Great Britain";
				case "GD": return "Grenada";
				case "GE": return "Georgia";
				case "GF": return "French Guiana";
				case "GG": return "Guernsey";
				case "GH": return "Ghana";
				case "GI": return "Gibraltar";
				case "GL": return "Greenland";
				case "GM": return "Gambia";
				case "GN": return "Guinea";
				case "GP": return "Guadeloupe";
				case "GQ": return "Equatorial Guinea";
				case "GR": return "Greece";
				case "GS": return "South Georgia and the South Sandwich Islands";
				case "GT": return "Guatemala";
				case "GU": return "Guam";
				case "GW": return "Guinea-Bissau";
				case "GY": return "Guyana";
				case "HK": return "Hong Kong";
				case "HM": return "Heard Island and McDonald Islands";
				case "HN": return "Honduras";
				case "HR": return "Croatia";
				case "HT": return "Haiti";
				case "HU": return "Hungary";
				case "ID": return "Indonesia";
				case "IE": return "Ireland";
				case "IL": return "Israel";
				case "IM": return "Isle of Man";
				case "IN": return "India";
				case "IO": return "British Indian Ocean Territory";
				case "IQ": return "Iraq";
				case "IR": return "Iran";
				case "IS": return "Iceland";
				case "IT": return "Italy";
				case "JE": return "Jersey";
				case "JM": return "Jamaica";
				case "JO": return "Jordan";
				case "JP": return "Japan";
				case "KE": return "Kenya";
				case "KG": return "Kyrgyzstan";
				case "KH": return "Cambodia";
				case "KI": return "Kiribati";
				case "KM": return "Comoros";
				case "KN": return "Saint Kitts and Nevis";
				case "KP": return "People's Democratic Republic of Korea";
				case "KR": return "Republic of Korea";
				case "KW": return "Kuwait";
				case "KY": return "Cayman Islands";
				case "KZ": return "Kazakhstan";
				case "LA": return "Lao";
				case "LB": return "Lebanon";
				case "LC": return "Saint Lucia";
				case "LI": return "Liechtenstein";
				case "LK": return "Sri Lanka";
				case "LR": return "Liberia";
				case "LS": return "Lesotho";
				case "LT": return "Lithuania";
				case "LU": return "Luxembourg";
				case "LV": return "Latvia";
				case "LY": return "Libya";
				case "MA": return "Morocco";
				case "MC": return "Monaco";
				case "MD": return "Republic of Moldova";
				case "ME": return "Montenegro";
				case "MF": return "Saint Martin";
				case "MG": return "Madagascar";
				case "MH": return "Marshall Islands";
				case "MK": return "Macedonia";
				case "ML": return "Mali";
				case "MM": return "Myanmar";
				case "MN": return "Mongolia";
				case "MO": return "Macao";
				case "MP": return "Northern Mariana Islands";
				case "MQ": return "Martinique";
				case "MR": return "Mauritania";
				case "MS": return "Montserrat";
				case "MT": return "Malta";
				case "MU": return "Mauritius";
				case "MV": return "Maldives";
				case "MW": return "Malawi";
				case "MX": return "Mexico";
				case "MY": return "Malaysia";
				case "MZ": return "Mozambique";
				case "NA": return "Namibia";
				case "NC": return "New Caledonia";
				case "NE": return "Niger";
				case "NF": return "Norfolk Island";
				case "NG": return "Nigeria";
				case "NI": return "Nicaragua";
				case "NL": return "Netherlands";
				case "NO": return "Norway";
				case "NP": return "Nepal";
				case "NR": return "Nauru";
				case "NU": return "Niue";
				case "NZ": return "New Zealand";
				case "OM": return "Oman";
				case "PA": return "Panama";
				case "PE": return "Peru";
				case "PF": return "French Polynesia";
				case "PG": return "Papua New Guinea";
				case "PH": return "Philippines";
				case "PK": return "Pakistan";
				case "PL": return "Poland";
				case "PM": return "Saint Pierre and Miquelon";
				case "PN": return "Pitcairn";
				case "PR": return "Puerto Rico";
				case "PS": return "Palestine";
				case "PT": return "Portugal";
				case "PW": return "Palau";
				case "PY": return "Paraguay";
				case "QA": return "Qatar";
				case "RE": return "Réunion";
				case "RO": return "Romania";
				case "RS": return "Serbia";
				case "RU": return "Russia";
				case "RW": return "Rwanda";
				case "SA": return "Saudi Arabia";
				case "SB": return "Solomon Islands";
				case "SC": return "Seychelles";
				case "SD": return "Sudan";
				case "SE": return "Sweden";
				case "SG": return "Singapore";
				case "SH": return "Saint Helena";
				case "SI": return "Slovenia";
				case "SJ": return "Svalbard and Jan Mayen";
				case "SK": return "Slovakia";
				case "SL": return "Sierra Leone";
				case "SM": return "San Marino";
				case "SN": return "Senegal";
				case "SO": return "Somalia";
				case "SR": return "Suriname";
				case "SS": return "South Sudan";
				case "ST": return "Sao Tome and Principe";
				case "SV": return "El Salvador";
				case "SX": return "Sint Maarten";
				case "SY": return "Syrian Arab Republic";
				case "SZ": return "Swaziland";
				case "TC": return "Turks and Caicos Islands";
				case "TD": return "Chad";
				case "TF": return "French Southern Territories";
				case "TG": return "Togo";
				case "TH": return "Thailand";
				case "TJ": return "Tajikistan";
				case "TK": return "Tokelau";
				case "TL": return "Timor-Leste";
				case "TM": return "Turkmenistan";
				case "TN": return "Tunisia";
				case "TO": return "Tonga";
				case "TR": return "Turkey";
				case "TT": return "Trinidad and Tobago";
				case "TV": return "Tuvalu";
				case "TW": return "Taiwan, Province of China";
				case "TZ": return "Tanzania, United Republic of";
				case "UA": return "Ukraine";
				case "UG": return "Uganda";
				case "UM": return "United States Minor Outlying Islands";
				case "US": return "U.S.A.";
				case "UY": return "Uruguay";
				case "UZ": return "Uzbekistan";
				case "VA": return "Holy See";
				case "VC": return "Saint Vincent and the Grenadines";
				case "VE": return "Venezuela";
				case "VG": return "British Virgin Islands";
				case "VI": return "U.S. Virgin Islands";
				case "VN": return "Viet Nam";
				case "VU": return "Vanuatu";
				case "WF": return "Wallis and Futuna";
				case "WS": return "Samoa";
				case "YE": return "Yemen";
				case "YT": return "Mayotte";
				case "ZA": return "South Africa";
				case "ZM": return "Zambia";
				case "ZW": return "Zimbabwe";
			}
			return "";
		}
	}	

?>