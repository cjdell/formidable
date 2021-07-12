<?php

/**
 * Child theme functions
 * When using a child theme (see http://codex.wordpress.org/Theme_Development
 * and http://codex.wordpress.org/Child_Themes), you can override certain
 * functions (those wrapped in a function_exists() call) by defining them first
 * in your child theme's functions.php file. The child theme's functions.php
 * file is included before the parent theme's file, so the child theme
 * functions would be used.
 *
 * Text Domain: oceanwp
 * @link http://codex.wordpress.org/Plugin_API
 *
 */

/**
 * Load the parent style.css file
 * @link http://codex.wordpress.org/Child_Themes
 */

function formidable_enqueue_parent_style()
{

	// Dynamically get version number of the parent stylesheet (lets browsers re-cache your stylesheet when you update your theme)
	$theme   = wp_get_theme('OceanWP');
	$version = $theme->get('Version');

	// Load the stylesheet
	wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', ['oceanwp-style'], $version);
	wp_enqueue_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', ['jquery']);
	wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
	wp_enqueue_script('jquery-ui-widget');
	wp_enqueue_script('jquery-ui-accordion');
	wp_enqueue_script('DataTables-init-js', get_stylesheet_directory_uri() . '/js/dataTables-init.js', ['DataTables-js']);
	wp_enqueue_script('DataTables-searchDate-js', get_stylesheet_directory_uri() . '/js/dataTables-searchDate.js', ['DataTables-js']);
    wp_enqueue_style('DataTables_Styles-css', 'https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css');
	wp_enqueue_style('DataTables-calendar-css', 'https://cdn.datatables.net/datetime/1.1.0/css/dataTables.dateTime.min.css');
    wp_enqueue_script('DataTables-js', 'https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', ['jquery']);
    wp_enqueue_script('DataTableButtonsPrint-js', 'https://cdn.datatables.net/buttons/1.6.4/js/buttons.print.min.js', ['DataTables-js']);
    wp_enqueue_script('DataTableButtons-js', 'https://cdn.datatables.net/buttons/1.6.4/js/dataTables.buttons.min.js', ['DataTables-js']);
    wp_enqueue_script('HTML5Buttons-js', 'https://cdn.datatables.net/buttons/1.6.4/js/buttons.html5.min.js');
    wp_enqueue_script('JsZip-js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js');
    wp_enqueue_script('PdfMake-js', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js');
    wp_enqueue_script('VfsFonts-js', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js');
	wp_enqueue_script('Moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js');
	wp_enqueue_script('DateTime-js', 'https://cdn.datatables.net/datetime/1.1.0/js/dataTables.dateTime.min.js');
}

// ========================== Formidable Admin Tweaks ================================

add_filter('frm_admin_full_screen_class', 'frm_keep_full_screen');
function frm_keep_full_screen()
{
	return '';
}

add_action('admin_menu', 'frm_remove_smtp_menu', 9999);
function frm_remove_smtp_menu()
{
	remove_submenu_page('formidable', 'formidable-smtp');
}

add_action('wp_enqueue_scripts', 'formidable_enqueue_parent_style');

add_action('frm_after_create_entry', 'after_entry_created', 30, 2);
function after_entry_created($entry_id, $form_id)
{
	if ($form_id == 113) { //change 9 to the ID of your reservations form
		global $wpdb;
		$reward_ids = $_POST['item_meta'][2136]; //change 95 to the ID of your Dynamic dropdown field in your reservations form
		$booking_num = $_POST['item_meta'][2137]; //change 96 to the ID of your booking places field in your reservations form
		$seat_count_field = 2134; //change 91 to the ID of the available seats field in the event form
		foreach ((array) $reward_ids as $reward_id) {
			$available = FrmEntryMeta::get_entry_meta_by_field($reward_id, $seat_count_field, $booking_num, true);
			$wpdb->update($wpdb->prefix . 'frm_item_metas', array('meta_value' => ((int) $available - $booking_num)), array('item_id' => $reward_id, 'field_id' => $seat_count_field));
		}
	}
}

/** Blackout Booked Afternoon Tea Dates **/

define('FF_FORM_AFTERNOON_TEA_KEY', 'afternoonteas');
define('FF_AFTERNOON_TEA_DATE_FIELD_KEY', 'csubj');
define('FF_AFTERNOON_TEA_TIME_FIELD_KEY', 'soglr');
define('FF_AFTERNOON_TEA_DAY_BOOKING_MAX', 3); // change to 25 after testing
define('FF_AFTERNOON_TEA_SLOT_BOOKING_MAX', 2); // change to ?? after testing

wp_enqueue_script('js-file', get_stylesheet_directory_uri() . '/js/booking.js', ['jquery']);

add_action('wp_ajax_booking_ajax', 'booking_ajax');
function booking_ajax()
{
	$bookedDates = findBookings();

	header('Content-Type: application/json');
	echo json_encode($bookedDates);

	wp_die();
}

/** FF - Block Booked Dates - Find any dates from today forward that have all of its time slots booked.  If so, then the date is added to the DatePickers "datesDisabled" parameter in the javascript. */
/** This Formidable filter allows you to add a list of "blacked out dates" to the existing javascript for the datepicker */

add_filter('frm_date_field_options', 'add_booking_blackout_dates', 30, 2);
function add_booking_blackout_dates($js_options, $extra)
{
	if ($extra['field_id'] === "field_" . FF_AFTERNOON_TEA_DATE_FIELD_KEY) {
		$bookedDates = findBookings();
		foreach ($bookedDates as $date => ['count' => $count]) {
			if ($count === 0) {
				$js_options['formidable_dates']['datesDisabled'][] = date('Y-m-d', strtotime($date));
			}
		}
	}

	return $js_options;
}

/** 
 * Find all the dates (today or in the future) that have maxxed out 
 * the bookings for all available slots
 */
function findBookings()
{
	$currentDate = date("Y-m-d");

	$formId = FrmForm::getIdByKey(FF_FORM_AFTERNOON_TEA_KEY);
	$dateField = FrmField::getOne(FF_AFTERNOON_TEA_DATE_FIELD_KEY);
	$timeField = FrmField::getOne(FF_AFTERNOON_TEA_TIME_FIELD_KEY);
	$entries = FrmEntry::getAll(array('it.form_id' => $formId), " ORDER BY meta_$dateField->id DESC", " LIMIT 1000");

	// using the date, calculate the total bookings
	//  if the total bookings for a time slot exceed the limit
	//    all its slots filled

	$dateSlots = [];

	foreach ($entries as $entry) {
		$entry = FrmEntry::getOne($entry->id, true);
		$entryDate = $entry->metas[$dateField->id];
		$entryTime = $entry->metas[$timeField->id];

		// jump out if you've passed the date
		//  because we're only ever looking at today and forward
		//  and the list of $entries is sorted descending by date
		if ($entryDate < $currentDate) {
			break;
		}

		if (isset($dateSlots[$entryDate])) {
			$dateSlots[$entryDate]['count']--;
			$dateSlots[$entryDate]['times'][$entryTime]--;
		} else {
			$dateSlots[$entryDate] = ['count' => FF_AFTERNOON_TEA_DAY_BOOKING_MAX - 1, 'times' => [$entryTime => FF_AFTERNOON_TEA_SLOT_BOOKING_MAX - 1]];
		}
	}

	return $dateSlots;
}

