/**
 * Creates a new SearchAutoComplete class to manage search auto complete
 * @class Represents a SearchAutoComplete 
 * @constructor
 */ 
var SearchAutoComplete = function( aPreferredConcept ){

	/**
	 * The MINIMUM search string length
	 */
	this.CONFIG_MIN_TERM_LENGTH = 3;

	/**
	 * The DELAY since last keystroke until ajax is triggered to fetch search results
	 * This will keep the server from getting "hammered"
	 */
  this.CONFIG_AJAX_DELAY = 200;

	/**
	 * The preferred concept ID
	 */
	this.PreferredConcept = aPreferredConcept;

	/**
	 * The search input element
	 */
	this.ElementInput = document.getElementById( 'search' );

	/**
	 * The auto complete drop down
	 */
	this.ElementDropDown = document.getElementById( 'search-autocomplete' );

	/**
	 * The box of the search
	 */
	this.ElementBox = document.getElementById( 'search-box' );

	/**
	 * The pointer to the element list of the dropdown
	 */
  this.DropDownElementList = null;
	
	/**
	 * Flag to indicate if progress is displayed
	 */
	this.ProgressDisplay = false;

	/**
	 * Key event semaphor 
	 */
	this.KeyEventSemaphor = 0;

  // setup singleton on window object
	window.SearchAutoComplete = this;
	
  // initialize the autocomplete handler	
  this.initialize();
}

SearchAutoComplete.prototype = {

	/**
	 * Initialize the auto complete
	 * @private
	 */
  initialize: function() {

		window.SearchAutoComplete.ElementInput.onkeyup = function( aEvent ){
	    window.SearchAutoComplete.onKeyUp( aEvent );
		}
		
		// box focus for glow
		window.SearchAutoComplete.ElementInput.onfocus = function( aEvent ){
			window.SearchAutoComplete.ElementBox.classList.add('quick-search-glow');
		}
		
		// losing focus on box
		window.SearchAutoComplete.ElementInput.onblur = function( aEvent ){
			window.SearchAutoComplete.ElementBox.classList.remove('quick-search-glow');
		}
		
	},
	
	/**
	 * Key press event
	 * @private
	 */
	onKeyUp: function( aEvent ) {
		switch (aEvent.keyCode) {
			// arrow down is clicked
			case 40:;
			  if ( !this.DropDownElementList ) return;
			  if ( this.SelectedIndex<this.DropDownElementList.length-1) {
  			  this.setSelection( ++this.SelectedIndex );
				} else {
  			  this.setSelection( 0 );
				}
      break;

			// arrow up is clicked
			case 38:;
			  if ( !this.DropDownElementList ) return;
			  if (this.SelectedIndex>0){
  			  this.setSelection( --this.SelectedIndex );
				} else {
  			  this.setSelection( this.DropDownElementList.length-1 );
				}
			break;
			
			// ESC is clicked
			case 27:
  			this.ElementDropDown.style.display = 'none';
				this.ElementInput.value = '';
			break;

			// enter was hit
			case 13:
    		window.SearchAutoComplete.KeyEventSemaphor = 1;
				window.SearchAutoComplete.processKeyPress();
			break;

			// all other keys
			default:
			
			  // check if anything changed
				var value = window.SearchAutoComplete.ElementInput.value.trim();
				if ( value.length >= this.CONFIG_MIN_TERM_LENGTH ) {
				  if ( window.SearchAutoComplete.lastValue != value ) {
						window.SearchAutoComplete.KeyEventSemaphor++;
						window.SearchAutoComplete.displayProgress();
						window.setTimeout( 
							function () {
								window.SearchAutoComplete.processKeyPress();
							},
							this.CONFIG_AJAX_DELAY
						);
					}					
				} else {
					this.DropDownElementList = null;
					this.ElementDropDown.style.display = 'none';					
				}        
				window.SearchAutoComplete.lastValue = value;
		}
	},
	
	/**
	 * process the delayed key press. This is done to only triggere an ajax 50ms after the last key was pressed
	 * @private
	 */
	processKeyPress: function() {
		window.SearchAutoComplete.KeyEventSemaphor--;

    // bail if stuck events are fired after enter was hit
		if (window.SearchAutoComplete.KeyEventSemaphor<0) {
  		window.SearchAutoComplete.KeyEventSemaphor = 0;
			return
		}
		
		if (window.SearchAutoComplete.KeyEventSemaphor==0) {
			
			var term = window.SearchAutoComplete.ElementInput.value.trim();

			if ( term != window.SearchAutoComplete.lastSearchTerm ) {
				var url = '/search-ajax.php?t=' + encodeURIComponent(term);
				
				// check if we need to send preferred concept
				if (window.SearchAutoComplete.PreferredConcept !=undefined) {
					url = url + '&c=' + window.SearchAutoComplete.PreferredConcept;
				}
				
				ajaxGet( url,	window.SearchAutoComplete.autoCompleteAjaxDone,	term );
			} else {
				
				// just display the last results if nothing has changed
				this.loadAutoComplete( this.lastData );
			}
		}
	},
	
	/**
	 * update the selection
	 * @private
	 */
  setSelection: function( aNewIndex ) {
		this.SelectedIndex = aNewIndex;
		
		for( i=0;i<this.DropDownElementList.length;i++ ) {
			if ( i==this.SelectedIndex ) {
				if (this.DropDownElementList[i].isTop==true) {
  				this.DropDownElementList[i].className='selected top';
				} else {
  				this.DropDownElementList[i].className='selected';
				}
				this.DropDownElementList[i].scrollIntoView(false);
				this.ElementInput.value = this.DropDownElementList[i].data.term;
  			this.ElementContext.innerHTML = this.ContextBitMask[ this.DropDownElementList[i].data.mask ];
  			this.ElementDropDown.style.width = ( this.ElementInput.parentElement.parentElement.offsetWidth - 2 ) + 'px';
			} else {
				
				if (this.DropDownElementList[i].isTop==true) {
  				this.DropDownElementList[i].className='top';
				} else {
  				this.DropDownElementList[i].className='';
				}
			}
		}
		
	},
	
	/**
	 * Load-up autocomplete
	 * @private
	 */
	loadAutoComplete: function( aData ) {
		this.lastData = aData;
		
		// simple error check
		if (aData.error!=undefined) {			
      var html = 'Error: ' + aData.error;
      html += '<br>Line: ' + aData.line;
      html += '<br>File: ' + aData.file;

			this.ElementDropDown.innerHTML = '<pre style="padding:10px;color:red">' + html + '</pre>';
			this.ElementDropDown.style.display = 'block';			
			return;
		}

    // grab the translations
    this.LabelSearching = aData.lbl[1];
    this.LabelSearchingFor = aData.lbl[2];

    var html = '<table cellpadding=0 cellspacing=0><tr>';
		html = html + '<td class="search-left">';
		html = html + '<div class="search-term"><strong>'+this.LabelSearchingFor+'</strong> <em>"' + aData.ts.join( '&nbsp;' ) + '"</em></div>';
		
    // ---------------------------------------------
		// list concepts
    // ---------------------------------------------
		if ( aData.cs!=undefined) {
			html = html + '<div class="search-concepts">';

      // list the preferred if we have results
      if (aData.cs[this.PreferredConcept] != undefined) {
				html = html + '<div>';
				html = html + '<span>(' + aData.cs[this.PreferredConcept].cnt + ')</span>';
				html = html + '&nbsp;&nbsp;<em>' + aData.cs[this.PreferredConcept].li +'</em> <strong>' + aData.cs[this.PreferredConcept].lc +'</strong>';
				html = html + '</div>';
			}

			// list the other concept counts
			for ( var i in aData.cs ) {
				if ( i != this.PreferredConcept ){
					html = html + '<div>';
					html = html + '<span>(' + aData.cs[i].cnt + ')</span>';
					html = html + '&nbsp;&nbsp;<em>' + aData.cs[i].li +'</em> <strong>' + aData.cs[i].lc +'</strong>';
					html = html + '</div>';
				}
			}

			html = html + '</div>';
		}

    // ---------------------------------------------
    // list term combinations
    // ---------------------------------------------
		if ( aData.tcs!=undefined) {
			html = html + '<div class="search-combinations">';
			for ( var i in aData.tcs ) {
				// use HREF in otder to get keyboard TAB navigation to work
				html = html + '<a href="javascript:window.SearchAutoComplete.onClickCombination('+i+');"><div>';
    		html = html + '<span>(' + aData.tcs[i].cnt + ')</span>';
  			for ( var j in aData.tcs[i].ts ) {
					// check if we have to mark up entered term or partial term
					if (aData.ts[j]==undefined) {
        		html = html + aData.tcs[i].ts[j] + ' ';
					} else {
        		html = html + 
	  				  aData.tcs[i].ts[j].replace( 
	    					aData.ts[j].toLowerCase(),
  		  				'<strong>' + aData.ts[j] + '</strong>'
				  		 ) + ' ';
					}
				}
    		html = html + '</div></a>';					
			}
   		html = html + '</div>';
		}
		html = html + '</td>';

    // ---------------------------------------------
    // List abstract suggestions on the right side
    // ---------------------------------------------
		if ( aData.as != undefined ) {
			
			html = html + '<td class="search-right">';

				
			// check if we have preferred concepts
			if (aData.as[this.PreferredConcept] != undefined) {
				html = html + '<div class="search-total">'+aData.as[this.PreferredConcept].l +'</div>';
				
				for (var abstract_id in aData.as[this.PreferredConcept].as) {
					// grab the abstract
					var abstract = aData.as[this.PreferredConcept].as[abstract_id];
					
					html = html + '<a target="'+concept_id+'-'+abstract.i+'" href="'+abstract.u.replace('&','%26')+'">';
					html = html + '<img class="search-icon" src="/images/'+abstract.ic+'">';
					html = html + '<div class="search-abstract">';
					html = html + '<div>' + abstract.a.join( '</div><div>' ) + '</div>';
					html = html + '</div>';
					html = html + '</a>';
				}
			}				

      // abstracts are groupped under concepts
			for (var concept_id in aData.as) {
				if ( concept_id == this.PreferredConcept ) {
					continue;
				}
				
				html = html + '<div class="search-total">'+aData.as[concept_id].l +'</div>';
				
				for (var abstract_id in aData.as[concept_id].as) {
					// crab the abstract
					var abstract = aData.as[concept_id].as[abstract_id];
					
					html = html + '<a target="'+concept_id+'-'+abstract.i+'" href="'+abstract.u.replace('&','%26')+'">';
					html = html + '<img class="search-icon" src="/images/'+abstract.ic+'">';
					html = html + '<div class="search-abstract">';
					html = html + '<div>' + abstract.a.join( '</div><div>' ) + '</div>';
					html = html + '</div>';
					html = html + '</a>';
				}
			}
			
			html = html + '</td>';
			html = html + '</tr></table>';
		}		

  	this.ElementDropDown.innerHTML = html;
		this.ElementDropDown.style.display = 'block';			
		this.ProgressDisplay = false;
		return;
	},
	
	/**
	 * Loading event from ajax call
	 * @private
	 */
	autoCompleteAjaxDone: function( data, aCustomData ) {
		window.SearchAutoComplete.loadAutoComplete( data );
		window.SearchAutoComplete.lastSearchTerm = aCustomData;
	},
	
	/**
	 * The mouse was clicked
	 * @private
	 */
	onClickCombination: function( aIndex ) {
    if (this.lastData.tcs[aIndex].ts!=undefined) {
			this.ElementInput.value = this.lastData.tcs[aIndex].ts.join( ' ' );
   		this.KeyEventSemaphor = 1;
	    this.processKeyPress();		
		}
	},
	
	/**
	 * display progress
	 * @private
	 */
	displayProgress: function() {
		var term = window.SearchAutoComplete.ElementInput.value.trim();

		// check if already displayed
		if ( this.ProgressDisplay ) {
			// hide progress if entry too short
			if ( term.trim().length < this.CONFIG_MIN_TERM_LENGTH ) {
    		this.ElementDropDown.style.display = 'hidden';
    		this.ProgressDisplay = false;
			}
			return;
		}

    // no progress if entry too small
		if ( term.length < this.CONFIG_MIN_TERM_LENGTH ) return;
		
		// check if we have translation. Otherwise display basic label
		if (this.LabelSearching == undefined ) {
    	this.ElementDropDown.innerHTML = '<div class="search-progress"><img src="/images/search-progress.gif"><span>...</span></div>';
		} else {
    	this.ElementDropDown.innerHTML = '<div class="search-progress"><img src="/images/search-progress.gif"><span>'+this.LabelSearching+'</span></div>';
		}
		this.ElementDropDown.style.display = 'block';			
		this.ProgressDisplay = true;
	}
	
}