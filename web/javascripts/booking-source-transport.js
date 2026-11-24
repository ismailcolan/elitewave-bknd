/**
 * GCN booking: Source of transport (vehicle / train / flight) follows Mode of Transport.
 * Requires window.BOOKING_MODE_KIND, #mode_of_trasport, #vehicle_type (hidden), optional #train_name_hidden.
 */
(function ($) {
	'use strict';

	function transportKind() {
		var id = $('#mode_of_trasport').val();
		var map = window.BOOKING_MODE_KIND || {};
		return (id && map[String(id)]) ? map[String(id)] : 'road';
	}

	function selectedLabel($sel) {
		if (!$sel.length || !$sel.val()) {
			return '';
		}
		var txt = $.trim($sel.find('option:selected').text());
		if (txt === '' || txt.indexOf('Select ') === 0) {
			return '';
		}
		return txt;
	}

	function setRemoteEnabled($sel, on) {
		if (!$sel.length) {
			return;
		}
		if (on) {
			var kind = $sel.hasClass('booking-source-train') ? 'train' : 'flight';
			$sel.prop('disabled', false).attr('data-remote-source', kind);
		} else {
			$sel.prop('disabled', true).removeAttr('data-remote-source');
		}
	}

	window.syncBookingSourceTransportHidden = function () {
		var kind = transportKind();
		var $hidden = $('#vehicle_type');
		var $trainHidden = $('#train_name_hidden');
		if (!$hidden.length) {
			return;
		}
		if (kind === 'train') {
			var $t = $('#booking_source_train');
			var id = $t.val();
			var lbl = selectedLabel($t);
			$hidden.val(id ? 'train:' + id : '');
			if ($trainHidden.length) {
				$trainHidden.val(lbl);
			}
			$('#vehicle_type_dims_display').val('');
		} else if (kind === 'flight') {
			var $f = $('#booking_source_flight');
			var fid = $f.val();
			$hidden.val(fid ? 'flight:' + fid : '');
			if ($trainHidden.length) {
				$trainHidden.val('');
			}
			$('#vehicle_type_dims_display').val('');
		} else {
			var $v = $('#booking_source_vehicle');
			var vid = $v.val();
			var name = '';
			if (vid) {
				var $opt = $v.find('option:selected');
				name = $.trim($opt.attr('data-type-name') || '');
				if (!name && window.BOOKING_VEHICLE_NAMES) {
					name = window.BOOKING_VEHICLE_NAMES[String(vid)] || window.BOOKING_VEHICLE_NAMES[parseInt(vid, 10)] || '';
				}
				if (!name) {
					name = selectedLabel($v);
				}
			}
			$hidden.val(name);
			if ($trainHidden.length) {
				$trainHidden.val('');
			}
			if (typeof window.syncVehicleTypeDimsDisplay === 'function') {
				window.syncVehicleTypeDimsDisplay();
			}
		}
	};

	window.applyBookingSourceTransportUi = function (resetSource) {
		if (!$('#booking_source_transport_slots').length) {
			return;
		}
		var kind = transportKind();
		var $road = $('#booking_source_vehicle');
		var $train = $('#booking_source_train');
		var $flight = $('#booking_source_flight');

		$road.toggle(kind === 'road').css('display', kind === 'road' ? '' : 'none');
		$train.toggle(kind === 'train').css('display', kind === 'train' ? '' : 'none');
		$flight.toggle(kind === 'flight').css('display', kind === 'flight' ? '' : 'none');

		$road.prop('disabled', kind !== 'road');
		setRemoteEnabled($train, kind === 'train');
		setRemoteEnabled($flight, kind === 'flight');

		var $dimsWrap = $('#vehicle_type_dims_wrap');
		if ($dimsWrap.length) {
			$dimsWrap.toggle(kind === 'road');
		}

		if (resetSource) {
			if (kind !== 'road') {
				$road.val('');
			}
			if (kind !== 'train') {
				$train.val('');
			}
			if (kind !== 'flight') {
				$flight.val('');
			}
		}

		window.syncBookingSourceTransportHidden();
	};

	$(function () {
		if (!$('#booking_source_transport_slots').length) {
			return;
		}
		window.applyBookingSourceTransportUi(false);
		$(document).on('change', '#mode_of_trasport', function () {
			window.applyBookingSourceTransportUi(true);
		});
		$(document).on('change', '#booking_source_vehicle, #booking_source_train, #booking_source_flight', function () {
			window.syncBookingSourceTransportHidden();
		});
		$(document).on('submit', 'form', function () {
			if ($('#booking_source_transport_slots').length) {
				window.syncBookingSourceTransportHidden();
			}
		});
	});
})(jQuery);