// Toggle the containers for the quotas page.
function toggleContainers(name)
{
	var link = document.getElementById(name + '_link');
	var fs = document.getElementById(name);
	fs.style.display = (fs.style.display === 'none') ? '' : 'none';
	link.style.display = (fs.style.display === 'none') ? '' : 'none';
	return false;
}

// Use to close fieldsets selection areas, expandable on click
function closeFieldsets()
{
	let thisLegends = document.getElementsByTagName("legend"),
		i;

	for (i = 0, n = thisLegends.length; i < n; i++)
	{
		thisLegends[i].nextElementSibling.style.display = 'none';
	}
}

// Show and hide the overall areas based on enabled file types.
function showHide(name)
{
	var thisvalue = document.getElementById('lgal_enable_' + name).checked;
	var elements = document.querySelectorAll('.container_' + name);
	for (var i = 0, n = elements.length; i < n; i++)
	{
		elements[i].style.display = thisvalue ? '' : 'none';
	}
}

function groups_no_quota(quota_type)
{
	var groups_no_quota_list = [];
	for (group in groupList)
	{
		var this_group = ~~group;
		// Discard craziness and people who can manage the gallery.
		if (in_array(this_group, managers))
		{
			continue;
		}

		// Now let's see if they're in any actual defined quota.
		if (group_has_quota(quota_type, this_group))
		{
			continue;
		}

		// Nope.
		groups_no_quota_list.push(group);
	}

	return groups_no_quota_list;
}

function group_has_quota(quota_type, group)
{
	var this_quota = quotas[quota_type];
	for (var i = 0, n = this_quota.length; i < n; i++)
	{
		for (var j = 0, m = this_quota[i][0].length; j < m; j++)
		{
			if (group == this_quota[i][0][j])
			{
				return true;
			}
		}
	}
	return false;
}

function generate_quota_image()
{
	var content = '<table style="width:100%" id="image_quota_table">';
	content += '<tr><th class="lefttext">' + langs.groups + '</th><th class="lefttext">' + langs.max_image_size + '</th><th class="lefttext">' + langs.max_file_size + '</th><th></th></tr>';
	content += '<tr><td>';
	// Now we add the admin/manager groups.
	for (var i = 0, n = managers.length; i < n; i++)
	{
		content += (i != 0 ? ', ' : '') + groupList[managers[i]];
	}
	content += ' <span class="smalltext">' + langs.managers + '</span></td><td>' + langs.max_image_size_unlimited + '</td><td>' + langs.max_image_size_unlimited + '</td><td></td></tr>';

	// Now we go through the rest of them.
	for (var i = 0, n = quotas.image.length; i < n; i++)
	{
		content += '<tr id="image_quota_row_' + i + '"><td class="group_list">';
		// List of groups
		for (var j = 0, m = quotas.image[i][0].length; j < m; j++)
		{
			content += (j != 0 ? ', ' : '') + groupList[quotas.image[i][0][j]];
		}
		content += '</td>';

		// Image size
		if (quotas.image[i][1] == '0x0')
		{
			content += '<td class="image_size">' + langs.max_image_size_unlimited + '</td>';
		}
		else
		{
			content += '<td class="image_size">' + quotas.image[i][1].replace('x', ' &times; ') + '</td>';
		}

		// File size
		if (quotas.image[i][2] == '0')
		{
			content += '<td class="file_size">' + langs.image_size_unlimited + '</td>';
		}
		else
		{
			content += '<td class="file_size">' + quotas.image[i][2] + '</td>';
		}

		// Modify button
		content += '<td class="buttons"><input type="button" value="'  + langs.modify + '" onclick="return edit_quota_image(' + i + ');" class="button_submit modify" /></td></tr>';
	}

	content += '</table>';

	// Now, who is left?
	var groups_without_quotas = groups_no_quota('image');

	// If there's some groups left, we can add a quota.
	if (groups_without_quotas.length != 0)
	{
		content += '<div class="floatright add_quota"><input id="image_quota_add_btn" type="button" value="' + langs.add + '" class="button_submit" onclick="return add_quota_image();" /></div>';
	}

	// And display the list of which groups have no quota.
	content += '<div class="groups_no_quota floatleft">' + langs.no_upload;
	if (groups_without_quotas.length == 0)
	{
		content += ' ' + langs.none;
	}
	else
	{
		content += '<div>';
		for (var i = 0, n = groups_without_quotas.length; i < n; i++)
		{
			content += (i != 0 ? ', ' : '') + groupList[groups_without_quotas[i]];
		}
		content += '</div>';
	}
	content += '</div>';

	// Now the really icky part.
	for (var i = 0, n = quotas.image.length; i < n; i++)
	{
		content += '<input type="hidden" name="image_quota_groups[' + i + ']" value="' + quotas.image[i][0].join(',') + '" />';
		content += '<input type="hidden" name="image_quota_imagesize[' + i + ']" value="' + quotas.image[i][1] + '" />';
		content += '<input type="hidden" name="image_quota_filesize[' + i + ']" value="' + quotas.image[i][2] + '" />';
	}

	document.getElementById('image_quota_container').innerHTML = content;
	return false;
}

function add_quota_image()
{
	document.getElementById('image_quota_add_btn').disabled = true;
	var row = document.getElementById('image_quota_table').insertRow(-1);
	row.className = 'add_quota_row';

	// First, the memberlist columns.
	var memberlist = row.insertCell(0);
	groups_without_quotas = groups_no_quota('image');
	memberlist.innerHTML = (groups_without_quotas.length == 0) ? langs.none : getGroupControl('image');

	// Now the selector for image size
	var imagesize = row.insertCell(1);

	imagesize.innerHTML = getImageSizeControl('unlimited', '', '');

	var filesize = row.insertCell(2);
	filesize.innerHTML = getFileSizeControl('image', '');

	var buttons = row.insertCell(3);
	var button_content = '<input type="button" value="' + langs.update + '" class="button_submit" onclick="return save_new_quota_image();" />';
	button_content += ' <input type="button" value="' + langs.cancel + '" class="button_submit" onclick="return generate_quota_image();" />';
	buttons.innerHTML = button_content;
	return false;
}

function edit_quota_image(row)
{
	// Once more with style.
	var tr = document.getElementById('image_quota_row_' + row);
	tr.className = 'updating_quota_row';

	var groups = document.querySelectorAll('#image_quota_row_' + row + ' td.group_list');
	groups[0].innerHTML = getGroupControl('image', row);

	// Image size.
	var imagesize = document.querySelectorAll('#image_quota_row_' + row + ' td.image_size');
	var dims = quotas.image[row][1].split('x');
	imagesize[0].innerHTML = getImageSizeControl(quotas.image[row][1] == '0x0' ? 'unlimited' : 'defined', dims[0], dims[1]);

	// And the filesize.
	var filesize = document.querySelectorAll('#image_quota_row_' + row + ' td.file_size');
	filesize[0].innerHTML = getFileSizeControl('image', quotas.image[row][2]);

	// And update the buttons
	var buttons = document.querySelectorAll('#image_quota_row_' + row + ' td.buttons');
	var buttons_content = '<input type="button" value="' + langs.update + '" class="button_submit" onclick="return update_quota_image(' + row + ');" />';
	buttons_content += ' <input type="button" value="' + langs.cancel + '" class="button_submit" onclick="return generate_quota_image();" />';
	buttons_content += ' <input type="button" value="' + langs.remove + '" class="button_submit" onclick="return remove_quota_image(' + row + ');" />';
	buttons[0].innerHTML = buttons_content;

	// And lastly disable all the other modify buttons so we can't click them.
	var otherbuttons = document.querySelectorAll('#image_quota_table input.modify');
	for (var i = 0, n = otherbuttons.length; i < n; i++)
	{
		otherbuttons[i].style.display = 'none';
	}

	return false;
}

function update_quota_image(row)
{
	// First, the member groups.
	var selectedElements = getSelectedGroups('tr#image_quota_row_' + row + ' input[name^=image_quota_row_group]');
	if (selectedElements.length == 0)
	{
		alert(langs.quota_no_groups_selected);
		return false;
	}

	// Second, image size.
	var imagesize = getImageSize();
	if (imagesize === false)
	{
		alert(langs.quota_invalid_imagesize);
		return false;
	}

	// Third, filesize.
	var filesize = document.getElementById('max_image_file_size').value;
	filesize = sanitise_filesize(filesize);
	if (!is_valid_filesize(filesize))
	{
		alert(langs.quota_invalid_filesize);
		return false;
	}

	quotas.image[row] = [selectedElements, imagesize, filesize];

	showChanged('image');

	// And back to the regeneration phase.
	return generate_quota_image();
}

function save_new_quota_image()
{
	// First, the member groups.
	var selectedElements = getSelectedGroups('tr.add_quota_row input[name^=image_quota_row_group]');
	if (selectedElements.length == 0)
	{
		alert(langs.quota_no_groups_selected);
		return false;
	}

	// Second, image size.
	var imagesize = getImageSize();
	if (imagesize === false)
	{
		alert(langs.quota_invalid_imagesize);
		return false;
	}

	// Third, filesize.
	var filesize = document.getElementById('max_image_file_size').value;
	filesize = sanitise_filesize(filesize);
	if (!is_valid_filesize(filesize))
	{
		alert(langs.quota_invalid_filesize);
		return false;
	}

	var new_row = [selectedElements, imagesize, filesize];
	quotas.image.push(new_row);

	showChanged('image');

	// And back to the regeneration phase.
	return generate_quota_image();
}

function remove_quota_image(row)
{
	quotas.image.splice(row, 1);
	showChanged('image');
	return generate_quota_image();
}

/* And now we do the generic equivalents compared to the above specific instance. */
function generate_quota_generic(type)
{
	var content = '<table style="width:100%" id="' + type + '_quota_table">';
	content += '<tr><th class="lefttext">' + langs.groups + '</th><th class="lefttext">' + langs.max_file_size + '</th><th></th></tr>';
	content += '<tr><td>';
	// Now we add the admin/manager groups.
	for (var i = 0, n = managers.length; i < n; i++)
	{
		content += (i != 0 ? ', ' : '') + groupList[managers[i]];
	}
	content += ' <span class="smalltext">' + langs.managers + '</span></td><td>' + langs.max_image_size_unlimited + '</td><td></td></tr>';

	// Now we go through the rest of them.
	for (var i = 0, n = quotas[type].length; i < n; i++)
	{
		content += '<tr id="' + type + '_quota_row_' + i + '"><td class="group_list">';
		// List of groups
		for (var j = 0, m = quotas[type][i][0].length; j < m; j++)
		{
			content += (j != 0 ? ', ' : '') + groupList[quotas[type][i][0][j]];
		}
		content += '</td>';

		// File size
		if (quotas[type][i][1] == '0')
		{
			content += '<td class="file_size">' + langs.image_size_unlimited + '</td>';
		}
		else
		{
			content += '<td class="file_size">' + quotas[type][i][1] + '</td>';
		}

		// Modify button
		content += '<td class="buttons"><input type="button" value="'  + langs.modify + '" onclick="return edit_quota_generic(\'' + type + '\', ' + i + ');" class="button_submit modify" /></td></tr>';
	}

	content += '</table>';

	// Now, who is left?
	var groups_without_quotas = groups_no_quota(type);

	// If there's some groups left, we can add a quota.
	if (groups_without_quotas.length != 0)
	{
		content += '<div class="floatright add_quota"><input id="' + type + '_quota_add_btn" type="button" value="' + langs.add + '" class="button_submit" onclick="return add_quota_generic(\'' + type + '\');" /></div>';
	}

	// And display the list of which groups have no quota.
	content += '<div class="groups_no_quota floatleft">' + langs.no_upload;
	if (groups_without_quotas.length == 0)
	{
		content += ' ' + langs.none;
	}
	else
	{
		content += '<div>';
		for (var i = 0, n = groups_without_quotas.length; i < n; i++)
		{
			content += (i != 0 ? ', ' : '') + groupList[groups_without_quotas[i]];
		}
		content += '</div>';
	}
	content += '</div>';

	// Now the really icky part.
	for (var i = 0, n = quotas[type].length; i < n; i++)
	{
		content += '<input type="hidden" name="' + type + '_quota_groups[' + i + ']" value="' + quotas[type][i][0].join(',') + '" />';
		content += '<input type="hidden" name="' + type + '_quota_filesize[' + i + ']" value="' + quotas[type][i][1] + '" />';
	}

	document.getElementById(type + '_quota_container').innerHTML = content;
	return false;
}

function add_quota_generic(type)
{
	document.getElementById(type + '_quota_add_btn').disabled = true;
	var row = document.getElementById(type + '_quota_table').insertRow(-1);
	row.className = 'add_quota_row';

	// First, the memberlist columns.
	var memberlist = row.insertCell(0);
	var groups_without_quotas = groups_no_quota(type);
	memberlist.innerHTML = (groups_without_quotas.length == 0) ? langs.none : getGroupControl(type);

	var filesize = row.insertCell(1);
	filesize.innerHTML = getFileSizeControl(type, '');

	var buttons = row.insertCell(2);
	var button_content = '<input type="button" value="' + langs.update + '" class="button_submit" onclick="return save_new_quota_generic(\'' + type + '\');" />';
	button_content += ' <input type="button" value="' + langs.cancel + '" class="button_submit" onclick="return generate_quota_generic(\'' + type + '\');" />';
	buttons.innerHTML = button_content;
	return false;
}

function edit_quota_generic(type, row)
{
	// Once more with style.
	var tr = document.getElementById(type + '_quota_row_' + row);
	tr.className = 'updating_quota_row';

	var groups = document.querySelectorAll('#' + type + '_quota_row_' + row + ' td.group_list');
	groups[0].innerHTML = getGroupControl(type, row);

	// And the filesize.
	var filesize = document.querySelectorAll('#' + type + '_quota_row_' + row + ' td.file_size');
	filesize[0].innerHTML = getFileSizeControl(type, quotas[type][row][1]);

	// And update the buttons
	var buttons = document.querySelectorAll('#' + type + '_quota_row_' + row + ' td.buttons');
	var buttons_content = '<input type="button" value="' + langs.update + '" class="button_submit" onclick="return update_quota_generic(\'' + type + '\',' + row + ');" />';
	buttons_content += ' <input type="button" value="' + langs.cancel + '" class="button_submit" onclick="return generate_quota_generic(\'' + type + '\');" />';
	buttons_content += ' <input type="button" value="' + langs.remove + '" class="button_submit" onclick="return remove_quota_generic(\'' + type + '\',' + row + ');" />';
	buttons[0].innerHTML = buttons_content;

	// And lastly disable all the other modify buttons so we can't click them.
	var otherbuttons = document.querySelectorAll('#' + type + '_quota_table input.modify');
	for (var i = 0, n = otherbuttons.length; i < n; i++)
	{
		otherbuttons[i].style.display = 'none';
	}

	return false;
}

function update_quota_generic(type, row)
{
	// First, the member groups.
	var selectedElements = getSelectedGroups('tr#' + type + '_quota_row_' + row + ' input[name^=' + type + '_quota_row_group]');
	if (selectedElements.length == 0)
	{
		alert(langs.quota_no_groups_selected);
		return false;
	}

	// Next, filesize.
	var filesize = document.getElementById('max_' + type + '_file_size').value;
	filesize = sanitise_filesize(filesize);
	if (!is_valid_filesize(filesize))
	{
		alert(langs.quota_invalid_filesize);
		return false;
	}

	quotas[type][row] = [selectedElements, filesize];

	showChanged(type);

	// And back to the regeneration phase.
	return generate_quota_generic(type);
}

function save_new_quota_generic(type)
{
	// First, the member groups.
	var selectedElements = getSelectedGroups('tr.add_quota_row input[name^=' + type + '_quota_row_group]');
	if (selectedElements.length == 0)
	{
		alert(langs.quota_no_groups_selected);
		return false;
	}

	// Next, filesize.
	var filesize = document.getElementById('max_' + type + '_file_size').value;
	filesize = sanitise_filesize(filesize);
	if (!is_valid_filesize(filesize))
	{
		alert(langs.quota_invalid_filesize);
		return false;
	}

	var new_row = [selectedElements, filesize];
	quotas[type].push(new_row);

	showChanged(type);

	// And back to the regeneration phase.
	return generate_quota_generic(type);
}

function remove_quota_generic(type, row)
{
	quotas[type].splice(row, 1);
	showChanged(type);
	return generate_quota_generic(type);
}

/* And some helper functions */
function getSelectedGroups(selector)
{
	var elements = document.querySelectorAll(selector);
	var selectedElements = [];
	for (var i = 0, n = elements.length; i < n; i++)
	{
		if (elements[i].checked)
		{
			selectedElements.push(~~(elements[i].value));
		}
	}
	selectedElements.sort();
	return selectedElements;
}

function getImageSize()
{
	if (document.getElementById('max_image_size').value == 'defined')
	{
		var imagewidth = ~~(document.getElementById('max_image_size_width').value);
		var imageheight = ~~(document.getElementById('max_image_size_height').value);
		if (imagewidth <= 0 || imagewidth >= 10000 || imageheight <= 0 || imageheight >= 10000)
		{
			return false;
		}
		return imagewidth + 'x' + imageheight;
	}
	else
	{
		return '0x0';
	}
}

function getGroupControl(section, row)
{
	var group_content = '<ul class="permission_groups">';
	if (typeof row != 'undefined')
	{
		// First, grab the groups currently selected.
		for (var i = 0, n = quotas[section][row][0].length; i < n; i++)
		{
			group_content += '<li><label><input type="checkbox" name="' + section + '_quota_row_group[' + quotas[section][row][0][i] + ']" value="' + quotas[section][row][0][i] + '" class="input_check" checked="checked" /> ' + groupList[quotas[section][row][0][i]] + '</label></li>';
		}
	}

	// Then the ones without quotes
	groups_without_quotas = groups_no_quota(section);
	for (var i = 0, n = groups_without_quotas.length; i < n; i++)
	{
		group_content += '<li><label><input type="checkbox" name="' + section + '_quota_row_group[' + groups_without_quotas[i] + ']" value="' + groups_without_quotas[i] + '" class="input_check" /> ' + groupList[groups_without_quotas[i]] + '</label></li>';
	}

	group_content += '</ul>';

	return group_content;
}

function getImageSizeControl(size_setting, width, height)
{
	var imagesize_content = '<select name="max_image_size" id="max_image_size" onchange="document.getElementById(\'max_image_size_setting\').style.display = (this.value == \'defined\' ? \'\' : \'none\');">';
	imagesize_content += '<option value="unlimited"' + (size_setting == 'unlimited' ? ' selected="selected"' : '') + '>' + langs.max_image_size_unlimited + '</option>';
	imagesize_content += '<option value="defined"' + (size_setting == 'defined' ? ' selected="selected"' : '') + '>' + langs.max_image_size_defined + '</option>';
	imagesize_content += '</select>';
	imagesize_content += '<div id="max_image_size_setting"' + (size_setting == 'unlimited' ? ' style="display:none"' : '') + '>';
	var placeholder = langs.max_image_size_placeholder;
	placeholder = placeholder.replace('%1$s', '<input type="text" id="max_image_size_width" value="' + (size_setting == 'unlimited' ? '' : width) + '" size="4" class="input_text" />');
	placeholder = placeholder.replace('%2$s', '<input type="text" id="max_image_size_height" value="' + (size_setting == 'unlimited' ? '' : height) + '" size="4" class="input_text" />');
	imagesize_content += placeholder + '</div>';

	return imagesize_content;
}

function getFileSizeControl(type, current_size)
{
	return '<input type="text" id="max_' + type + '_file_size" value="' + current_size + '" size="5" class="input_text" />';
}

function sanitise_filesize(str)
{
	// Trim the spaces and remove any character that doesn't fit.
	str = str.replace(/^\s+|\s+$/gm,'');
	return str.replace(/[^0-9KMG]/gi, '');
}

function is_valid_filesize(str)
{
	var re = /^[1-9][0-9]{0,5}[KMG]$/i;
	return re.test(str);
}

function showChanged(section)
{
	document.getElementById(section + '_changed').style.display = 'block';
}