/**
 * OA Woo Stock — Xero-style reconcile rows (WooCommerce | center | import).
 */
(function ($) {
	'use strict';

	var S = oaWooStockAdmin.strings;

	var LIST_SORT_STORAGE_KEY = 'oaWooStock_listSortMode';
	var MATCH_SESSION_KEY = 'oaWooStock_importMatch_v1';
	/** Associate controls with our empty form so WooCommerce / WP admin wrapper forms do not own them (prevents full-page POST on Enter / odd browser behaviour). */
	var OA_DETACHED_FORM_ATTR = ' form="oa-woo-stock-controls-form"';

	function readListSortMode() {
		try {
			var s = localStorage.getItem(LIST_SORT_STORAGE_KEY);
			if (s === 'name' || s === 'sheet') {
				return s;
			}
		} catch (e) {
			/* ignore */
		}
		return 'sheet';
	}

	var state = {
		variations: [],
		csvRows: [],
		varToCsv: {},
		/** Published variable products for parent checkboxes before CSV is loaded. */
		variableParentsCatalog: [],
		/** Original SKU per variation_id (from preview / session) for resetting when unlinking or linking a row with no Code. */
		variationSkuBaseline: {},
		listSortMode: readListSortMode(),
		rowUpdateFlash: null,
		previewWarnings: [],
		productApplyScope: 'all',
		selectedParentIdsForApply: {}
	};

	/**
	 * Sort mode from the toolbar select when present (source of truth), else state.
	 */
	function getListSortMode() {
		var $el = $('#oa-woo-stock-list-sort');
		if ($el.length) {
			var raw = String($el.val() || 'sheet').trim();
			var mode = raw === 'name' ? 'name' : 'sheet';
			state.listSortMode = mode;
			return mode;
		}
		return state.listSortMode === 'name' ? 'name' : 'sheet';
	}

	function escHtml(s) {
		return $('<div/>').text(s == null ? '' : String(s)).html();
	}

	function clearPersistedMatchSession() {
		try {
			sessionStorage.removeItem(MATCH_SESSION_KEY);
		} catch (e) {
			/* ignore */
		}
	}

	var persistMatchSessionTimer = null;

	function writeMatchSessionToStorage() {
		try {
			if (!state.csvRows.length && !state.variations.length) {
				sessionStorage.removeItem(MATCH_SESSION_KEY);
				return;
			}
			sessionStorage.setItem(
				MATCH_SESSION_KEY,
				JSON.stringify({
					v: 1,
					variations: state.variations,
					csvRows: state.csvRows,
					varToCsv: state.varToCsv,
					listSortMode: state.listSortMode,
					previewWarnings: state.previewWarnings || [],
					productApplyScope: state.productApplyScope === 'selected' ? 'selected' : 'all',
					selectedParentIdsForApply: state.selectedParentIdsForApply || {}
				})
			);
		} catch (e) {
			/* QuotaExceededError etc. */
		}
	}

	/** Save immediately so a full-page reload right after a dropdown change cannot lose the new link (debounced save was too slow). */
	function persistMatchSessionSync() {
		writeMatchSessionToStorage();
	}

	function schedulePersistMatchSession() {
		clearTimeout(persistMatchSessionTimer);
		persistMatchSessionTimer = setTimeout(function () {
			persistMatchSessionTimer = null;
			writeMatchSessionToStorage();
		}, 450);
	}

	function paintImportItemsSummary(stockBuckets) {
		var buckets = stockBuckets || getStockReviewWarningBuckets();
		var n = buckets.unlinked.length + buckets.wooHigher.length + buckets.wooLower.length;
		var t = String(S.importItemsCount).replace('%d', String(n));
		var $el = $('#oa-woo-stock-import-items-text');
		if ($el.length) {
			$el.text(t);
		}
	}

	function paintMatchStats(stockBuckets) {
		var buckets = stockBuckets || getStockReviewWarningBuckets();
		var $stats = $('#oa-woo-stock-stats');
		$stats.empty();
		$stats.append(
			'<div class="oa-woo-stock-stat"><strong>' +
				state.variations.length +
				'</strong><span>' +
				escHtml(S.statVariations) +
				'</span></div>'
		);
		$stats.append(
			'<div class="oa-woo-stock-stat"><strong>' +
				state.csvRows.length +
				'</strong><span>' +
				escHtml(S.statCsvRows) +
				'</span></div>'
		);
		var storeAbove = buckets.wooHigher.length;
		var warnCls =
			storeAbove > 0
				? 'oa-woo-stock-stat oa-woo-stock-stat--store-above-file'
				: 'oa-woo-stock-stat oa-woo-stock-stat--store-above-file oa-woo-stock-stat--store-above-file-zero';
		$stats.append(
			'<div class="' +
				warnCls +
				'"><strong>' +
				storeAbove +
				'</strong><span>' +
				escHtml(S.statStoreAboveFile) +
				'</span></div>'
		);
	}

	function paintMatchWarnings(stockBuckets) {
		var buckets = stockBuckets || getStockReviewWarningBuckets();
		var $warn = $('#oa-woo-stock-warnings');
		var parseErrs = state.previewWarnings || [];
		var hasAny =
			parseErrs.length ||
			buckets.unlinked.length ||
			buckets.wooHigher.length ||
			buckets.wooLower.length;
		if (!hasAny) {
			$warn.empty().prop('hidden', true);
			return;
		}
		var h = '<div class="notice notice-warning"><p><strong>' + escHtml(S.warningsHeading) + '</strong></p><ul>';
		parseErrs.forEach(function (e) {
			h += '<li>' + escHtml(e) + '</li>';
		});
		buckets.unlinked.forEach(function (e) {
			h += '<li class="oa-stock-warn-li oa-stock-warn-li--unlinked">' + escHtml(e) + '</li>';
		});
		buckets.wooHigher.forEach(function (e) {
			h += '<li class="oa-stock-warn-li oa-stock-warn-li--woo-higher">' + escHtml(e) + '</li>';
		});
		buckets.wooLower.forEach(function (e) {
			h += '<li class="oa-stock-warn-li oa-stock-warn-li--woo-lower">' + escHtml(e) + '</li>';
		});
		h += '</ul></div>';
		$warn.html(h).prop('hidden', false);
	}

	function revealMatchPanelsAndScroll() {
		$('#oa-woo-stock-match-ui').prop('hidden', false);
		$('#oa-woo-stock-import-results').prop('hidden', true).empty();

		var el = document.getElementById('oa-woo-stock-match-ui');
		if (el && el.scrollIntoView) {
			el.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		updateApplyEnabled();
	}

	function tryRestoreMatchSession() {
		try {
			var raw = sessionStorage.getItem(MATCH_SESSION_KEY);
			if (!raw) {
				return false;
			}
			var snap = JSON.parse(raw);
			if (!snap || snap.v !== 1 || !Array.isArray(snap.csvRows) || !Array.isArray(snap.variations)) {
				return false;
			}
			if (!snap.csvRows.length && !snap.variations.length) {
				return false;
			}
			state.rowUpdateFlash = null;
			state.variations = snap.variations;
			state.csvRows = snap.csvRows;
			state.varToCsv =
				snap.varToCsv && typeof snap.varToCsv === 'object' && !Array.isArray(snap.varToCsv)
					? snap.varToCsv
					: {};
			state.listSortMode =
				snap.listSortMode === 'name' || snap.listSortMode === 'sheet' ? snap.listSortMode : readListSortMode();
			state.previewWarnings = Array.isArray(snap.previewWarnings) ? snap.previewWarnings : [];
			state.productApplyScope = snap.productApplyScope === 'selected' ? 'selected' : 'all';
			state.selectedParentIdsForApply =
				snap.selectedParentIdsForApply &&
				typeof snap.selectedParentIdsForApply === 'object' &&
				!Array.isArray(snap.selectedParentIdsForApply)
					? snap.selectedParentIdsForApply
					: {};

			rebuildVariationSkuBaseline();

			var $sort = $('#oa-woo-stock-list-sort');
			if ($sort.length) {
				$sort.val(state.listSortMode);
			}
			renderReconcileList();
			syncProductScopeUiFromState();
			revealMatchPanelsAndScroll();
			return true;
		} catch (err) {
			return false;
		}
	}

	function getCsvIndexFromValue(val) {
		if (val === '' || val == null) {
			return null;
		}
		var n = parseInt(val, 10);
		return isNaN(n) ? null : n;
	}

	function findVarForCsv(csvIndex) {
		var found = null;
		Object.keys(state.varToCsv).forEach(function (vid) {
			if (state.varToCsv[vid] === csvIndex) {
				found = vid;
			}
		});
		return found;
	}

	function getVariation(vid) {
		var id = String(vid);
		for (var i = 0; i < state.variations.length; i++) {
			if (String(state.variations[i].variation_id) === id) {
				return state.variations[i];
			}
		}
		return null;
	}

	function parseQtyInt(val) {
		if (val === '' || val == null) {
			return null;
		}
		var n = parseInt(String(val), 10);
		return isNaN(n) ? null : n;
	}

	/**
	 * Single reference quantity from the CSV row for comparing to store stock (available column, else on hand, else apply default).
	 */
	function getSheetReferenceQty(csvIdx) {
		var row = state.csvRows[csvIdx];
		if (!row) {
			return null;
		}
		if (row.has_qty_available_column) {
			var a = parseQtyInt(row.qty_available_in_sheet);
			if (a !== null) {
				return a;
			}
		}
		var oh = parseQtyInt(row.qty_on_hand);
		if (oh !== null) {
			return oh;
		}
		return parseQtyInt(row.qty_available);
	}

	function variationStockAsInt(v) {
		if (!v) {
			return null;
		}
		var stock = v.current_stock;
		if (stock === null || stock === '' || typeof stock === 'undefined') {
			return null;
		}
		var n = parseInt(String(stock), 10);
		return isNaN(n) ? null : n;
	}

	function fillStockWarnTemplate(tpl, reps) {
		var s = String(tpl);
		Object.keys(reps).forEach(function (k) {
			s = s.split('[' + k + ']').join(String(reps[k]));
		});
		return s;
	}

	/**
	 * Stock review messages for the import screen: unlinked variations with stock; linked rows where store vs file quantities differ.
	 *
	 * @return {{unlinked: string[], wooHigher: string[], wooLower: string[]}}
	 */
	function getStockReviewWarningBuckets() {
		var unlinked = [];
		var wooHigher = [];
		var wooLower = [];
		if (!state.variations.length) {
			return { unlinked: unlinked, wooHigher: wooHigher, wooLower: wooLower };
		}

		state.variations.forEach(function (v) {
			var vid = String(v.variation_id);
			if (Object.prototype.hasOwnProperty.call(state.varToCsv, vid)) {
				return;
			}
			var cur = variationStockAsInt(v);
			if (cur === null || cur < 1) {
				return;
			}
			unlinked.push(
				fillStockWarnTemplate(S.stockWarnUnlinked, {
					variation_name: v.name || '',
					variation_id: vid,
					wc_stock: cur
				})
			);
		});

		Object.keys(state.varToCsv).forEach(function (vid) {
			var csvIdx = state.varToCsv[vid];
			if (csvIdx === undefined || csvIdx === null) {
				return;
			}
			var idx = parseInt(String(csvIdx), 10);
			if (isNaN(idx)) {
				return;
			}
			var v = getVariation(vid);
			var cur = variationStockAsInt(v);
			if (cur === null) {
				return;
			}
			var ref = getSheetReferenceQty(idx);
			if (ref === null) {
				return;
			}
			if (cur > ref) {
				wooHigher.push(
					fillStockWarnTemplate(S.stockWarnWooHigherThanFile, {
						variation_name: v && v.name ? v.name : '',
						variation_id: String(vid),
						wc_stock: cur,
						file_qty: ref
					})
				);
			} else if (cur < ref) {
				wooLower.push(
					fillStockWarnTemplate(S.stockWarnFileHigherThanWoo, {
						variation_name: v && v.name ? v.name : '',
						variation_id: String(vid),
						wc_stock: cur,
						file_qty: ref
					})
				);
			}
		});

		return { unlinked: unlinked, wooHigher: wooHigher, wooLower: wooLower };
	}

	/**
	 * Default for the "stock to set" input from a CSV row (Qty Available, else on hand when no avail column, else on hand as fallback when avail is blank).
	 */
	function getApplyQtyInputDefaultFromCsvRow(row) {
		if (!row) {
			return '';
		}
		if (row.qty_available !== '' && row.qty_available != null) {
			return String(row.qty_available);
		}
		if (!row.has_qty_available_column) {
			var oh = parseQtyInt(row.qty_on_hand);
			if (oh !== null) {
				return String(oh);
			}
			return '';
		}
		var ohFallback = parseQtyInt(row.qty_on_hand);
		if (ohFallback !== null) {
			return String(ohFallback);
		}
		return '';
	}

	/** After auto-links, pre-fill in-memory SKU from import Code when the variation has no SKU. */
	function syncEmptySkusFromLinkedCsvRows() {
		Object.keys(state.varToCsv).forEach(function (v2) {
			var csvI = state.varToCsv[v2];
			var r = state.csvRows[csvI];
			if (!r) {
				return;
			}
			var c = String(r.code || '').trim();
			if (!c) {
				return;
			}
			var vv = getVariation(v2);
			if (!vv) {
				return;
			}
			if (!String(vv.sku || '').trim()) {
				patchVariationInState(v2, { sku: c });
			}
		});
	}

	function patchVariationInState(vid, fields) {
		var id = String(vid);
		state.variations.forEach(function (v) {
			if (String(v.variation_id) !== id) {
				return;
			}
			if (fields.sku !== undefined) {
				v.sku = fields.sku;
			}
			if (fields.current_stock !== undefined) {
				v.current_stock = fields.current_stock;
			}
		});
	}

	function rebuildVariationSkuBaseline() {
		state.variationSkuBaseline = {};
		state.variations.forEach(function (v) {
			state.variationSkuBaseline[String(v.variation_id)] = v.sku != null ? String(v.sku) : '';
		});
	}

	/**
	 * Keep paired-row SKU / apply-qty inputs aligned with state after link changes (defensive; render should already match).
	 */
	function syncPairedRowFormFieldsFromState(vid, csvIdx) {
		vid = String(vid);
		var idx = parseInt(String(csvIdx), 10);
		if (isNaN(idx)) {
			return;
		}
		var $row = $(
			'.oa-reconcile-row--paired[data-variation-id="' + vid + '"][data-csv-index="' + idx + '"]'
		);
		if (!$row.length) {
			return;
		}
		var v = getVariation(vid);
		var row = state.csvRows[idx];
		var $sku = $row.find('.oa-var-sku');
		if ($sku.length && v) {
			$sku.val(v.sku != null ? String(v.sku) : '');
		}
		var $qty = $row.find('.oa-wc-apply-stock');
		if ($qty.length && row) {
			$qty.val(getApplyQtyInputDefaultFromCsvRow(row));
		}
		updateApplyEnabled();
	}

	/**
	 * @param {string} vid
	 * @param {number} csvIdx
	 * @param {{requireQty?: boolean}} opts
	 */
	function getPairedRowPayload(vid, csvIdx, opts) {
		opts = opts || {};
		var requireQty = !!opts.requireQty;
		var $row = $(
			'.oa-reconcile-row--paired[data-variation-id="' + vid + '"][data-csv-index="' + csvIdx + '"]'
		);
		if (!$row.length) {
			return null;
		}
		var qty = '';
		if ($row.find('.oa-wc-apply-stock').length) {
			qty = $row.find('.oa-wc-apply-stock').val();
		}
		var sku = $row.find('.oa-var-sku').length ? String($row.find('.oa-var-sku').val() || '').trim() : '';
		var qOk = !(qty === '' || qty == null);
		var sOk = sku !== '';
		if (requireQty && !qOk) {
			return null;
		}
		if (!requireQty && !qOk && !sOk) {
			return null;
		}
		var row = state.csvRows[csvIdx];
		var pid = parseInt(String(vid), 10);
		if (!Number.isFinite(pid) || pid < 1) {
			return null;
		}
		var obj = {
			product_id: pid,
			code: row && row.code ? row.code : ''
		};
		if (qOk) {
			obj.qty_available = String(qty).trim();
		}
		if (sOk) {
			obj.sku = sku;
		}
		return obj;
	}

	function isAutoSkuPair(vid, csvIdx) {
		var row = state.csvRows[csvIdx];
		return !!(row && row.sku_match_is_variation && String(row.suggested_variation_id) === String(vid));
	}

	/**
	 * True when this pair should use SKU-style UI: CSV matched variation on upload, or variation SKU equals import Code.
	 */
	function isSkuLinkedPair(vid, csvIdx) {
		if (isAutoSkuPair(vid, csvIdx)) {
			return true;
		}
		var v = getVariation(vid);
		var row = state.csvRows[csvIdx];
		if (!v || !row) {
			return false;
		}
		var sku = String(v.sku || '').trim().toLowerCase();
		var code = String(row.code || '').trim().toLowerCase();
		return sku !== '' && code !== '' && sku === code;
	}

	function setLink(variationId, csvIndex) {
		var vid = String(variationId);
		if (csvIndex === null || csvIndex === '' || typeof csvIndex === 'undefined') {
			if (state.variationSkuBaseline && Object.prototype.hasOwnProperty.call(state.variationSkuBaseline, vid)) {
				patchVariationInState(vid, { sku: state.variationSkuBaseline[vid] });
			}
			delete state.varToCsv[vid];
			renderReconcileList();
			updateApplyEnabled();
			persistMatchSessionSync();
			return;
		}

		var idx = parseInt(csvIndex, 10);
		if (isNaN(idx)) {
			return;
		}

		Object.keys(state.varToCsv).forEach(function (v2) {
			if (state.varToCsv[v2] === idx && v2 !== vid) {
				delete state.varToCsv[v2];
			}
		});

		state.varToCsv[vid] = idx;

		var linkedRow = state.csvRows[idx];
		if (linkedRow) {
			var importCode = String(linkedRow.code || '').trim();
			if (importCode) {
				patchVariationInState(vid, { sku: importCode });
			} else if (state.variationSkuBaseline && Object.prototype.hasOwnProperty.call(state.variationSkuBaseline, vid)) {
				patchVariationInState(vid, { sku: state.variationSkuBaseline[vid] });
			}
		}

		renderReconcileList();
		if (state.varToCsv[vid] === idx) {
			syncPairedRowFormFieldsFromState(vid, idx);
		}
		updateApplyEnabled();
		persistMatchSessionSync();
	}

	function buildVarOptionsHtml(selectedVid) {
		var h = '<option value="">' + escHtml(S.matchSelectVar) + '</option>';
		var rows = state.variations.slice();
		if (state.listSortMode === 'name') {
			rows.sort(function (a, b) {
				var c = localeCompareBase(a && a.name ? String(a.name) : '', b && b.name ? String(b.name) : '');
				if (c !== 0) {
					return c;
				}
				return parseInt(a.variation_id, 10) - parseInt(b.variation_id, 10);
			});
		}
		rows.forEach(function (v) {
			var id = String(v.variation_id);
			var sku = v.sku ? v.sku : '';
			var label = (v.name || '') + (sku ? ' [' + sku + ']' : '');
			h +=
				'<option value="' +
				escHtml(id) +
				'"' +
				(id === String(selectedVid) ? ' selected' : '') +
				'>' +
				escHtml(label) +
				'</option>';
		});
		return h;
	}

	function buildCsvOptionsHtml(selectedIdx) {
		var h = '<option value="">' + escHtml(S.matchSelectCsv) + '</option>';
		state.csvRows.forEach(function (row, i) {
			var shortDesc = row.description ? row.description : '';
			if (shortDesc.length > 40) {
				shortDesc = shortDesc.substring(0, 37) + '…';
			}
			var label = row.code + (shortDesc ? ' — ' + shortDesc : '');
			var sel =
				selectedIdx != null && selectedIdx !== '' && !isNaN(Number(selectedIdx)) && i === Number(selectedIdx)
					? ' selected'
					: '';
			h += '<option value="' + i + '"' + sel + '>' + escHtml(label) + '</option>';
		});
		return h;
	}

	function variationNameSortKey(vid) {
		var v = getVariation(String(vid));
		return v && v.name ? String(v.name) : '';
	}

	function localeCompareBase(a, b) {
		return String(a || '').localeCompare(String(b || ''), undefined, { sensitivity: 'base' });
	}

	function getUniqueParentsForBulkScope() {
		var map = {};
		if (state.variations.length) {
			state.variations.forEach(function (v) {
				var pid = v.parent_id != null && v.parent_id !== '' ? String(v.parent_id) : '';
				if (!pid) {
					return;
				}
				var t =
					v.parent_title != null && String(v.parent_title).trim() !== ''
						? String(v.parent_title)
						: '#' + pid;
				if (!map[pid]) {
					map[pid] = { parent_id: pid, title: t };
				}
			});
		} else if (state.variableParentsCatalog && state.variableParentsCatalog.length) {
			state.variableParentsCatalog.forEach(function (p) {
				var pid = p.parent_id != null && p.parent_id !== '' ? String(p.parent_id) : '';
				if (!pid) {
					return;
				}
				var t =
					p.title != null && String(p.title).trim() !== '' ? String(p.title) : '#' + pid;
				if (!map[pid]) {
					map[pid] = { parent_id: pid, title: t };
				}
			});
		}
		return Object.keys(map)
			.map(function (k) {
				return map[k];
			})
			.sort(function (a, b) {
				return localeCompareBase(a.title, b.title);
			});
	}

	function fetchVariableParentsCatalog(done) {
		$.ajax({
			url: oaWooStockAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'oa_woo_stock_variable_parents',
				nonce: oaWooStockAdmin.importNonce
			},
			success: function (res) {
				if (res.success && res.data && Array.isArray(res.data.parents)) {
					state.variableParentsCatalog = res.data.parents;
				} else {
					state.variableParentsCatalog = [];
				}
				if (typeof done === 'function') {
					done();
				}
			},
			error: function () {
				state.variableParentsCatalog = [];
				if (typeof done === 'function') {
					done();
				}
			}
		});
	}

	function syncProductScopeRadiosFromState() {
		var scope = state.productApplyScope === 'selected' ? 'selected' : 'all';
		state.productApplyScope = scope;
		var $r = $('input[name="oa-product-apply-scope"][value="' + scope + '"]');
		if ($r.length) {
			$r.prop('checked', true);
		}
	}

	function paintParentScopeCheckboxes() {
		var $wrap = $('#oa-woo-stock-parent-checkboxes');
		if (!$wrap.length) {
			return;
		}
		var parents = getUniqueParentsForBulkScope();
		var h = '';
		parents.forEach(function (p) {
			var checked = state.selectedParentIdsForApply[p.parent_id] ? ' checked' : '';
			h +=
				'<label class="oa-woo-stock-parent-cb-label">' +
				'<input type="checkbox" class="oa-parent-apply-cb"' +
				OA_DETACHED_FORM_ATTR +
				' data-parent-id="' +
				escHtml(p.parent_id) +
				'"' +
				checked +
				'>' +
				escHtml(p.title) +
				'</label>';
		});
		$wrap.html(h);
	}

	function updateParentScopeBoxVisibility() {
		$('#oa-woo-stock-parent-scope-box').prop('hidden', state.productApplyScope !== 'selected');
	}

	function ensureSelectedParentsDefaultWhenEmpty() {
		if (state.productApplyScope !== 'selected') {
			return;
		}
		var parents = getUniqueParentsForBulkScope();
		var any = false;
		for (var i = 0; i < parents.length; i++) {
			if (state.selectedParentIdsForApply[parents[i].parent_id]) {
				any = true;
				break;
			}
		}
		if (!any && parents.length) {
			parents.forEach(function (p) {
				state.selectedParentIdsForApply[p.parent_id] = true;
			});
		}
	}

	function syncProductScopeUiFromState() {
		syncProductScopeRadiosFromState();
		ensureSelectedParentsDefaultWhenEmpty();
		paintParentScopeCheckboxes();
		updateParentScopeBoxVisibility();
	}

	function isVariationParentIncludedInBulkApply(v) {
		if (!v || state.productApplyScope !== 'selected') {
			return true;
		}
		var pid = v.parent_id != null && v.parent_id !== '' ? String(v.parent_id) : '';
		if (!pid) {
			return true;
		}
		return !!state.selectedParentIdsForApply[pid];
	}

	function sortPairedRowsForDisplay(skuPairs, otherPairs, sortMode) {
		if (sortMode === 'name') {
			var cmp = function (a, b) {
				var c = localeCompareBase(variationNameSortKey(a.vid), variationNameSortKey(b.vid));
				if (c !== 0) {
					return c;
				}
				return a.csvIdx - b.csvIdx;
			};
			skuPairs.sort(cmp);
			otherPairs.sort(cmp);
			return;
		}
		skuPairs.sort(function (a, b) {
			return a.csvIdx - b.csvIdx;
		});
		otherPairs.sort(function (a, b) {
			return a.csvIdx - b.csvIdx;
		});
	}

	function getOrderedSections() {
		var sortMode = state.listSortMode === 'name' ? 'name' : 'sheet';
		var pairs = [];
		Object.keys(state.varToCsv).forEach(function (vid) {
			var csvIdx = state.varToCsv[vid];
			var skuLinked = isSkuLinkedPair(vid, csvIdx);
			pairs.push({
				vid: String(vid),
				csvIdx: csvIdx,
				autoSku: skuLinked
			});
		});

		var skuPairs = pairs.filter(function (p) {
			return p.autoSku;
		});

		var otherPairs = pairs.filter(function (p) {
			return !p.autoSku;
		});

		sortPairedRowsForDisplay(skuPairs, otherPairs, sortMode);

		var pairedVar = {};
		pairs.forEach(function (p) {
			pairedVar[p.vid] = true;
		});

		var varOnly = [];
		state.variations.forEach(function (v) {
			var id = String(v.variation_id);
			if (!pairedVar[id]) {
				varOnly.push(id);
			}
		});
		if (sortMode === 'name') {
			varOnly.sort(function (a, b) {
				var c = localeCompareBase(variationNameSortKey(a), variationNameSortKey(b));
				if (c !== 0) {
					return c;
				}
				return parseInt(a, 10) - parseInt(b, 10);
			});
		}

		return {
			skuPairs: skuPairs,
			otherPairs: otherPairs,
			varOnly: varOnly
		};
	}

	function rebuildAutomaticLinks() {
		applyInitialSkuLinks();
	}

	function renderCsvRowVarSelectHtml(csvIdx, linkedVariationId) {
		var selVid =
			linkedVariationId != null && linkedVariationId !== '' ? String(linkedVariationId) : '';
		return (
			'<select' +
			OA_DETACHED_FORM_ATTR +
			' class="oa-reconcile-select oa-reconcile-select--assign-var-solo oa-reconcile-select--table"' +
			' data-csv-index="' +
			csvIdx +
			'" aria-label="' +
			escHtml(S.matchSelectVar) +
			'">' +
			buildVarOptionsHtml(selVid) +
			'</select>'
		);
	}

	function renderVarOnlyCsvSelectHtml(vid) {
		var linkedIdx = state.varToCsv[String(vid)];
		var selIdx =
			linkedIdx !== undefined && linkedIdx !== null && !isNaN(Number(linkedIdx)) ? Number(linkedIdx) : null;
		return (
			'<select' +
			OA_DETACHED_FORM_ATTR +
			' class="oa-reconcile-select oa-reconcile-select--assign-csv-solo oa-reconcile-select--table"' +
			' data-variation-id="' +
			escHtml(String(vid)) +
			'" aria-label="' +
			escHtml(S.matchSelectCsv) +
			'">' +
			buildCsvOptionsHtml(selIdx) +
			'</select>'
		);
	}

	function renderWcCard(vid, opts) {
		opts = opts || {};
		var columnMode = opts.columnMode || 'all';
		var tableLayout = !!opts.tableLayout;
		var v = getVariation(vid);
		if (!v) {
			return '';
		}
		var stock = v.current_stock;
		var stockLabel = stock === null || stock === '' || typeof stock === 'undefined' ? '—' : String(stock);
		var isPaired = !!opts.isPaired;

		var showSkuField = !!opts.showSkuField;
		var importCode = (opts.importCode && String(opts.importCode).trim()) || '';

		var linksInner = '';
		if (v.admin_edit_url) {
			linksInner +=
				'<a href="' +
				escHtml(v.admin_edit_url) +
				'" class="oa-edit-variation-link" target="_blank" rel="noopener noreferrer">' +
				escHtml(S.editVariationLink) +
				'</a>';
		}

		var stockWrapClass = 'oa-reconcile-stock-wrap';
		var sheetRef =
			typeof opts.linkedCsvIdx === 'number' && !isNaN(opts.linkedCsvIdx)
				? getSheetReferenceQty(opts.linkedCsvIdx)
				: null;
		var curNum = null;
		if (stock !== null && stock !== '' && typeof stock !== 'undefined') {
			var parsedCur = parseInt(String(stock), 10);
			if (!isNaN(parsedCur)) {
				curNum = parsedCur;
			}
		}
		var stockTitle = '';
		if (sheetRef !== null) {
			if (curNum !== null && curNum === sheetRef) {
				stockWrapClass += ' oa-reconcile-stock-wrap--match';
				stockTitle = escHtml(S.stockVsSheetMatch);
			} else if (curNum !== null && curNum > sheetRef) {
				stockWrapClass += ' oa-reconcile-stock-wrap--store-higher';
				stockTitle = escHtml(S.stockVsSheetStoreHigher);
			} else if (curNum !== null && curNum < sheetRef) {
				stockWrapClass += ' oa-reconcile-stock-wrap--file-higher';
				stockTitle = escHtml(S.stockVsSheetFileHigher);
			} else {
				stockWrapClass += ' oa-reconcile-stock-wrap--diff';
				stockTitle = escHtml(S.stockVsSheetDiff);
			}
		}
		var showApplyQtyField =
			typeof opts.linkedCsvIdx === 'number' &&
			!isNaN(opts.linkedCsvIdx) &&
			state.csvRows[opts.linkedCsvIdx];
		var showInlineApplyStock = !!opts.showUpdateButton && showApplyQtyField;

		if (columnMode === 'store') {
			var omitSku = !!opts.omitSku;
			var matchSelectHtml = opts.matchSelectHtml ? String(opts.matchSelectHtml) : '';
			var storeHintHtml = opts.storeHintHtml ? String(opts.storeHintHtml) : '';
			var hs = '<div class="oa-reconcile-cell oa-reconcile-cell--store">';
			hs +=
				'<div class="oa-reconcile-card__meta screen-reader-text">' +
				escHtml(S.cardWcMeta) +
				'</div>';
			if (storeHintHtml) {
				hs += storeHintHtml;
			}
			if (showSkuField && !omitSku) {
				var skuVal = v.sku || '';
				var ph = '';
				if (!skuVal && importCode) {
					ph = escHtml(S.skuPlaceholderCode) + ': ' + escHtml(importCode);
				} else if (!skuVal) {
					ph = escHtml(S.skuPlaceholderCode);
				}
				hs +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--sku">' +
					'<div class="oa-reconcile-field-value oa-reconcile-field-value--full">' +
					'<input type="text" class="oa-var-sku regular-text"' +
					OA_DETACHED_FORM_ATTR +
					' value="' +
					escHtml(skuVal) +
					'" autocomplete="off" maxlength="100" aria-label="' +
					escHtml(S.skuLabel) +
					'" placeholder="' +
					ph +
					'">' +
					'</div></div>';
			} else if (!omitSku) {
				hs +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--sku" role="group" aria-label="' +
					escHtml(S.skuLabel) +
					'">' +
					'<div class="oa-reconcile-field-value oa-reconcile-field-value--full"><code>' +
					escHtml(v.sku || '') +
					'</code></div></div>';
			}
			if (matchSelectHtml) {
				hs +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--name-with-match">' +
					'<div class="oa-reconcile-name-match-row">' +
					'<div class="oa-reconcile-card__name oa-reconcile-card__name--with-match" role="group" aria-label="' +
					escHtml(S.variationNameLabel) +
					'">' +
					escHtml(v.name) +
					'</div>' +
					'<div class="oa-reconcile-name-match-row__select">' +
					matchSelectHtml +
					'</div></div></div>';
			} else {
				hs +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--name" role="group" aria-label="' +
					escHtml(S.variationNameLabel) +
					'">' +
					'<div class="oa-reconcile-field-value oa-reconcile-field-value--full oa-reconcile-card__name">' +
					escHtml(v.name) +
					'</div></div>';
			}
			if (isPaired) {
				hs += '<div class="oa-reconcile-lane oa-reconcile-lane--links">' + linksInner + '</div>';
			} else if (linksInner) {
				hs +=
					'<div class="oa-reconcile-card__row oa-reconcile-card__product-links">' +
					linksInner +
					'</div>';
			}
			hs += '</div>';
			return hs;
		}

		if (columnMode === 'sku') {
			var hkSku = '<div class="oa-reconcile-cell oa-reconcile-cell--sku">';
			hkSku +=
				'<div class="oa-reconcile-card__meta screen-reader-text">' +
				escHtml(S.cardSkuColMeta) +
				'</div>';
			if (showSkuField) {
				var skuValSk = v.sku || '';
				var phSk = '';
				if (!skuValSk && importCode) {
					phSk = escHtml(S.skuPlaceholderCode) + ': ' + escHtml(importCode);
				} else if (!skuValSk) {
					phSk = escHtml(S.skuPlaceholderCode);
				}
				hkSku +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--sku">' +
					'<div class="oa-reconcile-field-value oa-reconcile-field-value--full">' +
					'<input type="text" class="oa-var-sku regular-text"' +
					OA_DETACHED_FORM_ATTR +
					' value="' +
					escHtml(skuValSk) +
					'" autocomplete="off" maxlength="100" aria-label="' +
					escHtml(S.skuLabel) +
					'" placeholder="' +
					phSk +
					'">' +
					'</div></div>';
			} else {
				hkSku +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--sku" role="group" aria-label="' +
					escHtml(S.skuLabel) +
					'">' +
					'<div class="oa-reconcile-field-value oa-reconcile-field-value--full"><code>' +
					escHtml(v.sku || '') +
					'</code></div></div>';
			}
			hkSku += '</div>';
			return hkSku;
		}

		if (columnMode === 'stock') {
			var stockCircleClass = stockWrapClass + ' oa-reconcile-stock-wrap--circle';
			var hk = '<div class="oa-reconcile-cell oa-reconcile-cell--stock">';
			hk +=
				'<div class="oa-reconcile-card__meta screen-reader-text">' +
				escHtml(S.cardStockColMeta) +
				'</div>';
			if (!showApplyQtyField) {
				hk +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--stock oa-reconcile-lane--stock-with-circle">' +
					'<div class="oa-reconcile-field-value oa-reconcile-field-value--stock-circle">' +
					'<span class="' +
					stockCircleClass +
					'"' +
					(stockTitle ? ' title="' + stockTitle + '"' : '') +
					' aria-label="' +
					escHtml(S.stockLabel) +
					': ' +
					escHtml(stockLabel) +
					'">' +
					'<strong class="oa-reconcile-stock-num">' +
					escHtml(stockLabel) +
					'</strong></span></div></div>';
			} else {
				var csvR = state.csvRows[opts.linkedCsvIdx];
				var applyDef = getApplyQtyInputDefaultFromCsvRow(csvR);
				hk +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--stock oa-reconcile-lane--stock-col-inline">' +
					'<div class="oa-reconcile-stock-col-inline">' +
					'<span class="' +
					stockCircleClass +
					'"' +
					(stockTitle ? ' title="' + stockTitle + '"' : '') +
					' aria-label="' +
					escHtml(S.stockLabel) +
					': ' +
					escHtml(stockLabel) +
					'">' +
					'<strong class="oa-reconcile-stock-num">' +
					escHtml(stockLabel) +
					'</strong></span>' +
					'<input type="number"' +
					OA_DETACHED_FORM_ATTR +
					' class="oa-wc-apply-stock" min="0" step="1" value="' +
					escHtml(applyDef) +
					'" aria-label="' +
					escHtml(S.wcStockToSetLabel) +
					'" title="' +
					escHtml(S.wcStockToSetHint) +
					'">' +
					'</div></div>';
			}
			hk += '</div>';
			return hk;
		}

		if (columnMode === 'actions') {
			var ha = '<div class="oa-reconcile-cell oa-reconcile-cell--actions">';
			ha +=
				'<div class="oa-reconcile-card__meta screen-reader-text">' +
				escHtml(S.cardActionsColMeta) +
				'</div>';
			if (opts.showUpdateButton) {
				var flashOkA =
					state.rowUpdateFlash &&
					String(state.rowUpdateFlash.vid) === String(vid) &&
					state.rowUpdateFlash.msg;
				ha +=
					'<div class="oa-reconcile-lane oa-reconcile-lane--actions">' +
					'<div class="oa-reconcile-card__actions oa-reconcile-card__actions--stacked">' +
					(v.view_product_url
						? '<a href="' +
							escHtml(v.view_product_url) +
							'" class="button button-small oa-view-product-btn" target="_blank" rel="noopener noreferrer">' +
							escHtml(S.viewProductLink) +
							'</a>'
						: '') +
					'<button type="button" class="button button-primary oa-row-update-btn">' +
					escHtml(S.updateRowBtn) +
					'</button>' +
					'<span class="oa-row-update-feedback' +
					(flashOkA ? ' oa-row-update-feedback--ok' : '') +
					'">' +
					(flashOkA ? escHtml(state.rowUpdateFlash.msg) : '') +
					'</span>' +
					'</div></div>';
			}
			ha += '</div>';
			return ha;
		}

		var h = '<div class="oa-reconcile-card oa-reconcile-card--wc">';
		if (tableLayout) {
			h +=
				'<div class="oa-reconcile-card__meta screen-reader-text">' +
				escHtml(S.cardWcMeta) +
				'</div>';
		} else {
			h +=
				'<div class="oa-reconcile-card__meta">' +
				escHtml(S.cardWcMeta) +
				'</div>';
		}
		if (showSkuField) {
			var skuVal2 = v.sku || '';
			var ph2 = '';
			if (!skuVal2 && importCode) {
				ph2 = escHtml(S.skuPlaceholderCode) + ': ' + escHtml(importCode);
			} else if (!skuVal2) {
				ph2 = escHtml(S.skuPlaceholderCode);
			}
			h +=
				'<div class="oa-reconcile-lane oa-reconcile-lane--sku">' +
				'<div class="oa-reconcile-field-value oa-reconcile-field-value--full">' +
				'<input type="text" class="oa-var-sku regular-text"' +
				OA_DETACHED_FORM_ATTR +
				' value="' +
				escHtml(skuVal2) +
				'" autocomplete="off" maxlength="100" aria-label="' +
				escHtml(S.skuLabel) +
				'" placeholder="' +
				ph2 +
				'">' +
				'</div></div>';
		} else {
			h +=
				'<div class="oa-reconcile-lane oa-reconcile-lane--sku" role="group" aria-label="' +
				escHtml(S.skuLabel) +
				'">' +
				'<div class="oa-reconcile-field-value oa-reconcile-field-value--full"><code>' +
				escHtml(v.sku || '') +
				'</code></div></div>';
		}
		h +=
			'<div class="oa-reconcile-lane oa-reconcile-lane--name" role="group" aria-label="' +
			escHtml(S.variationNameLabel) +
			'">' +
			'<div class="oa-reconcile-field-value oa-reconcile-field-value--full oa-reconcile-card__name">' +
			escHtml(v.name) +
			'</div></div>';
		if (isPaired) {
			h += '<div class="oa-reconcile-lane oa-reconcile-lane--links">' + linksInner + '</div>';
		} else if (linksInner) {
			h +=
				'<div class="oa-reconcile-card__row oa-reconcile-card__product-links">' +
				linksInner +
				'</div>';
		}
		if (!showInlineApplyStock) {
			h +=
				'<div class="oa-reconcile-lane oa-reconcile-lane--stock">' +
				'<div class="oa-reconcile-field-value">' +
				'<span class="' +
				stockWrapClass +
				'"' +
				(stockTitle ? ' title="' + stockTitle + '"' : '') +
				' aria-label="' +
				escHtml(S.stockLabel) +
				': ' +
				escHtml(stockLabel) +
				'">' +
				'<strong class="oa-reconcile-stock-num">' +
				escHtml(stockLabel) +
				'</strong></span></div></div>';
		} else {
			var csvR2 = state.csvRows[opts.linkedCsvIdx];
			var applyDef2 = getApplyQtyInputDefaultFromCsvRow(csvR2);
			h +=
				'<div class="oa-reconcile-lane oa-reconcile-lane--apply oa-reconcile-lane--apply-inline">' +
				'<div class="oa-reconcile-field-value oa-reconcile-field-value--full">' +
				'<div class="oa-reconcile-apply-inline-row">' +
				'<span class="' +
				stockWrapClass +
				'"' +
				(stockTitle ? ' title="' + stockTitle + '"' : '') +
				'><strong class="oa-reconcile-stock-num">' +
				escHtml(stockLabel) +
				'</strong></span>' +
				'<input type="number"' +
				OA_DETACHED_FORM_ATTR +
				' class="oa-wc-apply-stock" min="0" step="1" value="' +
				escHtml(applyDef2) +
				'" aria-label="' +
				escHtml(S.wcStockToSetLabel) +
				'" title="' +
				escHtml(S.wcStockToSetHint) +
				'">' +
				'</div></div></div>';
		}
		if (opts.showUpdateButton) {
			var flashOk2 =
				state.rowUpdateFlash &&
				String(state.rowUpdateFlash.vid) === String(vid) &&
				state.rowUpdateFlash.msg;
			h +=
				'<div class="oa-reconcile-lane oa-reconcile-lane--actions">' +
				'<div class="oa-reconcile-card__actions">' +
				(v.view_product_url
					? '<a href="' +
						escHtml(v.view_product_url) +
						'" class="button button-small oa-view-product-btn" target="_blank" rel="noopener noreferrer">' +
						escHtml(S.viewProductLink) +
						'</a>'
					: '') +
				'<button type="button" class="button button-primary oa-row-update-btn">' +
				escHtml(S.updateRowBtn) +
				'</button>' +
				'<span class="oa-row-update-feedback' +
				(flashOk2 ? ' oa-row-update-feedback--ok' : '') +
				'">' +
				(flashOk2 ? escHtml(state.rowUpdateFlash.msg) : '') +
				'</span>' +
				'</div></div>';
		}
		h += '</div>';
		return h;
	}

	function renderPairedRow(p) {
		var skuClass = p.autoSku ? ' oa-reconcile-row--sku-match' : '';
		var imp = state.csvRows[p.csvIdx];
		var impCode = imp && imp.code ? imp.code : '';
		return (
			'<tr class="oa-reconcile-row oa-reconcile-row--paired' +
			skuClass +
			'" data-row-type="paired" data-variation-id="' +
			escHtml(p.vid) +
			'" data-csv-index="' +
			p.csvIdx +
			'">' +
			'<td class="oa-reconcile-td oa-reconcile-td--sku">' +
			renderWcCard(p.vid, {
				columnMode: 'sku',
				tableLayout: true,
				showSkuField: true,
				importCode: impCode
			}) +
			'</td>' +
			'<td class="oa-reconcile-td oa-reconcile-td--store">' +
			renderWcCard(p.vid, {
				columnMode: 'store',
				tableLayout: true,
				omitSku: true,
				matchSelectHtml: renderCsvRowVarSelectHtml(p.csvIdx, p.vid),
				importCode: impCode,
				isPaired: true,
				showSkuField: true
			}) +
			'</td>' +
			'<td class="oa-reconcile-td oa-reconcile-td--stock">' +
			renderWcCard(p.vid, {
				columnMode: 'stock',
				tableLayout: true,
				linkedCsvIdx: p.csvIdx,
				isPaired: true
			}) +
			'</td>' +
			'<td class="oa-reconcile-td oa-reconcile-td--actions">' +
			renderWcCard(p.vid, {
				columnMode: 'actions',
				tableLayout: true,
				showUpdateButton: true
			}) +
			'</td></tr>'
		);
	}

	function renderVarOnlyRow(vid) {
		var hint =
			'<p class="description oa-reconcile-td-hint">' +
			escHtml(S.importColumnHintVarOnly) +
			'</p>';
		return (
			'<tr class="oa-reconcile-row oa-reconcile-row--var-only" data-row-type="var-only" data-variation-id="' +
			escHtml(String(vid)) +
			'">' +
			'<td class="oa-reconcile-td oa-reconcile-td--sku">' +
			renderWcCard(vid, {
				columnMode: 'sku',
				tableLayout: true,
				showSkuField: true,
				importCode: ''
			}) +
			'</td>' +
			'<td class="oa-reconcile-td oa-reconcile-td--store">' +
			renderWcCard(vid, {
				columnMode: 'store',
				tableLayout: true,
				omitSku: true,
				matchSelectHtml: renderVarOnlyCsvSelectHtml(vid),
				storeHintHtml: hint,
				importCode: '',
				isPaired: false,
				showSkuField: true
			}) +
			'</td>' +
			'<td class="oa-reconcile-td oa-reconcile-td--stock">' +
			renderWcCard(vid, {
				columnMode: 'stock',
				tableLayout: true,
				isPaired: false
			}) +
			'</td>' +
			'<td class="oa-reconcile-td oa-reconcile-td--actions">' +
			renderWcCard(vid, {
				columnMode: 'actions',
				tableLayout: true,
				showUpdateButton: true
			}) +
			'</td></tr>'
		);
	}

	function renderSection(title, bodyRowsHtml) {
		if (!bodyRowsHtml) {
			return '';
		}
		return (
			'<div class="oa-reconcile-section">' +
			'<h4 class="oa-reconcile-section__title">' +
			escHtml(title) +
			'</h4>' +
			'<div class="oa-reconcile-table-wrap">' +
			'<table class="oa-reconcile-table">' +
			'<thead><tr>' +
			'<th scope="col" class="oa-reconcile-th oa-reconcile-th--sku">' +
			escHtml(S.thReconcileSku) +
			'</th>' +
			'<th scope="col" class="oa-reconcile-th oa-reconcile-th--store">' +
			escHtml(S.thReconcileStore) +
			'</th>' +
			'<th scope="col" class="oa-reconcile-th oa-reconcile-th--stock">' +
			escHtml(S.thReconcileStock) +
			'</th>' +
			'<th scope="col" class="oa-reconcile-th oa-reconcile-th--actions">' +
			escHtml(S.thReconcileActions) +
			'</th>' +
			'</tr></thead><tbody>' +
			bodyRowsHtml +
			'</tbody></table></div></div>'
		);
	}

	function renderReconcileList() {
		getListSortMode();
		var html = '';
		try {
			var ord = getOrderedSections();
			html += renderSection(
				S.sectionSkuMatched,
				ord.skuPairs.map(renderPairedRow).join('')
			);
			html += renderSection(
				S.sectionOtherPairs,
				ord.otherPairs.map(renderPairedRow).join('')
			);
			html += renderSection(
				S.sectionVarOnly,
				ord.varOnly.map(renderVarOnlyRow).join('')
			);
		} catch (err) {
			console.error('OA Woo Stock: render list', err);
			html =
				'<div class="notice notice-error"><p><strong>' +
				escHtml(S.renderListFailed) +
				'</strong></p><p>' +
				escHtml(String(err && err.message ? err.message : err)) +
				'</p></div>';
		}

		$('#oa-woo-stock-reconcile-list').html(html || '<p class="description">' + escHtml(S.uploadPreview) + '</p>');
		schedulePersistMatchSession();
		var stockBuckets = getStockReviewWarningBuckets();
		paintImportItemsSummary(stockBuckets);
		paintMatchStats(stockBuckets);
		paintMatchWarnings(stockBuckets);
	}

	function applyInitialSkuLinks() {
		var variationsById = {};
		state.variations.forEach(function (v) {
			variationsById[String(v.variation_id)] = v;
		});

		state.varToCsv = {};
		state.csvRows.forEach(function (row, idx) {
			var vid = row.suggested_variation_id;
			if (!vid || !row.sku_match_is_variation || !variationsById[String(vid)]) {
				return;
			}
			var vStr = String(vid);
			if (state.varToCsv[vStr] !== undefined) {
				return;
			}
			if (findVarForCsv(idx) !== null) {
				return;
			}
			state.varToCsv[vStr] = idx;
		});
	}

	function showMatchUi(data) {
		state.rowUpdateFlash = null;
		state.variations = data.variations || [];
		state.csvRows = data.csv_rows || [];
		rebuildVariationSkuBaseline();
		state.previewWarnings = data.errors && data.errors.length ? data.errors : [];

		rebuildAutomaticLinks();
		syncEmptySkusFromLinkedCsvRows();
		var $sort = $('#oa-woo-stock-list-sort');
		if ($sort.length) {
			$sort.val(state.listSortMode);
		}
		renderReconcileList();

		syncProductScopeUiFromState();
		revealMatchPanelsAndScroll();
		persistMatchSessionSync();
	}

	function collectImportPayload() {
		var out = [];
		Object.keys(state.varToCsv).forEach(function (vid) {
			var csvIdx = state.varToCsv[vid];
			var v = getVariation(vid);
			if (!isVariationParentIncludedInBulkApply(v)) {
				return;
			}
			var one = getPairedRowPayload(vid, csvIdx, {requireQty: true});
			if (one) {
				out.push(one);
			}
		});
		return out;
	}

	function sendImportRequest(rows, onDone, onFail) {
		$.ajax({
			url: oaWooStockAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'oa_woo_stock_import_process',
				nonce: oaWooStockAdmin.importNonce,
				import_data_json: JSON.stringify(rows)
			},
			success: function (res) {
				if (res.success) {
					onDone(res.data);
				} else {
					onFail(res.data && res.data.message ? res.data.message : S.importError);
				}
			},
			error: function (xhr, st, err) {
				onFail(err || st || S.importError);
			}
		});
	}

	function updateApplyEnabled() {
		var n = collectImportPayload().length;
		$('#oa-woo-stock-apply-btn').prop('disabled', n < 1);
	}

	function wireAssignVar($select, csvIdx) {
		var val = $select.val();
		if (!val) {
			var cur = findVarForCsv(csvIdx);
			if (cur) {
				setLink(cur, null);
			}
			return;
		}
		setLink(String(val), csvIdx);
	}

	function wireAssignCsv($select, vid) {
		var val = $select.val();
		if (val === '' || val == null) {
			setLink(vid, null);
			return;
		}
		setLink(vid, getCsvIndexFromValue(val));
	}

	$(document).ready(function () {
		$(document).on('change', '#oa-woo-stock-list-sort', function () {
			var raw = String($(this).val() || 'sheet').trim();
			state.listSortMode = raw === 'name' ? 'name' : 'sheet';
			try {
				localStorage.setItem(LIST_SORT_STORAGE_KEY, state.listSortMode);
			} catch (e) {
				/* ignore */
			}
			renderReconcileList();
		});

		var $root = $('.oa-woo-stock-tabs');
		if (!$root.length) {
			return;
		}

		$('#oa-woo-stock-controls-form').on('submit', function (e) {
			e.preventDefault();
			return false;
		});

		/* Block accidental submit of a parent WooCommerce / WP admin <form> when focus is inside our match UI (e.g. Enter in qty, or stray submit). */
		if (typeof document.addEventListener === 'function') {
			document.addEventListener(
				'submit',
				function (e) {
					var form = e.target;
					if (!form || form.nodeName !== 'FORM') {
						return;
					}
					if (form.id === 'oa-woo-stock-upload-form' || form.id === 'oa-woo-stock-controls-form') {
						return;
					}
					var importPanel = document.getElementById('oa-woo-stock-import');
					if (!importPanel || !importPanel.classList.contains('is-active')) {
						return;
					}
					var matchUi = document.getElementById('oa-woo-stock-match-ui');
					var scopePanel = document.getElementById('oa-woo-stock-product-scope');
					var ae = document.activeElement;
					var sub = 'submitter' in e ? e.submitter : null;
					var inMatch =
						matchUi &&
						!matchUi.hidden &&
						ae &&
						typeof matchUi.contains === 'function' &&
						matchUi.contains(ae);
					var inScope =
						scopePanel &&
						ae &&
						typeof scopePanel.contains === 'function' &&
						scopePanel.contains(ae);
					var subInMatch =
						sub && matchUi && !matchUi.hidden && typeof matchUi.contains === 'function' && matchUi.contains(sub);
					var subInScope =
						sub && scopePanel && typeof scopePanel.contains === 'function' && scopePanel.contains(sub);
					if (!inMatch && !inScope && !subInMatch && !subInScope) {
						return;
					}
					e.preventDefault();
					e.stopPropagation();
				},
				true
			);
		}

		var sessionRestored = tryRestoreMatchSession();
		if (sessionRestored) {
			$('.oa-woo-stock-section')
				.first()
				.prepend(
					'<div class="notice notice-info oa-woo-stock-session-notice"><p>' +
						escHtml(S.sessionRestored) +
						'</p></div>'
				);
		} else {
			fetchVariableParentsCatalog(function () {
				syncProductScopeUiFromState();
			});
		}

		var $sortInit = $('#oa-woo-stock-list-sort');
		if ($sortInit.length) {
			$sortInit.val(state.listSortMode);
		}

		$root.on('click', '.nav-tab-wrapper a', function (e) {
			e.preventDefault();
			var target = $(this).attr('href');
			$root.find('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$root.find('.oa-woo-stock-tab-panel').removeClass('is-active');
			$root.find(target).addClass('is-active');
		});

		$('#oa-woo-stock-upload-form').on('submit', function (e) {
			e.preventDefault();
			var input = document.getElementById('oa_woo_stock_csv');
			if (!input || !input.files || !input.files.length) {
				window.alert(S.chooseCsvAlert);
				return;
			}
			var fd = new FormData();
			fd.append('action', 'oa_woo_stock_import_preview');
			fd.append('nonce', oaWooStockAdmin.importNonce);
			fd.append('csv_file', input.files[0]);

			var $btn = $('#oa-woo-stock-upload-btn');
			$btn.prop('disabled', true).text(S.uploading);

			$.ajax({
				url: oaWooStockAdmin.ajaxUrl,
				type: 'POST',
				data: fd,
				processData: false,
				contentType: false,
				success: function (res) {
					$btn.prop('disabled', false).text(S.uploadPreview);
					if (res.success) {
						showMatchUi(res.data);
					} else {
						window.alert(res.data && res.data.message ? res.data.message : S.uploadFailAlert);
					}
				},
				error: function (xhr, st, err) {
					$btn.prop('disabled', false).text(S.uploadPreview);
					window.alert(S.uploadFailAlert + ' ' + (err || st));
				}
			});
		});

		$(document).on('change', '.oa-reconcile-select--assign-var-solo', function () {
			var csvIdx = parseInt($(this).attr('data-csv-index'), 10);
			wireAssignVar($(this), csvIdx);
		});

		$(document).on('change', '.oa-reconcile-select--assign-csv-solo', function () {
			var vid = String($(this).attr('data-variation-id'));
			wireAssignCsv($(this), vid);
		});

		$(document).on('change', '.oa-var-sku, .oa-wc-apply-stock', function () {
			updateApplyEnabled();
		});

		$(document).on('input', '.oa-wc-apply-stock', function () {
			updateApplyEnabled();
		});

		$(document).on('change', 'input[name="oa-product-apply-scope"]', function () {
			var raw = String($(this).val() || 'all');
			state.productApplyScope = raw === 'selected' ? 'selected' : 'all';
			if (state.productApplyScope === 'selected') {
				ensureSelectedParentsDefaultWhenEmpty();
			}
			paintParentScopeCheckboxes();
			updateParentScopeBoxVisibility();
			updateApplyEnabled();
			schedulePersistMatchSession();
		});

		$(document).on('change', '.oa-parent-apply-cb', function () {
			var pid = String($(this).data('parent-id') || '');
			if (!pid) {
				return;
			}
			if ($(this).is(':checked')) {
				state.selectedParentIdsForApply[pid] = true;
			} else {
				delete state.selectedParentIdsForApply[pid];
			}
			updateApplyEnabled();
			schedulePersistMatchSession();
		});

		$('#oa-woo-stock-parent-scope-all').on('click', function () {
			getUniqueParentsForBulkScope().forEach(function (p) {
				state.selectedParentIdsForApply[p.parent_id] = true;
			});
			paintParentScopeCheckboxes();
			updateApplyEnabled();
			schedulePersistMatchSession();
		});

		$('#oa-woo-stock-parent-scope-none').on('click', function () {
			state.selectedParentIdsForApply = {};
			paintParentScopeCheckboxes();
			updateApplyEnabled();
			schedulePersistMatchSession();
		});

		$('#oa-woo-stock-clear-links-btn').on('click', function () {
			rebuildAutomaticLinks();
			syncEmptySkusFromLinkedCsvRows();
			renderReconcileList();
			updateApplyEnabled();
			persistMatchSessionSync();
		});

		$('#oa-woo-stock-reset-ui-btn').on('click', function () {
			clearPersistedMatchSession();
			state.variations = [];
			state.csvRows = [];
			state.varToCsv = {};
			state.variationSkuBaseline = {};
			state.rowUpdateFlash = null;
			state.previewWarnings = [];
			state.productApplyScope = 'all';
			state.selectedParentIdsForApply = {};
			syncProductScopeUiFromState();
			$('#oa-woo-stock-reconcile-list').empty();
			$('#oa-woo-stock-match-ui').prop('hidden', true);
			$('#oa-woo-stock-stats').empty();
			$('#oa-woo-stock-warnings').empty().prop('hidden', true);
			$('#oa-woo-stock-import-results').prop('hidden', true).empty();
			$('#oa-woo-stock-upload-form')[0].reset();
			$('#oa-woo-stock-apply-btn').prop('disabled', true);
			paintImportItemsSummary({ unlinked: [], wooHigher: [], wooLower: [] });
		});

		$('#oa-woo-stock-apply-btn').on('click', function () {
			var payload = collectImportPayload();
			if (!payload.length) {
				window.alert(S.noImportRows);
				return;
			}
			if (!window.confirm('Update stock for ' + payload.length + ' variation(s)?')) {
				return;
			}
			var $btn = $(this);
			$btn.prop('disabled', true).text(S.importing);

			sendImportRequest(
				payload,
				function (d) {
					$btn.prop('disabled', false).text(S.importBtn);
					var html =
						'<div class="notice notice-success"><p><strong>' +
						escHtml(S.importSuccess) +
						'</strong></p><ul>' +
						'<li>' +
						escHtml(S.resultsUpdated) +
						': <strong>' +
						d.success +
						'</strong></li>' +
						'<li>' +
						escHtml(S.resultsFailed) +
						': <strong>' +
						d.failed +
						'</strong></li>' +
						'<li>' +
						escHtml(S.resultsSkipped) +
						': <strong>' +
						d.skipped +
						'</strong></li></ul>';
					if (d.errors && d.errors.length) {
						html += '<p><strong>' + escHtml(S.resultsErrors) + '</strong></p><ul>';
						d.errors.forEach(function (err) {
							html += '<li>' + escHtml(err) + '</li>';
						});
						html += '</ul>';
					}
					html += '</div>';
					$('#oa-woo-stock-import-results').html(html).prop('hidden', false);
					$('#oa-woo-stock-match-ui').prop('hidden', true);
					clearPersistedMatchSession();
					paintImportItemsSummary({ unlinked: [], wooHigher: [], wooLower: [] });
					updateApplyEnabled();
				},
				function (msg) {
					$btn.prop('disabled', false).text(S.importBtn);
					window.alert(S.importError + ' ' + String(msg));
					updateApplyEnabled();
				}
			);
		});

		$(document).on('click', '.oa-row-update-btn', function () {
			var $btn = $(this);
			var $row = $btn.closest('.oa-reconcile-row');
			var $fb = $row.find('.oa-row-update-feedback');
			var type = $row.attr('data-row-type');
			var vid;
			var payload;

			if (type === 'paired') {
				vid = String($row.attr('data-variation-id'));
				var csvIdx = parseInt($row.attr('data-csv-index'), 10);
				payload = getPairedRowPayload(vid, csvIdx, {requireQty: false});
				if (!payload) {
					window.alert(S.rowUpdateNeedQtyOrSku);
					return;
				}
				payload = [payload];
			} else if (type === 'var-only') {
				vid = String($row.attr('data-variation-id'));
				var varPid = parseInt(vid, 10);
				if (!Number.isFinite(varPid) || varPid < 1) {
					window.alert(S.rowUpdateNeedQtyOrSku);
					return;
				}
				var skuOnly = $row.find('.oa-var-sku').length
					? String($row.find('.oa-var-sku').val() || '').trim()
					: '';
				if (!skuOnly) {
					window.alert(S.rowUpdateNeedQtyOrSku);
					return;
				}
				payload = [
					{
						product_id: varPid,
						code: '',
						sku: skuOnly
					}
				];
			} else {
				return;
			}

			$fb.removeClass('oa-row-update-feedback--error').text('');
			$btn.prop('disabled', true).text(S.rowUpdating);

			sendImportRequest(
				payload,
				function (d) {
					$btn.prop('disabled', false).text(S.updateRowBtn);
					if (d.success >= 1) {
						var p = payload[0];
						if (type === 'paired') {
							var fields = {};
							if (p.qty_available !== undefined && p.qty_available !== '') {
								fields.current_stock = parseInt(p.qty_available, 10);
							}
							if (p.sku) {
								fields.sku = p.sku;
								state.variationSkuBaseline[String(vid)] = String(p.sku);
							}
							patchVariationInState(vid, fields);
						} else {
							patchVariationInState(vid, { sku: p.sku });
							state.variationSkuBaseline[String(vid)] = String(p.sku);
						}
						state.rowUpdateFlash = {vid: String(vid), msg: S.rowUpdateOk};
						renderReconcileList();
						updateApplyEnabled();
						persistMatchSessionSync();
						var flashVid = String(vid);
						window.setTimeout(function () {
							if (
								state.rowUpdateFlash &&
								state.rowUpdateFlash.vid === flashVid &&
								state.rowUpdateFlash.msg === S.rowUpdateOk
							) {
								state.rowUpdateFlash = null;
								renderReconcileList();
							}
						}, 3200);
					} else if (d.errors && d.errors.length) {
						$fb.addClass('oa-row-update-feedback--error').text(d.errors[0]);
					} else if (d.skipped > 0) {
						$fb.addClass('oa-row-update-feedback--error').text(S.rowUpdateSkipped);
					} else {
						$fb.addClass('oa-row-update-feedback--error').text(S.rowUpdateFail);
					}
				},
				function (msg) {
					$btn.prop('disabled', false).text(S.updateRowBtn);
					$fb.addClass('oa-row-update-feedback--error').text(String(msg));
				}
			);
		});
	});
})(jQuery);
