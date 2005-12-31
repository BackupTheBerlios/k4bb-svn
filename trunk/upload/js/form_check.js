/**
* k4 Bulletin Board, form_check.js
*
* Copyright (c) 2005, Geoffrey Goodman
*
* This library is free software; you can redistribute it and/orextension=php_gd2.dll
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
* 
* Licensed under the LGPL license
* http://www.gnu.org/copyleft/lesser.html
*
* @author Geoffrey Goodman
* @version $Id: form_check.js 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

var elements = new Array();
var matches = new Array();
var regexs = new Array();
var errors = new Array();
var messages = new Array();
var error_classes = new Array();
var base_classes = new Array();

function resetErrors() {
	for (var i = 0; i < elements.length; i++)
	{
		var error = document.getElementById(errors[i]);
		if (error) error.style.display = 'none';

		var element = document.getElementById(elements[i]);
		if (element) element.className = base_classes[i];

		var message = document.getElementById(messages[i]);
		if (message) message.style.display = 'block';
	}
}

function showError(num)
{
	var error = document.getElementById(errors[num]);
	if (error) error.style.display = 'block';

	var element = document.getElementById(elements[num]);
	if (element) element.className = error_classes[num];

	var message = document.getElementById(messages[num]);
	if (message) message.style.display = 'none';
}
function checkForm(form)
{
	var valid = true;

	resetErrors();

	for (var i = 0; i < form.elements.length; i++)
	{
		var element = form.elements[i];
		for (var j = 0; j < elements.length; j++)
		{
			if (elements[j] == element.id)
			{
				var value = (element.options) ? element[element.selectedIndex].value : element.value;
				if (regexs[j] != '' && !regexs[j].test(value))
				{
					showError(j);
					valid = false;
					break;
				}
				if(typeof matches != 'undefined' && matches) {
					if (matches[j]) {
						var match = document.getElementById(matches[j]);
						if(typeof(match) != 'undefined' && match) {
							if (element.value != match.value)
							{
								element.value = '';
								match.value = '';

								showError(j);
								valid = false;
								break;
							}
						}
					}
				}
			}
		}
	}

	return valid;
}
function addMessage(id, message)
{
	for (var i = 0; i < elements.length; i++) {
		if (elements[i] == id)
		{
			messages[i] = message;
		}
	}
}
function addVerification(id, regex, error, errorclassname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = new RegExp('^'+regex+'$');
	matches[num] = '';
	errors[num] = error;

	element = document.getElementById(id);
	base_classes[num] = element.className;
	error_classes[num] = (errorclassname && errorclassname != '') ? errorclassname : element.className;
}
function addCompare(id, match, error, errorclassname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = '';
	matches[num] = match;
	errors[num] = error;

	element = document.getElementById(id);
	base_classes[num] = element.className;
	error_classes[num] = (errorclassname && errorclassname != '') ? errorclassname : element.className;
}