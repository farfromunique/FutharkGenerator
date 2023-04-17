/*
 * Provides ajax tools for ACWPD Projects
 * Feel free to use this in your projects! Just provide Attribution by keeping this block in place!
 *
 * This is version 1.1
 *
 * For the latest version, please visit: https://github.com/farfromunique/ACWPD_Tools
 *
 * This code is copyright (C) 2019 Aaron Coquet / ACWPD
 */

function prepareForSave () {
  let form = document.querySelector( '#data' );
  formElements = form.querySelectorAll( 'input' );
  formElements.forEach( el => {
    el.remove();
  } );
  for ( let index = 0; index < localStorage.length; index++ ) {
    const element = localStorage.key( index );

    let input = document.createElement( 'input' );
    if ( !element.startsWith( 'ic-' ) ) {
      input.name = localStorage.key( index );
      input.value = localStorage[element];
      input.type = 'hidden';
      console.log( 'name', localStorage.key( index ), 'value', input.value )
      form.appendChild( input );
    }
  }
}

$( document ).ready( function () {

  $( 'button.toggler' ).click( function ( ev ) {
    let tar = ev.target;
    html = $( tar ).html();
    old = html.match( /(Add|Show|Hide)/ )[0];
    new_ = ( old == 'Hide' ) ? 'Show' : 'Hide';
    replacement_html = html.replace( /Add|Show|Hide/, new_ );
    $( tar ).html( replacement_html );
  } );

  $( '#type-notes' ).on( 'input', function ( el ) {
    let remaining = 4000 - el.target.value.length;
    document.querySelector( '#type-notes-holder .notes-length .count' ).innerText = remaining;
    localStorage['notes_type'] = el.target.value;
  } );

  $( '#flavor-notes' ).on( 'input', function ( el ) {
    let remaining = 4000 - el.target.value.length;
    document.querySelector( '#flavor-notes-holder .notes-length .count' ).innerText = remaining;
    localStorage['notes_flavor'] = el.target.value;
  } );

  $( '#twist-notes' ).on( 'input', function ( el ) {
    let remaining = 4000 - el.target.value.length;
    document.querySelector( '#twist-notes-holder .notes-length .count' ).innerText = remaining;
    localStorage['notes_twist'] = el.target.value;
  } );

  $( '.icon.remove' ).click( function () {
    this.parentNode.remove();
  } );

  $( '#saveButton' ).click( function () {
    prepareForSave();
    $( '#data' ).submit();
  } );

  if ( localStorage['type_notes'].length > 0 ) {
    $( '#type-notes-button' ).click();
    document.querySelector( '#type-notes-holder .notes-length .count' ).innerText = 4000 - localStorage['type_notes'].length
  }

  if ( localStorage['flavor_notes'].length > 0 ) {
    $( '#flavor-notes-button' ).click();
    document.querySelector( '#flavor-notes-holder .notes-length .count' ).innerText = 4000 - localStorage['flavor_notes'].length
  }

  if ( localStorage['twist_notes'].length > 0 ) {
    $( '#twist-notes-button' ).click();
    document.querySelector( '#twist-notes-holder .notes-length .count' ).innerText = 4000 - localStorage['twist_notes'].length
  }

} );

document.addEventListener( "DOMContentLoaded", function ( event ) {
  document.querySelector( '#legal-button' ).addEventListener( 'click', legal_button_click );
} );

function legal_button_click () {
  let L_text = document.querySelector( '#legal-text' );
  let L_hold = document.querySelector( '#legal-holder' );
  let S_hold = document.querySelector( '#save-holder' );
  let less_icon = document.querySelector( '#less_icon' );
  let more_icon = document.querySelector( '#more_icon' );

  is_transparent = L_text.classList.toggle( 'transparent' );
  is_col_2 = L_hold.classList.toggle( 'col-md-2' );
  is_col_6 = L_hold.classList.toggle( 'col-md-6' );
  is_offset_4 = S_hold.classList.toggle( 'offset-md-8' );
  is_offset_4 = S_hold.classList.toggle( 'offset-md-4' );
  is_d_none = L_text.classList.toggle( 'd-none' );
  less_is_gone = less_icon.classList.toggle( 'd-none' );
  more_is_gone = more_icon.classList.toggle( 'd-none' );
}