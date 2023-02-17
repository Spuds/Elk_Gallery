// Expand ... in our page index
function levgal_expandPages(spanNode, baseURL, firstPage, lastPage)
{
	let replacement = '', i, oldLastPage = 0,
		perPageLimit = 50;

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
		replacement += '<span style="font-weight: bold; cursor: pointer;" onclick="levgal_expandPages(this, \'' + baseURL + '\', ' + (lastPage + 1) + ', ' + oldLastPage + ');"> ... </span> ';

	// Replace the dots by the new page links.
	setInnerHTML(spanNode, replacement);
}

// Switch the file/link containers
function switchUploadType(selected)
{
	let not_selected = selected === 'file' ? 'link' : 'file';
	document.getElementById('allowed_type_' + selected).style.display = '';
	document.getElementById('upload_type_' + selected).style.display = '';
	document.getElementById('allowed_type_' + not_selected).style.display = 'none';
	document.getElementById('upload_type_' + not_selected).style.display = 'none';
}

// Because this is better than using jQuery. Based on sendXMLDocument.
function sendJSONDocument(sUrl, sContent, funcCallback)
{
	let oSendDoc = new window.XMLHttpRequest(),
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
				if (typeof oSendDoc.response !== "undefined")
					funcCallback.call(oCaller, JSON.parse(oSendDoc.response));
				else
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
			if (data)
			{
				document.getElementById("item_likes").innerHTML = data.likes;
			}
			ajax_indicator(false);
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
			if (data)
			{
				// sidebar
				let el = document.querySelectorAll("#sidebar_actions_bookmark, #sidebar_actions_unbookmark");
				for (let i = 0, n = el.length; i < n; i++)
				{
					el[i].innerHTML = data.link;
				}

				// tabs
				el = document.querySelectorAll(".listlevel1.bookmark, .listlevel1.unbookmark");
				for (i = 0, n = el.length; i < n; i++)
				{
					el[i].innerHTML = data.link;
					let link = el[i].getElementsByTagName('a');
					link[0].classList.add('linklevel1');
				}
			}
			ajax_indicator(false);
			return false;
		});
		return false;
	}
}

function barConfig(bar_data, tooltips)
{
	return {
		type: "bar",
		data: bar_data,
		options: {
			indexAxis: "y",
			categoryPercentage: ".9",
			elements: {
				bar: {borderWidth: 0,}
			},
			responsive: true,
			scales: {
				y: {
					grid: {display: false},
					afterFit: function (scaleInstance) {
						scaleInstance.width = 200;
					},
					ticks: {font: {size: 15,}},
				},
				x: {
					ticks: {display: true,},
				}
			},
			plugins: {
				title: {display: false},
				legend: {display: false},
				tooltip: {
					callbacks: {
						label: function (context)
						{
							return tooltips[context.dataIndex];
						}
					}
				}
			}
		},
	};
}

function lgalClearTooltip(elem) {
	elem.currentTarget.setAttribute("class", "lgal_share");
	elem.currentTarget.removeAttribute("aria-label");
}

function lgalShowTooltip(elem, msg) {
	elem.setAttribute("aria-label", msg);
	elem.setAttribute("class", "lgal_share tooltipped tooltipped-s");
}