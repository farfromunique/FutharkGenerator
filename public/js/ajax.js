/*
 * Provides ajax tools for ACWPD Projects
 * Feel free to use this in your projects! Just provide Attribution by keeping this block in place!
 *
 * This is version 1.0
 *
 * For the latest version, please visit: https://github.com/farfromunique/ACWPD_Tools
 *
 * This code is copyright (C) 2019 Aaron Coquet / ACWPD
 */

function prepareForSave() {
	for (let index = 0; index < localStorage.length; index++) {
		const element = localStorage.key(index);
		let form = document.querySelector('#data');
		let input = document.createElement("input");
		input.name = localStorage.key(index);
		input.value = localStorage[element];
		input.type = 'hidden';
		console.table('name',localStorage.key(index),'value',input.value)
		form.appendChild(input);
	}
}

$(document).ready(function(){
	$('button.toggler').click(function(ev){
		let tar = ev.target;
		html = $(tar).html();
		old = html.match(/(Add|Show|Hide)/)[0];
		new_ = (old == 'Hide') ? 'Show' : 'Hide';
		replacement_html = html.replace(/Add|Show|Hide/, new_);
		$(tar).html(replacement_html);
	});

	$('#type-notes').on('input', function(el){
			let remaining = 4000 - el.target.value.length;
			document.querySelector('#type-notes-holder .notes-length .count').innerText = remaining;
			localStorage['notes_type'] = el.target.value;
	});

	$('#flavor-notes').on('input', function(el){
		let remaining = 4000 - el.target.value.length;
		document.querySelector('#flavor-notes-holder .notes-length .count').innerText = remaining;
		localStorage['notes_flavor'] = el.target.value;
	});

	$('#twist-notes').on('input', function(el){
		let remaining = 4000 - el.target.value.length;
		document.querySelector('#twist-notes-holder .notes-length .count').innerText = remaining;
		localStorage['notes_twist'] = el.target.value;
	});

	$('.icon.remove').click(function(){
		this.parentNode.remove();
	});

	$('#saveButton').click(function(){
		prepareForSave();
		$("#data").submit();
	});
});
