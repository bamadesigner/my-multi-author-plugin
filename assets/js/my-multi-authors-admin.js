(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Enable select2 for primary author field.
		var $primary_author_select = $( '#my-multi-authors-primary' );
		if ( typeof $primary_author_select.select2 == 'function' ) {
			$primary_author_select.select2();
		}

		// Initiate each author.
		$( '#my-multi-authors-select .my-multi-authors-author' ).each( function( index, value ) {
			$( this ).my_multi_authors_init_author( index );
		});

		// Process the button to add authors.
		$( '#my-multi-authors-add-author' ).on( 'click', function ( e ) {
			e.preventDefault();
			my_multi_authors_add_author_field();
		});
	});

	// Add an author field to the lineup.
	function my_multi_authors_add_author_field() {

		// Define the authors select wrapper.
		var $authors_select = $( '#my-multi-authors-select' );
		var $authors_children = $authors_select.children( '.my-multi-authors-author' );

		// Get the last author.
		var $last_author = $authors_children.last();
		var last_author_index = $last_author.data( 'index' );

		// Clone the last author row.
		var $new_author = $last_author.clone();
		var new_author_index = last_author_index + 1;

		// Empty the select.
		var $new_author_autocomplete = $new_author.find( 'select.my-multi-authors-autocomplete' );
		$new_author_autocomplete.select2( 'destroy' ).show();
		$new_author_autocomplete.children( 'option:selected' ).prop( 'selected', false );

		// Replace select2 with select.
		$new_author.find( '.select2-container' ).replaceWith( $new_author_autocomplete );

		// Initiate the new author.
		$new_author.my_multi_authors_init_author( new_author_index );

		// Add to the lineup.
		$authors_select.append( $new_author );

	}

	// Initiate an author select.
	$.fn.my_multi_authors_init_author = function( index ) {

		// Set the author.
		var $author = $( this );

		// Set the author index.
		if ( ( index !== undefined || index !== null ) && index >= 0 ) {
			$author.data( 'index', index );
		}

		// Get the parts we need.
		var $author_delete = $author.find( '.my-multi-authors-author-delete' );
		var $author_autocomplete = $author.find( 'select.my-multi-authors-autocomplete' );

		// Define/set the autocomplete label.
		var autocomplete_label = 'my_multi_author_authors' + index;
		$author.find( '.my-multi-authors-label' ).attr( 'for', autocomplete_label );

		// Define/set the autocomplete ID.
		var autocomplete_id = 'my-multi-authors-autocomplete' + index;
		$author_autocomplete.attr( 'id', autocomplete_id );

		// Enable select2.
		if ( typeof $author_autocomplete.select2 == 'function' ) {
			$author_autocomplete.select2();
		}

		// Setup delete.
		$author_delete.on( 'click', function(e) {
			e.preventDefault();
			$( this ).closest( '.my-multi-authors-author' ).my_multi_authors_delete_author();
		});
	};

	// Handle author delete.
	$.fn.my_multi_authors_delete_author = function() {

		// Count the amount of children.
		var authors_children_count = $( '#my-multi-authors-select .my-multi-authors-author' ).length;

		// If deleting the only author, prepare to add a new author.
		if ( 1 == authors_children_count ) {

			// Add a new author before delete.
			my_multi_authors_add_author_field();

		}

		// Remove the element.
		$( this ).remove();

	}
})(jQuery);