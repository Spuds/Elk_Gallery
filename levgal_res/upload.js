function addFileFilter(file, quota)
{
	let ext = file.name.split(".").pop().toLowerCase();
	if (quota.formats.hasOwnProperty(ext))
	{
		let this_quota = quota.quotas[quota.formats[ext]];
		if (this_quota === true)
		{
			return;
		}
		else
		{
			if (!this_quota.hasOwnProperty("file") || this_quota.file === false ||
				(quota.formats[ext] === "image" && (!this_quota.hasOwnProperty("image") || this_quota.image === false)))
			{
				return txt.not_allowed + ' (' + ext + ')';
			}

			if (this_quota.file < file.size)
			{
				return txt.upload_too_large;
			}

			if (quota.formats[ext] !== "image" || this_quota.image === true)
			{
				return;
			}

			if (ext === 'png' || ext === 'jpeg' || ext === 'jpg')
			{
				let dimensions = this_quota.image.split('x');
				if (file.width > dimensions[0] || file.height > dimensions[1])
				{
					return txt.upload_image_too_big;
				}
			}
		}
	}
	else
	{
		return txt.not_allowed + ' (' + file.name + ')';
	}
}

function is_submittable()
{
	var local_submittable = true;

	item = document.getElementById('item_name');
	if (item !== null && item.value.trim() === '')
	{
		display_error(txt.upload_no_title, false);
		local_submittable = false;
	}

	let type = document.getElementById('upload_type')
	if (type !== null && type.value === 'file')
	{
		if (local_submittable && submittable)
		{
			return true;
		}

		display_error(txt.upload_no_file, !local_submittable);
		return false;
	}
	else
	{
		// Courtesy comments to http://stackoverflow.com/questions/161738/what-is-the-best-regular-expression-to-check-if-a-string-is-a-valid-url#comment24355215_9284473
		// Note that this presumes the URL will always have a schema - but if it doesn't
		// (e.g. www.youtube.com/...), softly fix that here.
		let url = document.getElementById('upload_url').value;
		if (url.slice(0, 7) !== 'http://' && url.slice(0, 8) !== 'https://')
		{
			url = (url.slice(0, 2) === '//' ? 'http:' : 'http://') + url;
		}
		let re = /^(?:(?:https?):\/\/)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.‌​\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[‌​6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1‌​,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00‌​a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u‌​00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i;
		let match = url.match(re);
		if (match === null)
		{
			display_error(txt.upload_no_link, !local_submittable);
			return false;
		}
		return local_submittable;
	}
}

function display_error(error, append)
{
	let error_list = document.getElementById("error_list");
	if (!error_list)
	{
		return false;
	}

	let error_html = error_list.innerHTML;
	if (append)
	{
		error_html += (error_html.replace(/^\s+|\s+$/gm, "") ? "<br />" : "") + error;
	}
	else
	{
		error_html = error;
	}
	error_list.innerHTML = error_html;
	document.getElementById("errors").style.display = "block";
}

function get_upload_defaults()
{
	return {
		paramName: "file",
		chunking: true,
		retryChunks: true,
		parallelUploads: 1,
		chunkSize: 200000,
		parallelChunkUploads: true,
	};
}

function beginUpload()
{
	if (uploader.files.length > 0)
	{
		uploader.processQueue();
	}

	document.getElementById('begin_button').style.display = "none";
	return false;
}

function onChunkComplete(data)
{
	if (typeof data === 'undefined')
	{
		return false;
	}

	// This is less than ideal, but chunking does not update the template with uuid, so we
	// have to search by name, which is not necessarily unique
	data = data.responseJSON;
	if (typeof data.error !== 'undefined')
	{
		let myNames = document.getElementsByClassName('name'),
			el = null;
		for (let i = 0; i < myNames.length; i++)
		{
			if (myNames[i].innerHTML === data.filename)
			{
				el = myNames[i].nextElementSibling;
				break;
			}
		}

		if (el !== null && typeof data.error !== 'undefined')
		{
			el.innerHTML = '<span class="error">' + txt.error_occurred + ': ' + data.error + '</span>';
		}
	}

	return true;
}

function onFileSend(data)
{
	if (typeof data === 'undefined')
	{
		return false;
	}
	for (var i in urls)
	{
		if (urls[i].async === data.async && urls[i].url === "")
		{
			let el = document.getElementById('async_' + data.async);

			fileCount = fileCount - 1;
			urls[i].url = data.url;

			if (typeof data.error !== 'undefined')
			{
				el.innerHTML = '<span class="error">' + txt.error_occurred + ': ' + data.error + '</span>';
			}
			else
				el.innerHTML = '<button class="button_submit"><span class="icon i-external-link"></span> <a href="' + urls[i].url + '" target="_blank">' + txt.view_item + '</a></button>';
		}
	}

	if (fileCount === 0)
	{
		document.querySelector("#total-progress .progress-bar").innerHTML = txt.upload_complete;
		document.querySelector(".dz-default.dz-message").innerHTML = txt.upload_complete;
	}
}

function get_human_size(filesize)
{
	// Behaves like LevGal_Helper_Format::filesize
	if (filesize < (1024 * 1024))
	{
		return txt.size_kb.replace('%1$s', (filesize / 1024).toFixed(1));
	}
	if (filesize < (1024 * 1024 * 1024))
	{
		return txt.size_mb.replace('%1$s', (filesize / 1024 / 1024).toFixed(1));
	}

	return txt.size_gb.replace('%1$s', (filesize / 1024 / 1024 / 1024).toFixed(1));
}