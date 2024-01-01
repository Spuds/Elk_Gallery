/*!
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */


/**
 * Expands the ... in the page index by replacing it with page links.
 *
 * @param {HTMLElement} spanNode - The HTML element that contains the ... to be expanded.
 * @param {string} baseURL - The base URL for the page links.
 * @param {number} firstPage - The first page number to be included in the expanded pages.
 * @param {number} lastPage - The last page number to be included in the expanded pages.
 * @return {void}
 */
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

/**
 * Switches the upload type based on the selected value.
 * It displays the corresponding elements for the selected upload type and hides the elements for the not selected upload type.
 *
 * @param {string} selected - The selected upload type. It can be either 'file' or 'link'.
 *
 * @return {void}
 */
function switchUploadType(selected)
{
	let not_selected = selected === 'file' ? 'link' : 'file';
	document.getElementById('allowed_type_' + selected).style.display = '';
	document.getElementById('upload_type_' + selected).style.display = '';
	document.getElementById('allowed_type_' + not_selected).style.display = 'none';
	document.getElementById('upload_type_' + not_selected).style.display = 'none';
}

/**
 * Sends a JSON document to the specified URL using XMLHttpRequest. This is better than using jQuery.
 * Based on sendXMLDocument.
 *
 * @param {string} sUrl - The URL to send the JSON document to.
 * @param {string} sContent - The JSON document to send.
 * @param {function} funcCallback - The callback function to be called when the request is complete. It accepts one parameter, the parsed JSON response.
 * @return {boolean} - Returns true if the request was successfully sent.
 */
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

/**
 * Handles the like functionality for a given link.
 *
 * @param {HTMLElement} link - The link element to handle the like functionality for.
 * @return {boolean} Indicates whether the like functionality was handled successfully.
 */
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

/**
 * Handles bookmark operation for a given link.
 *
 * @param {Object} link - The link object to be bookmarked.
 * @param {string} link.href - The href property of the link.
 *
 * @return {boolean} - Whether the bookmark operation was successful or not.
 */
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

/**
 * Configures options for a bar chart.
 *
 * @param {Array} bar_data - The data to be visualized in the bar chart.
 * @param {Array} tooltips - An array of tooltips for each bar in the chart.
 * @return {Object} - The configuration object for the bar chart.
 */
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

/**
 * Clears the tooltip of the given element.
 *
 * @param {HTMLElement} elem - The element whose tooltip is to be cleared.
 *
 * @return {void}
 */
function lgalClearTooltip(elem) {
	elem.currentTarget.setAttribute("class", "lgal_share");
	elem.currentTarget.removeAttribute("aria-label");
}

/**
 * Sets the tooltip message on the specified element.
 *
 * @param {Element} elem - The element to set the tooltip on.
 * @param {string} msg - The tooltip message to be displayed.
 * @returns {void}
 */
function lgalShowTooltip(elem, msg) {
	elem.setAttribute("aria-label", msg);
	elem.setAttribute("class", "lgal_share tooltipped tooltipped-s");
}