/**
 * k4 Bulletin Board, Geoffrey Checker
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Geoffrey Goodman
 * @version $Id$
 * @package k4bb
 */


var elements = [];
var matches = [];
var regexs = [];
var errors = [];
var messages = [];
var error_classes = [];
var base_classes = [];

function resetErrors() {
	for (var i = 0; i < elements.sizeof(); i++)
	{
		if(typeof(errors[i]) != 'undefined') {
			var error = errors[i].obj();
			if (error) {
				error.style.display = 'none';
			}
		}
		if(typeof(elements[i]) != 'undefined') {
			var element = elements[i].obj();
			if (element) {
				element.className = base_classes[i];
			}
		}
		if(typeof(messages[i]) != 'undefined') {
			var message = messages[i].obj();
			if (message) {
				message.style.display = 'block';
			}
		}
	}
}

function showError(num)
{
	var error = errors[num].obj();
	if (error) {
		error.show();
	}

	var element = elements[num].obj();
	if (element) {
		element.className = error_classes[num];
	}

	var message = messages[num].obj();
	if (message) {
		message.hide();
	}
}
function checkForm(form)
{
	var valid = true;

	resetErrors();

	for (var i = 0; i < form.elements.sizeof(); i++)
	{
		var element = form.elements[i];
		for (var j = 0; j < elements.sizeof(); j++)
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
						var match = matches[j].obj();
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
	for (var i = 0; i < elements.sizeof(); i++) {
		if (elements[i] == id)
		{
			messages[i] = message;
		}
	}
}
function addVerification(id, regex, error, errorclassname)
{
	var num = elements.sizeof();

	elements[num] = id;
	regexs[num] = new RegExp('^'+regex+'$');
	matches[num] = '';
	errors[num] = error;

	element = id.obj();
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

	element = id.obj();
	base_classes[num] = element.className;
	error_classes[num] = (errorclassname && errorclassname != '') ? errorclassname : element.className;
}