/*
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Javascript file for CLA Survey Repository
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author	   Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.repository_cla_survey = {
	init: function(Y) {
		this.Y = Y;

		// show page 1 and hide page 2
		Y.one('#cla_survey_page1').show();
		Y.one('#cla_survey_page2').hide();
		//end

		//hide the url field
		Y.one('#URLinput').hide();
		//end

		// hide error divs
		Y.one('#titleError').hide();
		Y.one('#totalpagesError').hide();
		Y.one('#fromtoError').hide();
		Y.one('#publisherError').hide();
		Y.one('#copiedauthorError').hide();
		Y.one('#copiedidError').hide();
		Y.one('#titleError').hide();
		Y.one('#sourceurlError').hide();
		Y.one('#pagesError').hide();
		//end

		// hide author labels
//		   Y.one('#digitalauthor').hide();
//		   Y.one('#printedauthor').hide();

		setToDigital(); 		// default settings for page 2

		// this is to get the itemid which is the draft area's id for the saved files
		fpicker = getActivePicker();
		Y.one('#itemid').set('value', fpicker.options.itemid);
		// end

		// listeners

		// remove error messages on fill
		Y.one('#copytitle').on('blur', function(e){
			Y.one('#titleError').hide();
		});
		Y.one('#sourceurl').on('blur', function(e){
			Y.one('#sourceurlError').hide();
		});
		Y.one('#copiedid').on('blur', function(e){
			Y.one('#copiedidError').hide();
		});
		Y.one('#copiedauthor').on('blur', function(e){
			Y.one('#copiedauthorError').hide();
		});
		Y.one('#publisher').on('blur', function(e){
			Y.one('#publisherError').hide();
		});
		Y.one('#frompage').on('blur', function(e){
			Y.one('#fromtoError').hide();
		});
		Y.one('#topage').on('blur', function(e){
			Y.one('#fromtoError').hide();
		});
		Y.one('#totalpages').on('blur', function(e){
			Y.one('#totalpagesError').hide();
		});
		// end

		// toggle the radio buttons depending on whether this has copyright material
		Y.one('#copycheck').on('click', function(e) {
			var checkbox = e.target;
			if (checkbox.get('checked')) {
				// turn on the radio buttons
				Y.one('#radioWeb').set('disabled', false);
				Y.one('#radioDigital').set('disabled', false);
				Y.one('#radioPrint').set('disabled', false);
			}else{
				// move the selection away from web
				Y.one('#radioDigital').set('checked', true);
				hideURLinput(e);
				setToDigital(e);
				// turn off the radio buttons
				Y.one('#radioWeb').set('disabled', true);
				Y.one('#radioDigital').set('disabled', true);
				Y.one('#radioPrint').set('disabled', true);
			}
		});
		// end

		// radio button functions
		Y.one('#radioWeb').on('click',showURLinput);
		Y.one('#radioPrint').on('click', setToPrint);
		Y.one('#radioDigital').on('click',setToDigital);
		// end

		// toggle page 1 and 2
		Y.one('#goback').on('click', function(e) {
			Y.one('#cla_survey_page2').hide();
			Y.one('#cla_survey_page1').show();
		});
		//


		// Next steps - validate and go to page 2 if necessary
		Y.one('#gotopage2').on('click', function(e) {
//			   e.preventDefault();
			// check if a file has been selected - other upload or server file
			if ((Y.one('#serverfilename').get('value').length == 0) && (Y.one('#uploadfile').get('value').length == 0)) {
				e.preventDefault();
				alert('You need to chose a file');
				return;
			}

//			   NO VALIDATION FOR URL FIELD
//			   // validate the URL if selected on this page
//			   if (Y.one('#radioWeb').get('checked')) {
//				   if (Y.one('#sourceurl').get('value').length == 0) {
//						e.preventDefault();
//						alert("Error - Fix errors on page");
//						Y.one('#sourceurlError').show();
//				   }else{
//					   // test for url entry
//					   if (!checkURL(Y.one('#sourceurl').get('value'))) {
//						   e.preventDefault();
//						   alert("Error - Fix errors on page '" + Y.one('#sourceurl').get('value') + "'");
//						   Y.one('#sourceurlError').show();
//					   }
//				   }
//			   }

			// if we have not checked the copyright button OR we have chosen the Website radio button
			// we can submit the form - we do not need page 2
			if ( (Y.one('#copycheck').get('checked')) ) {
				if ( Y.one('#radioWeb').get('checked') === false ) {
					e.preventDefault();
					Y.one('#cla_survey_page1').hide();
					if (document.addEventListener) {			//newer browsers
						Y.one('#cla_survey_page2').set('style', 'display: block');			// undo styling set to stop filickering on load
					}
					Y.one('#cla_survey_page2').show();
					Y.one('#copytypebook').set('checked','checked');
					// and ensure author line is displayed
					Y.one('#authorrow').show();
				}
			}
		});

		// toggle author details row
		Y.one('#copytypebook').on('click', function(e) {
			Y.one('#authorrow').show();
		});
		Y.one('#copytypemag').on('click', function(e) {
			Y.one('#authorrow').hide();
		});
		Y.one('#copytypeother').on('click', function(e) {
			Y.one('#authorrow').hide();
		});

		// validate form
		// CLA	have decided that no fields are
		Y.one('#fullsubmit').on('click', function(e) {
			// validate the form
			var noerror = true;

			// need to work on this at some point with the server files
			//Y.one('repo_upload_file').files.length
			//Y.one('saveas').
			//Y.one('author').
			//Y.one('license').

			if ( Y.one('#copycheck').get('checked') ) {
				// CLA fields
//				if (Y.one('#copytitle').get('value').length == 0) {
//					noerror = false;
//					Y.one('#titleError').show();
//				}
//				if (Y.one('#copiedid').get('value').length == 0) {
//					noerror = false;
//					Y.one('#copiedidError').show();
//				}
//				if (Y.one('#copiedauthor').get('value').length == 0) {
//					noerror = false;
//					Y.one('#copiedauthorError').show();
//				}
//				if (Y.one('#publisher').get('value').length == 0) {
//					noerror = false;
//					Y.one('#publisherError').show();
//				}
//				if (Y.one('#totalpages').get('value').length == 0 &&
//						(Y.one('#topage').get('value').length == 0 || Y.one('#frompage').get('value').length == 0)) {
//					noerror = false;
//					Y.one('#pagesError').show();
//				}

				if (noerror) {
					// numerical check for pages
					if (Y.one('#totalpages').get('value').length > 0) {
						if (!isInt(Y.one('#totalpages').get('value'))) {
							Y.one('#totalpagesError').show();
							noerror = false;
						}
					}
//					if (Y.one('#topage').get('value').length > 0) {
//						if (!isInt(Y.one('#topage').get('value'))) {
//							Y.one('#fromtoError').show();
//							noerror = false;
//						}
//					}
//					if (Y.one('#frompage').get('value').length > 0) {
//						if (!isInt(Y.one('#frompage').get('value'))) {
//							Y.one('#fromtoError').show();
//							noerror = false;
//						}
//					}
				}
			}

			/* TEMPORARY */
//			   e.preventDefault(); // prevents the form from submitting

			if(!noerror) {
				alert("Error - Please fix errors on page");
				// show error messages
				e.preventDefault(); // prevents the form from submitting
			}
		});

		// functions for stuff called in several places

		// check for integers - stolen obviously :)
		function isInt(value) {
			return (!isNaN(parseInt(value,10)) && (parseFloat(value,10) == parseInt(value,10)));
		}
		// end functions

		// Copy Type Prompts etc
		function setToPrint(e) {
			//alert("Setting to Print");
			Y.one('#copytypeother').hide();
			Y.one('#copytypeprompt').hide();
			Y.one('#bookprompt').show();
			Y.one('#ebookprompt').hide();
			Y.one('#magazineprompt').show();
			Y.one('#emagazineprompt').hide();
//			   Y.one('#digitalauthor').hide();
//			   Y.one('#printedauthor').show();
			hideURLinput(e);
		}

		function setToDigital(e) {
			Y.one('#copytypeother').show();
			Y.one('#copytypeprompt').show();
			Y.one('#bookprompt').hide();
			Y.one('#ebookprompt').show();
			Y.one('#magazineprompt').hide();
			Y.one('#emagazineprompt').show();
//			   Y.one('#printedauthor').hide();
//			   Y.one('#digitalauthor').show();
			hideURLinput(e);
		}

		// URL input field toggles
		function showURLinput(e) {
			Y.one('#URLinput').set('style', 'display: block');			// undo styling set to stop flickering on load
			Y.one('#URLinput').show();
		}
		function hideURLinput(e) {
			Y.one('#URLinput').hide();
		}
		// end
	},
}

//stolen check //
function checkURL(value) {
	var urlregex = new RegExp("^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$");
	if (urlregex.test(value)) {
		return (true);
	}
	return (false);
}

function getActivePicker() {
	// this caused me no end of headache - get itemid from encompassing ajax
	var fp = window.parent.M.core_filepicker.active_filepicker; 		// later versions of Moodle
	if (fp === null) {
		// here we attempt to work out a filemanger instance
		var mykey = 0;
		// test object
		if (!('keys' in Object)) {		   // check for earlier browser versions that do not support Object Keys
			for (var i in window.parent.M.core_filepicker.instances){			//don't ask :(
				mykey = i;
				break;
			}
		}else{
			mykey = Object.keys(window.parent.M.core_filepicker.instances)[0];
		}

		if (!mykey) {
			alert('error - cannot identify filemanager instance.');
			return;
		}
		fp = window.parent.M.core_filepicker.instances[mykey];
	}

	//alert ("Key is " + mykey);
	return fp;
}