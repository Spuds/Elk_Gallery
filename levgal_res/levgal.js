// Expand ... in our page index
function levgal_expandPages(spanNode, baseURL, firstPage, lastPage)
{
	var replacement = '', i, oldLastPage = 0;
	var perPageLimit = 50;

	// The dots were bold, the page numbers are not (in most cases).
	spanNode.style.fontWeight = 'normal';
	spanNode.onclick = '';

	// Prevent too many pages to be loaded at once.
	if (lastPage - firstPage > perPageLimit)
	{
		oldLastPage = lastPage;
		lastPage = firstPage + perPageLimit - 1;
	}

	// Calculate the new pages.
	for (i = firstPage; i <= lastPage; i++)
		replacement += '<a class="navPages" href="' + baseURL.replace(/%1\$d/, i).replace(/%%/g, '%') + '">' + i + '</a> ';

	if (oldLastPage > 0)
		replacement += '<span style="font-weight: bold; cursor: ' + (is_ie && !is_ie6up ? 'hand' : 'pointer') + ';" onclick="levgal_expandPages(this, \'' + baseURL + '\', ' + (lastPage + 1) + ', ' + oldLastPage + ');"> ... </span> ';

	// Replace the dots by the new page links.
	setInnerHTML(spanNode, replacement);
}

// Switch the file/link containers
function switchUploadType(selected)
{
	var not_selected = selected == 'file' ? 'link' : 'file';
	document.getElementById('allowed_type_' + selected).style.display = '';
	document.getElementById('upload_type_' + selected).style.display = '';
	document.getElementById('allowed_type_' + not_selected).style.display = 'none';
	document.getElementById('upload_type_' + not_selected).style.display = 'none';
}

// Because this is better than using jQuery. Based on sendXMLDocument.
function sendJSONDocument(sUrl, sContent, funcCallback)
{
	var oSendDoc = new window.XMLHttpRequest(),
		oCaller = this;

	if (typeof(funcCallback) !== 'undefined')
	{
		oSendDoc.onreadystatechange = function () {
			if (oSendDoc.readyState !== 4)
			{
				return;
			}

			if (oSendDoc.status === 200)
			{
				funcCallback.call(oCaller, JSON.parse(oSendDoc.response));
			}
			else
			{
				funcCallback.call(oCaller, false);
			}
		};
	}
	oSendDoc.open('POST', sUrl, true);
	if ('setRequestHeader' in oSendDoc)
	{
		oSendDoc.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		oSendDoc.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	}
	oSendDoc.send(sContent);

	return true;
}

function handleLike(link)
{
	if (link && link.href)
	{
		ajax_indicator(true);
		sendJSONDocument(link.href, '', function (data) {
			ajax_indicator(false);
			if (data)
			{
				document.getElementById("item_likes").innerHTML = data.likes;
			}
			return false;
		});
		return false;
	}
}

function handleBookmark(link)
{
	if (link && link.href)
	{
		ajax_indicator(true);
		sendJSONDocument(link.href, '', function (data) {
			ajax_indicator(false);
			if (data)
			{
				var el = document.querySelectorAll("#sidebar_actions_bookmark, #sidebar_actions_unbookmark");
				for (var i = 0, n = el.length; i < n; i++)
				{
					el[i].innerHTML = data.link;
				}
			}
			return false;
		});
		return false;
	}
}